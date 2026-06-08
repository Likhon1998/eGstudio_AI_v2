<?php

namespace App\Http\Controllers;

use App\Models\CgiGeneration;
use App\Support\PublicMediaUrl;
use App\Models\MediaApproval;
use App\Models\Occasion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class ApprovalController
 * =========================================================================
 * Powers the client "two credentials" approval workflow:
 *
 *  - USER (client) : the account that generates pics/videos. Every asset it
 *                    creates is automatically visible to its approver and
 *                    CANNOT be published to social until it is Approved.
 *  - APPROVER      : a second credential attached to that user. Gets its own
 *                    dashboard listing every pic & video the user makes, with
 *                    approve / reject + a note the user can read.
 */
class ApprovalController extends Controller
{
    /**
     * Normalise a media URL down to its file name so the gallery URL (asset()),
     * the publish pipeline URL (url()/cloudinary) and the stored approval all
     * match regardless of host or query string.
     */
    public static function normalizeUrl(?string $url): string
    {
        if (!$url) {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return strtolower(trim(basename($path)));
    }

    /**
     * Approval record for a specific logical media slot (generation + type + variant).
     */
    public static function approvalRecord(string $source, string $genId, string $mediaType, string $variant = 'merged'): ?MediaApproval
    {
        return MediaApproval::where('source', $source)
            ->where('cgi_generation_id', $genId)
            ->where('media_type', $mediaType)
            ->where('variant', $variant)
            ->first();
    }

    /**
     * Status + comment for maker UI (defaults to pending when no record exists yet).
     */
    public static function approvalMeta(string $source, string $genId, string $mediaType, string $variant = 'merged'): array
    {
        $approval = self::approvalRecord($source, $genId, $mediaType, $variant);

        return [
            'status'  => $approval->status ?? MediaApproval::STATUS_PENDING,
            'comment' => $approval->comment ?? '',
        ];
    }

    /** @deprecated Use approvalMeta() with explicit variant. */
    public static function mergedApprovalMeta(string $source, string $genId, string $mediaType): array
    {
        return self::approvalMeta($source, $genId, $mediaType, 'merged');
    }

    /**
     * Normalized publishable URLs for a generation (used by the publish gate).
     */
    public static function publishableUrlKeys(string $source, CgiGeneration|Occasion $generation): array
    {
        // Only merged picture & merged video require approver sign-off (not raw or branded).
        $fields = ['merged_image_url', 'merged_video_url'];

        $keys = [];
        foreach ($fields as $field) {
            $raw = $generation->{$field} ?? null;
            if ($raw) {
                $key = self::normalizeUrl(self::resolveUrl($raw));
                if ($key !== '') {
                    $keys[] = $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Maker-side approval map keyed by normalized URL (for gallery cards).
     * Returns [array $approvalMap, bool $requiresApproval].
     */
    public static function buildMakerApprovalContext(User $user): array
    {
        $approvalMap = [];
        $requiresApproval = $user->requiresApproval();

        if ($requiresApproval) {
            foreach (MediaApproval::where('maker_id', $user->id)->get() as $approval) {
                $key = self::normalizeUrl($approval->media_url);
                if ($key !== '') {
                    $approvalMap[$key] = $approval;
                }
            }
        }

        return [$approvalMap, $requiresApproval];
    }

    /**
     * Returns an error message when publishing must be blocked, or null when allowed.
     * Users with an approver may only publish merged pictures/videos that are already approved.
     */
    public static function publishBlockedReason(
        User $user,
        string $source,
        string $generationId,
        ?string $mediaUrl,
        CgiGeneration|Occasion|null $generation = null
    ): ?string {
        if ($user->isAdmin() || !$user->requiresApproval()) {
            return null;
        }

        $normalized = self::normalizeUrl($mediaUrl);
        if ($normalized === '') {
            return 'Invalid media URL.';
        }

        if (!$generation) {
            $generation = $source === 'occasion'
                ? Occasion::find($generationId)
                : CgiGeneration::find($generationId);
        }

        if (!$generation) {
            return 'Asset not found.';
        }

        $allowed = self::publishableUrlKeys($source, $generation);

        if (!in_array($normalized, $allowed, true)) {
            return 'Your account requires approver sign-off. Only approved merged pictures and merged videos can be published.';
        }

        $approved = MediaApproval::where('cgi_generation_id', $generationId)
            ->where('source', $source)
            ->where('status', MediaApproval::STATUS_APPROVED)
            ->get()
            ->contains(fn ($a) => self::normalizeUrl($a->media_url) === $normalized);

        if (!$approved) {
            $record = MediaApproval::where('cgi_generation_id', $generationId)
                ->where('source', $source)
                ->get()
                ->first(fn ($a) => self::normalizeUrl($a->media_url) === $normalized);

            if ($record && $record->status === MediaApproval::STATUS_REJECTED) {
                $note = $record->comment ? ' Reason: ' . $record->comment : '';

                return 'This asset was rejected by your approver and cannot be published.' . $note;
            }

            $isMergedVideo = self::normalizeUrl(self::resolveUrl($generation->merged_video_url ?? '')) === $normalized;

            return $isMergedVideo
                ? 'This merged video must be approved by your approver before it can be published.'
                : 'This merged picture must be approved by your approver before it can be published.';
        }

        return null;
    }

    /**
     * Resolve a stored path/URL into a fully qualified, viewable URL
     * (same rule the galleries use).
     */
    protected static function resolveUrl(string $raw): string
    {
        return PublicMediaUrl::forMedia($raw);
    }

    /**
     * Category definitions (key => label), in display order. Each generated
     * media item is bucketed into exactly one of these so the approver can
     * review merged pictures and merged videos (not raw or branded).
     */
    public const CATEGORIES = [
        'pics_merged' => 'Merged Pictures',
        'vids_merged' => 'Merged Videos',
        'occ_merged'  => 'Occasion Merged',
    ];

    /**
     * =====================================================================
     * AUTO-DISCOVER every pic/video a client user has generated (CGI Studio
     * + Occasion Studio), each decorated with its current approval status.
     * This is what the approver reviews — no manual "submit" step required.
     * =====================================================================
     * Returns a Collection of stdClass media items.
     */
    public static function buildClientMedia(int $clientUserId)
    {
        // Existing decisions keyed by the exact logical slot so each media item
        // maps to its OWN approval (filename alone can collide between variants).
        $approvals = MediaApproval::where('maker_id', $clientUserId)
            ->get()
            ->keyBy(fn ($a) => $a->source . '|' . $a->cgi_generation_id . '|' . $a->media_type . '|' . $a->variant);

        $items = collect();

        $push = function (string $source, string $genId, ?string $rawUrl, string $type, string $variant, bool $branded, string $categoryKey, ?string $product, $createdAt) use (&$items, $approvals) {
            if (empty($rawUrl)) {
                return;
            }

            $full     = self::resolveUrl($rawUrl);
            $approval = $approvals->get($source . '|' . $genId . '|' . $type . '|' . $variant);

            $items->push((object) [
                'source'         => $source,
                'generation_id'  => $genId,
                'product_name'   => $product,
                'media_url'      => $full,
                'media_type'     => $type,
                'variant'        => $variant,
                'is_branded'     => $branded,
                'category_key'   => $categoryKey,
                'category_label' => self::CATEGORIES[$categoryKey] ?? ucfirst($categoryKey),
                'status'         => $approval->status ?? MediaApproval::STATUS_PENDING,
                'comment'        => $approval->comment ?? null,
                'reviewed_at'    => $approval->reviewed_at ?? null,
                'created_at'     => $createdAt,
            ]);
        };

        // ---- CGI STUDIO (merged picture + merged video) ----
        foreach (CgiGeneration::where('user_id', $clientUserId)->orderByDesc('created_at')->get() as $g) {
            $push('cgi', $g->id, $g->merged_image_url, 'image', 'merged', false, 'pics_merged', $g->product_name, $g->created_at);
            $push('cgi', $g->id, $g->merged_video_url, 'video', 'merged', false, 'vids_merged', $g->product_name, $g->created_at);
        }

        // ---- OCCASION STUDIO (merged pictures only) ----
        foreach (Occasion::where('user_id', $clientUserId)->orderByDesc('created_at')->get() as $o) {
            $title = $o->occasion_identity ?: 'Occasion Campaign';
            $push('occasion', $o->id, $o->merged_image_url, 'image', 'merged', false, 'occ_merged', $title, $o->created_at);
        }

        return $items;
    }

    /**
     * Maker studio: merged-asset approval history for one source (cgi | occasion).
     */
    public static function studioApprovalHistory(string $source, int $makerId): array
    {
        $items = self::buildClientMedia($makerId)->where('source', $source)->values();

        return self::formatStudioApprovalHistory($items, User::find($makerId));
    }

    /**
     * Admin studio: approval history for merged assets on visible index rows.
     *
     * @param  iterable<int, CgiGeneration|Occasion>  $models
     */
    public static function studioApprovalHistoryFromModels(string $source, iterable $models): array
    {
        $collection = collect($models);
        if ($collection->isEmpty()) {
            return self::formatStudioApprovalHistory(collect(), null);
        }

        $ids = $collection->pluck('id');
        $approvals = MediaApproval::where('source', $source)
            ->whereIn('cgi_generation_id', $ids)
            ->get()
            ->keyBy(fn ($a) => $a->cgi_generation_id . '|' . $a->media_type . '|' . $a->variant);

        $items = collect();

        foreach ($collection as $gen) {
            $product = $source === 'cgi'
                ? ($gen->product_name ?? 'Untitled')
                : ($gen->occasion_identity ?: 'Occasion Campaign');

            $slots = [
                ['merged_image_url', 'image', 'merged', $source === 'cgi' ? 'pics_merged' : 'occ_merged'],
            ];
            if ($source === 'cgi') {
                $slots[] = ['merged_video_url', 'video', 'merged', 'vids_merged'];
            }

            foreach ($slots as [$field, $type, $variant, $categoryKey]) {
                $raw = $gen->{$field} ?? null;
                if (empty($raw)) {
                    continue;
                }

                $approval = $approvals->get($gen->id . '|' . $type . '|' . $variant);

                $items->push((object) [
                    'source'         => $source,
                    'generation_id'  => $gen->id,
                    'product_name'   => $product,
                    'media_url'      => self::resolveUrl($raw),
                    'media_type'     => $type,
                    'variant'        => $variant,
                    'category_label' => self::CATEGORIES[$categoryKey] ?? ucfirst($categoryKey),
                    'status'         => $approval->status ?? MediaApproval::STATUS_PENDING,
                    'comment'        => $approval->comment ?? null,
                    'reviewed_at'    => $approval->reviewed_at ?? null,
                    'created_at'     => $gen->created_at,
                ]);
            }
        }

        return self::formatStudioApprovalHistory($items->sortByDesc('created_at')->values(), null);
    }

    public static function emptyStudioApprovalHistory(): array
    {
        return self::formatStudioApprovalHistory(collect(), null);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $items
     */
    protected static function formatStudioApprovalHistory($items, ?User $maker): array
    {
        $sortReviewed = fn ($i) => $i->reviewed_at ?? $i->created_at;

        return [
            'stats' => [
                'pending'  => $items->where('status', MediaApproval::STATUS_PENDING)->count(),
                'approved' => $items->where('status', MediaApproval::STATUS_APPROVED)->count(),
                'rejected' => $items->where('status', MediaApproval::STATUS_REJECTED)->count(),
                'total'    => $items->count(),
            ],
            'pending'  => $items->where('status', MediaApproval::STATUS_PENDING)->sortByDesc('created_at')->values(),
            'approved' => $items->where('status', MediaApproval::STATUS_APPROVED)->sortByDesc($sortReviewed)->values(),
            'rejected' => $items->where('status', MediaApproval::STATUS_REJECTED)->sortByDesc($sortReviewed)->values(),
            'approver_name' => $maker
                ? User::where('client_id', $maker->id)->where('account_type', 'approver')->value('name')
                : null,
        ];
    }

    /**
     * Count of pics/videos still awaiting this approver's decision (nav badge).
     */
    public static function pendingCountForApprover(User $approver): int
    {
        if (!$approver->isApprover() || !$approver->client_id) {
            return 0;
        }

        $clientId = (int) $approver->client_id;

        return Cache::remember(
            "approver.pending.{$approver->id}",
            60,
            fn () => self::countPendingClientMedia($clientId),
        );
    }

    /**
     * Fast SQL count of merged assets awaiting review (nav badge only).
     */
    private static function countPendingClientMedia(int $clientUserId): int
    {
        $pending = MediaApproval::STATUS_PENDING;

        $cgiImages = DB::table('cgi_generations as g')
            ->where('g.user_id', $clientUserId)
            ->whereNotNull('g.merged_image_url')
            ->leftJoin('media_approvals as a', function ($join) use ($clientUserId) {
                $join->on('a.cgi_generation_id', '=', 'g.id')
                    ->where('a.maker_id', $clientUserId)
                    ->where('a.source', 'cgi')
                    ->where('a.media_type', 'image')
                    ->where('a.variant', 'merged');
            })
            ->where(fn ($q) => $q->whereNull('a.id')->orWhere('a.status', $pending))
            ->count();

        $cgiVideos = DB::table('cgi_generations as g')
            ->where('g.user_id', $clientUserId)
            ->whereNotNull('g.merged_video_url')
            ->leftJoin('media_approvals as a', function ($join) use ($clientUserId) {
                $join->on('a.cgi_generation_id', '=', 'g.id')
                    ->where('a.maker_id', $clientUserId)
                    ->where('a.source', 'cgi')
                    ->where('a.media_type', 'video')
                    ->where('a.variant', 'merged');
            })
            ->where(fn ($q) => $q->whereNull('a.id')->orWhere('a.status', $pending))
            ->count();

        $occImages = DB::table('occasions as o')
            ->where('o.user_id', $clientUserId)
            ->whereNotNull('o.merged_image_url')
            ->leftJoin('media_approvals as a', function ($join) use ($clientUserId) {
                $join->on('a.cgi_generation_id', '=', 'o.id')
                    ->where('a.maker_id', $clientUserId)
                    ->where('a.source', 'occasion')
                    ->where('a.media_type', 'image')
                    ->where('a.variant', 'merged');
            })
            ->where(fn ($q) => $q->whereNull('a.id')->orWhere('a.status', $pending))
            ->count();

        return $cgiImages + $cgiVideos + $occImages;
    }

    /**
     * =====================================================================
     * APPROVER: REVIEW DASHBOARD
     * =====================================================================
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isApprover()) {
            abort(403, 'This area is reserved for client approval accounts.');
        }

        $clientId = (int) $user->client_id;
        $media    = self::buildClientMedia($clientId);

        $stats = [
            'pending'  => $media->where('status', MediaApproval::STATUS_PENDING)->count(),
            'approved' => $media->where('status', MediaApproval::STATUS_APPROVED)->count(),
            'rejected' => $media->where('status', MediaApproval::STATUS_REJECTED)->count(),
            'total'    => $media->count(),
        ];

        $filter = in_array($request->get('filter'), ['pending', 'approved', 'rejected'])
            ? $request->get('filter')
            : 'pending';

        $items = $media->where('status', $filter)->values();

        // Build the category tabs (only those that actually have items in this view).
        $categories = [];
        foreach (self::CATEGORIES as $key => $label) {
            $count = $items->where('category_key', $key)->count();
            if ($count > 0) {
                $categories[] = ['key' => $key, 'label' => $label, 'count' => $count];
            }
        }

        // Active sidebar section (Dashboard / Promotional / Occasional).
        $section = in_array($request->get('section'), ['dashboard', 'promotional', 'occasional'])
            ? $request->get('section')
            : 'dashboard';

        // Per-source counts so each section shows its own status totals + badges.
        $count = fn (string $src, string $st) => $media->where('source', $src)->where('status', $st)->count();

        $promoCounts = [
            'pending'  => $count('cgi', MediaApproval::STATUS_PENDING),
            'approved' => $count('cgi', MediaApproval::STATUS_APPROVED),
            'rejected' => $count('cgi', MediaApproval::STATUS_REJECTED),
            'total'    => $media->where('source', 'cgi')->count(),
        ];
        $occCounts = [
            'pending'  => $count('occasion', MediaApproval::STATUS_PENDING),
            'approved' => $count('occasion', MediaApproval::STATUS_APPROVED),
            'rejected' => $count('occasion', MediaApproval::STATUS_REJECTED),
            'total'    => $media->where('source', 'occasion')->count(),
        ];

        // A compact "needs your attention" queue for the dashboard landing,
        // independent of the active status filter.
        $pendingPreview = $media->where('status', MediaApproval::STATUS_PENDING)
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        $maker = User::find($clientId);

        // The client's active subscription (so the approver can see when it expires).
        $activeWallet = \App\Models\UserPackage::with('package')
            ->where('user_id', $clientId)
            ->where('is_active_selection', 'true')
            ->latest('id')
            ->first();

        $subscription = [
            'package'    => $activeWallet?->package?->name,
            'expires_at' => $activeWallet?->expires_at ? \Carbon\Carbon::parse($activeWallet->expires_at) : null,
            'active'     => $activeWallet && (is_null($activeWallet->expires_at) || \Carbon\Carbon::parse($activeWallet->expires_at)->isFuture()),
        ];

        return view('approvals.index', compact(
            'items', 'stats', 'filter', 'maker', 'subscription', 'categories',
            'section', 'promoCounts', 'occCounts', 'pendingPreview'
        ));
    }

    /**
     * =====================================================================
     * APPROVER: APPROVE OR REJECT A MEDIA ITEM
     * =====================================================================
     * Works directly off the user's generated media (creates/updates the
     * approval record on the fly).
     */
    public function review(Request $request)
    {
        $user = Auth::user();

        if (!$user->isApprover()) {
            abort(403, 'Only approver accounts can review assets.');
        }

        $request->validate([
            'source'        => 'required|in:cgi,occasion',
            'generation_id' => 'required|string',
            'media_url'     => 'required|string',
            'media_type'    => 'required|in:image,video',
            'variant'       => 'nullable|string|max:50',
            'is_branded'    => 'nullable',
            'decision'      => 'required|in:approved,rejected,undo',
            'comment'       => 'nullable|string|max:1000',
        ]);

        // The asset must belong to the user this approver is assigned to.
        if ($request->source === 'occasion') {
            $generation = Occasion::where('id', $request->generation_id)
                ->where('user_id', $user->client_id)
                ->first();
            $product = $generation?->occasion_identity;
        } else {
            $generation = CgiGeneration::where('id', $request->generation_id)
                ->where('user_id', $user->client_id)
                ->first();
            $product = $generation?->product_name;
        }

        if (!$generation) {
            return back()->with('error', 'You can only review assets for your assigned client.');
        }

        $slotKey = [
            'cgi_generation_id' => $generation->id,
            'source'            => $request->source,
            'media_type'        => $request->media_type,
            'variant'           => $request->variant ?: 'merged',
        ];

        $existing = MediaApproval::where($slotKey)->first();

        if ($request->decision === 'undo') {
            if (!$existing || !in_array($existing->status, [MediaApproval::STATUS_APPROVED, MediaApproval::STATUS_REJECTED], true)) {
                return back()->with('error', 'Nothing to undo for this asset.');
            }
            if (!$existing->reviewed_at || $existing->reviewed_at->lt(now()->subMinute())) {
                return back()->with('error', 'Undo window expired. You can only undo within 1 minute of your decision.');
            }

            $existing->update([
                'status'      => MediaApproval::STATUS_PENDING,
                'comment'     => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

            Cache::forget("approver.pending.{$user->id}");

            return back()->with('success', 'Decision undone. The asset is awaiting review again.');
        }

        if ($existing?->status === MediaApproval::STATUS_APPROVED && $request->decision === MediaApproval::STATUS_REJECTED) {
            return back()->with('error', 'Cannot reject an approved asset. Use Undo within 1 minute, then reject if needed.');
        }

        if ($existing?->status === MediaApproval::STATUS_REJECTED && $request->decision === MediaApproval::STATUS_APPROVED) {
            return back()->with('error', 'Cannot approve a rejected asset. Use Undo within 1 minute, then approve if needed.');
        }

        if ($existing && in_array($existing->status, [MediaApproval::STATUS_APPROVED, MediaApproval::STATUS_REJECTED], true)) {
            return back()->with('error', 'This asset was already reviewed. Use Undo within 1 minute to change your decision.');
        }

        MediaApproval::updateOrCreate(
            $slotKey,
            [
                'maker_id'     => $generation->user_id,
                'product_name' => $product,
                'media_url'    => $request->media_url,
                'is_branded'   => filter_var($request->is_branded, FILTER_VALIDATE_BOOLEAN),
                'status'       => $request->decision,
                'comment'      => $request->comment,
                'reviewed_by'  => $user->id,
                'reviewed_at'  => now(),
            ]
        );

        Cache::forget("approver.pending.{$user->id}");
        Cache::forget("dashboard.stats.{$generation->user_id}");

        $msg = $request->decision === MediaApproval::STATUS_APPROVED
            ? 'Asset approved. The user can now publish it.'
            : 'Asset rejected. Your note has been saved for the user.';

        return back()->with('success', $msg);
    }

    /**
     * =====================================================================
     * USER (optional): manually flag a media item for approval.
     * The approver already sees everything, so this just (re)sets a media
     * item back to pending after a rejection.
     * =====================================================================
     */
    public function submit(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts are not subject to the approval workflow.',
            ], 422);
        }

        $request->validate([
            'cgi_generation_id' => 'required|string|exists:cgi_generations,id',
            'media_url'         => 'required|string',
            'media_type'        => 'required|in:image,video',
            'variant'           => 'nullable|string|max:50',
            'is_branded'        => 'nullable|boolean',
        ]);

        $generation = CgiGeneration::where('id', $request->cgi_generation_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$generation) {
            return response()->json(['success' => false, 'message' => 'You can only submit your own assets.'], 403);
        }

        $mediaType = $request->media_type;
        $variant   = $request->variant ?: 'merged';

        if ($variant !== 'merged' || !in_array($mediaType, ['image', 'video'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only merged pictures and merged videos can be submitted for approval.',
            ], 422);
        }

        $mergedField = $mediaType === 'video' ? 'merged_video_url' : 'merged_image_url';
        $mergedKey   = self::normalizeUrl(self::resolveUrl($generation->{$mergedField} ?? ''));
        if ($mergedKey === '' || self::normalizeUrl($request->media_url) !== $mergedKey) {
            return response()->json([
                'success' => false,
                'message' => 'URL must match the directive merged ' . ($mediaType === 'video' ? 'video' : 'picture') . '.',
            ], 422);
        }

        if (!$user->approver()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No approver is assigned to your account yet. Please contact your administrator.',
            ], 422);
        }

        $approval = MediaApproval::updateOrCreate(
            [
                'cgi_generation_id' => $generation->id,
                'source'            => 'cgi',
                'media_type'        => $request->media_type,
                'variant'           => 'merged',
            ],
            [
                'maker_id'     => $user->id,
                'product_name' => $generation->product_name,
                'media_url'    => $request->media_url,
                'is_branded'   => (bool) $request->boolean('is_branded'),
                'status'       => MediaApproval::STATUS_PENDING,
                'comment'      => null,
                'reviewed_by'  => null,
                'reviewed_at'  => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Submitted for client approval.',
            'status'  => $approval->status,
        ]);
    }
}
