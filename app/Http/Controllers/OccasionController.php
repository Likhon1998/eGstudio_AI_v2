<?php

namespace App\Http\Controllers;

use App\Models\Occasion;
use App\Models\OccasionSocialPost;
use App\Models\ProductAsset;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Support\CaptionLanguageOptions;
use App\Support\GalleryAssetPaginator;
use App\Support\OccasionCalendarPresets;
use App\Support\PublicMediaUrl;

class OccasionController extends Controller
{
    private const PROMPT_WEBHOOK_URL = 'https://n8n.egeneration.co/webhook/occasionStudio';

    /**
     * Occasion Studio now runs on the SAME plan as CGI (the "pic plan").
     * Credits are read from / deducted on the user's active UserPackage wallet:
     *   prompt  -> directive_credits
     *   image   -> image_credits
     *   social  -> social_post_credits
     */
    private function activeUserPackage(): ?UserPackage
    {
        return UserPackage::where('user_id', auth()->id())
            ->where('is_active_selection', 'true')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    private function getWallet(): ?object
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return (object) [
                'prompt_credits'         => 9999,
                'image_credits'          => 9999,
                'video_credits'          => 9999,
                'branding_image_credits' => 9999,
                'branding_video_credits' => 9999,
                'social_post_credits'    => 9999,
                'is_admin'               => true,
            ];
        }

        $package = $this->activeUserPackage();

        if (!$package) {
            return null;
        }

        // Map CGI plan columns onto the field names the Occasion views expect.
        return (object) [
            'prompt_credits'         => $package->directive_credits ?? 0,
            'image_credits'          => $package->image_credits ?? 0,
            'video_credits'          => $package->video_credits ?? 0,
            'branding_image_credits' => $package->branding_image_credits ?? 0,
            'branding_video_credits' => $package->branding_video_credits ?? 0,
            'social_post_credits'    => $package->social_post_credits ?? 0,
        ];
    }

    private function walletAllowances(): ?array
    {
        if (auth()->user()->role === 'admin') {
            return null;
        }

        $package = $this->activeUserPackage();

        if (!$package) {
            return null;
        }

        $plan = $package->package;

        return [
            'prompt'         => max($plan->directive_allowance ?? 0, $package->directive_credits ?? 0),
            'image'          => max($plan->image_allowance ?? 0, $package->image_credits ?? 0),
            'video'          => max($plan->video_allowance ?? 0, $package->video_credits ?? 0),
            'branding_image' => max($plan->branding_image_allowance ?? 0, $package->branding_image_credits ?? 0),
            'branding_video' => max($plan->branding_video_allowance ?? 0, $package->branding_video_credits ?? 0),
            'social_post'    => max($plan->social_post_allowance ?? 0, $package->social_post_credits ?? 0),
        ];
    }

    private function deductCredit(string $column): bool
    {
        $package = $this->activeUserPackage();

        if ($package && ($package->{$column} ?? 0) > 0) {
            $package->decrement($column);

            return true;
        }

        return false;
    }

    private function assertBrandingImageCredits(): ?string
    {
        $wallet = $this->getWallet();

        if (! $wallet) {
            return 'No active subscription wallet found.';
        }

        if (isset($wallet->is_admin)) {
            return null;
        }

        $package = $this->activeUserPackage();

        if (! $package?->package || ($package->package->branding_image_allowance ?? 0) <= 0) {
            return 'Your current package does not allow image branding.';
        }

        if (($wallet->branding_image_credits ?? 0) < 1) {
            return 'Out of Image Branding Credits. Please top up.';
        }

        return null;
    }

    private function assertSocialPostCredits(): ?string
    {
        $wallet = $this->getWallet();

        if (! $wallet) {
            return 'No active subscription wallet found.';
        }

        if (isset($wallet->is_admin)) {
            return null;
        }

        if (($wallet->social_post_credits ?? 0) < 1) {
            return 'Out of Social Post Credits. Please top up.';
        }

        return null;
    }

    private function deductBrandingImageCreditForUser(int $userId): bool
    {
        $user = User::find($userId);

        if ($user && $user->role === 'admin') {
            return true;
        }

        $package = UserPackage::where('user_id', $userId)
            ->where('is_active_selection', 'true')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($package && ($package->branding_image_credits ?? 0) > 0) {
            $package->decrement('branding_image_credits');

            return true;
        }

        return false;
    }

    private function deductBrandingImageCredit(): bool
    {
        if (auth()->user()->role === 'admin') {
            return true;
        }

        return $this->deductCredit('branding_image_credits');
    }

    /**
     * Deduct branding image credit once logo'd or merged asset exists (n8n async).
     */
    private function finalizeBrandingCreditsIfReady(Occasion $occasion): Occasion
    {
        $occasion->refresh();

        if (filled($occasion->branded_image_url) && ! $occasion->branding_logo_credit_deducted) {
            if ($this->deductBrandingImageCreditForUser((int) $occasion->user_id)) {
                $occasion->update(['branding_logo_credit_deducted' => true]);
                $occasion->refresh();
            }
        }

        if (filled($occasion->merged_image_url) && ! $occasion->merge_branding_credit_deducted) {
            if ($this->deductBrandingImageCreditForUser((int) $occasion->user_id)) {
                $occasion->update(['merge_branding_credit_deducted' => true]);
                $occasion->refresh();
            }
        }

        return $occasion;
    }

    /**
     * n8n often writes image_url without clearing image_status — sync so the UI can stop polling.
     */
    private function finalizeImageIfReady(Occasion $occasion): Occasion
    {
        $occasion->refresh();
        $updates = [];

        if (filled($occasion->image_url) && ($occasion->image_status ?? '') === 'making') {
            $updates['image_status'] = 'completed';
        }

        if (filled($occasion->merged_image_url) && ($occasion->merge_status ?? '') === 'processing') {
            $updates['merge_status'] = 'completed';
        }

        if ($updates !== []) {
            $occasion->update($updates);
            $occasion->refresh();
        }

        return $occasion;
    }

    private function posterStyleFamilyFor(string $occasionIdentity): string
    {
        $map = config('occasion_presets.posterStyleByOccasion', []);

        return $map[$occasionIdentity] ?? 'modern_celebration';
    }

    private function typographyDirectiveFor(string $occasionIdentity): string
    {
        $family = $this->posterStyleFamilyFor($occasionIdentity);
        $byFamily = config('occasion_presets.typographyByStyleFamily', []);
        $mood = $byFamily[$family] ?? ($byFamily['modern_celebration'] ?? '');

        return trim((string) config('occasion_presets.typographyMatchingBrief', '').' '.$mood);
    }

    private function semanticDirectiveFor(string $occasionIdentity): string
    {
        $details = config('occasion_presets.semanticOccasionDetail', []);

        if (isset($details[$occasionIdentity])) {
            return (string) $details[$occasionIdentity];
        }

        $categories = config('occasion_presets.semanticCategoryByOccasion', []);
        $templates = config('occasion_presets.semanticCategoryTemplates', []);
        $category = $categories[$occasionIdentity] ?? 'modern_celebration';
        $template = $templates[$category] ?? ($templates['modern_celebration'] ?? '');

        return 'Occasion "'.$occasionIdentity.'": '.$template
            .' Every visual layer and reserved text zone must carry specific meaning for '
            .$occasionIdentity.'—no meaningless decoration.';
    }

    private function compositionDirectiveFor(string $occasionIdentity): string
    {
        $typeA = config('occasion_presets.posterTypeA', []);
        $isTypeA = collect($typeA)->contains(
            fn ($name) => strcasecmp((string) $name, $occasionIdentity) === 0
        );

        $rule = $isTypeA
            ? config('occasion_presets.compositionTypeA')
            : config('occasion_presets.compositionTypeB');

        return trim((string) $rule.' '.config('occasion_presets.graphicDesignerBrief', ''));
    }

    private function enrichCustomTextPayload(Occasion $occasion): string
    {
        $base = trim((string) ($occasion->custom_text_payload ?? ''));
        $identity = (string) $occasion->occasion_identity;
        $blocks = [];

        if ($base === '' || ! str_contains($base, 'SEMANTIC POSTER:')) {
            $blocks[] = 'SEMANTIC POSTER: '.config('occasion_presets.semanticPosterFramework');
            $blocks[] = 'SEMANTIC OCCASION: '.$this->semanticDirectiveFor($identity);
        }

        if ($base === '' || ! str_contains($base, 'GRAPHIC DESIGNER BRIEF:')) {
            $blocks[] = 'ASPECT RATIO: '.config('occasion_presets.aspectRatioDirective');
            $blocks[] = 'GRAPHIC DESIGNER BRIEF: '.config('occasion_presets.graphicDesignerBrief');
            $blocks[] = 'TYPOGRAPHY MATCH: '.$this->typographyDirectiveFor($identity);
            $blocks[] = 'COMPOSITION: '.$this->compositionDirectiveFor($identity);
            $blocks[] = 'EXCELLENCE LOCK: '.config('occasion_presets.promptExcellenceLock');
        }

        if ($blocks === []) {
            return $base;
        }

        $enrichment = implode(' ', $blocks);

        return $base === '' ? $enrichment : $base.' '.$enrichment;
    }

    private function buildPromptWebhookPayload(Occasion $occasion): array
    {
        $identity = (string) $occasion->occasion_identity;

        return [
            'occasion_id'            => $occasion->id,
            'target_month'           => $occasion->target_month,
            'target_year'            => $occasion->target_year,
            'occasion_identity'      => $occasion->occasion_identity,
            'visual_direction'       => $occasion->visual_direction,
            'custom_text'            => $occasion->custom_text,
            'custom_text_payload'    => $this->enrichCustomTextPayload($occasion),
            'poster_style'           => 'bengali_social_poster_varied',
            'poster_style_family'    => $this->posterStyleFamilyFor($identity),
            'poster_directive'       => config('occasion_presets.posterDirective'),
            'design_brief'           => config('occasion_presets.graphicDesignerBrief'),
            'typography_directive'   => $this->typographyDirectiveFor($identity),
            'composition_directive'  => $this->compositionDirectiveFor($identity),
            'prompt_excellence_lock' => config('occasion_presets.promptExcellenceLock'),
            'negative_prompt_hint'   => config('occasion_presets.negativePromptBase'),
            'aspect_ratio'              => config('occasion_presets.aspectRatio', '16:9'),
            'aspect_ratio_directive'    => config('occasion_presets.aspectRatioDirective'),
            'semantic_poster_framework' => config('occasion_presets.semanticPosterFramework'),
            'semantic_poster_directive' => $this->semanticDirectiveFor($identity),
        ];
    }

    private function normalizeWebhookPayload(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        return $data;
    }

    private function webhookResponseFailed(mixed $data): bool
    {
        $payload = $this->normalizeWebhookPayload($data);

        if ($payload === []) {
            return false;
        }

        if (array_key_exists('success', $payload) && $payload['success'] === false) {
            return true;
        }

        if (array_key_exists('ok', $payload) && $payload['ok'] === false) {
            return true;
        }

        $status = strtolower((string) ($payload['status'] ?? $payload['state'] ?? ''));

        return in_array($status, ['error', 'failed', 'failure'], true);
    }

    private function extractWebhookError(mixed $data, int $httpStatus = 0): string
    {
        $payload = $this->normalizeWebhookPayload($data);

        $message = $payload['message']
            ?? $payload['error']
            ?? $payload['detail']
            ?? $payload['error_message']
            ?? null;

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        if ($httpStatus > 0) {
            return 'Prompt engine rejected the request (HTTP '.$httpStatus.').';
        }

        return 'Prompt generation failed. Credits were NOT deducted.';
    }

    private function extractPromptFields(mixed $data): array
    {
        $payload = $this->normalizeWebhookPayload($data);

        return [
            'image_prompt'    => $payload['image_prompt'] ?? null,
            'video_prompt'    => $payload['video_prompt'] ?? null,
            'audio_prompt'    => $payload['audio_prompt'] ?? null,
            'negative_prompt' => $payload['negative_prompt'] ?? null,
        ];
    }

    private function promptsPresent(array $prompts): bool
    {
        return filled($prompts['image_prompt'] ?? null);
    }

    private function isPromptPipelineFailed(Occasion $occasion): bool
    {
        return $occasion->status === 'failed' || filled($occasion->prompt_error_message);
    }

    private function webhookPayloadIndicatesFailure(mixed $data, string $message): bool
    {
        if ($this->webhookResponseFailed($data)) {
            return true;
        }

        $generic = 'Prompt generation failed. Credits were NOT deducted.';
        if ($message === $generic) {
            return false;
        }

        $payload = $this->normalizeWebhookPayload($data);
        if ($payload === [] || ! $this->promptsPresent($this->extractPromptFields($data))) {
            if (isset($payload['message']) || isset($payload['error']) || isset($payload['error_message'])) {
                $haystack = strtolower($message);
                foreach (['error', 'failed', 'failure', 'webhook', 'unused respond', 'timeout', 'exception', 'invalid'] as $needle) {
                    if (str_contains($haystack, $needle)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function markPromptFailed(Occasion $occasion, string $message): void
    {
        $updates = [
            'status'               => 'failed',
            'prompt_error_message' => $message,
        ];

        if (($occasion->image_status ?? '') === 'making') {
            $updates['image_status'] = 'pending';
        }
        if (($occasion->video_status ?? '') === 'making') {
            $updates['video_status'] = 'pending';
        }
        if (($occasion->merge_status ?? '') === 'processing') {
            $updates['merge_status'] = 'pending';
        }

        $occasion->update($updates);
    }

    private function deductPromptCreditOnce(Occasion $occasion): bool
    {
        $occasion->refresh();

        if ($occasion->prompt_credit_deducted) {
            return true;
        }

        $user = User::find($occasion->user_id);

        if ($user && $user->role === 'admin') {
            $occasion->update(['prompt_credit_deducted' => true]);

            return true;
        }

        $package = UserPackage::where('user_id', $occasion->user_id)
            ->where('is_active_selection', 'true')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($package && ($package->directive_credits ?? 0) > 0) {
            $package->decrement('directive_credits');
            $occasion->update(['prompt_credit_deducted' => true]);

            return true;
        }

        $this->markPromptFailed($occasion, 'Prompts were generated but your wallet has no prompt credits left.');

        return false;
    }

    /**
     * Fire occasionStudio webhook after the HTTP response (fast redirect / JSON).
     */
    private function queuePromptWebhook(Occasion $occasion): void
    {
        $occasionId = $occasion->id;

        dispatch(function () use ($occasionId) {
            app(self::class)->runPromptWebhookPipeline($occasionId);
        })->afterResponse();
    }

    public function runPromptWebhookPipeline(string $occasionId): void
    {
        $occasion = Occasion::query()->find($occasionId);

        if (! $occasion || $occasion->status !== 'pending_prompt') {
            return;
        }

        $this->applyPromptWebhookResult($occasion, $this->dispatchPromptWebhook($occasion));
    }

    /**
     * When n8n writes prompts asynchronously, deduct credit once prompts exist.
     */
    private function finalizePromptIfReady(Occasion $occasion): Occasion
    {
        $occasion->refresh();

        if ($occasion->status === 'failed' || $occasion->prompt_credit_deducted) {
            return $occasion;
        }

        if (blank($occasion->image_prompt)) {
            return $occasion;
        }

        $this->deductPromptCreditOnce($occasion);

        $occasion->update([
            'prompt_error_message' => null,
            'status'               => $occasion->status === 'pending_prompt' ? 'ready' : $occasion->status,
        ]);

        return $occasion->fresh();
    }

    /**
     * @return array{ok: bool, message?: string, sync?: bool, data?: mixed}
     */
    private function dispatchPromptWebhook(Occasion $occasion): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(120)
                ->post(self::PROMPT_WEBHOOK_URL, $this->buildPromptWebhookPayload($occasion));

            $data = $response->json();

            if (! $response->successful()) {
                return [
                    'ok'      => false,
                    'message' => $this->extractWebhookError($data, $response->status()),
                ];
            }

            if ($this->webhookResponseFailed($data)) {
                return [
                    'ok'      => false,
                    'message' => $this->extractWebhookError($data),
                ];
            }

            $prompts = $this->extractPromptFields($data);

            if ($this->promptsPresent($prompts)) {
                return [
                    'ok'   => true,
                    'sync' => true,
                    'data' => $prompts,
                ];
            }

            $message = $this->extractWebhookError($data);
            if ($this->webhookPayloadIndicatesFailure($data, $message)) {
                return [
                    'ok'      => false,
                    'message' => $message,
                ];
            }

            return [
                'ok'      => true,
                'sync'    => false,
                'message' => $message ?: 'AI is writing your campaign DNA.',
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Occasion prompt webhook timeout', ['occasion_id' => $occasion->id, 'error' => $e->getMessage()]);

            return [
                'ok'      => false,
                'message' => 'Prompt engine timed out. Service was not completed — credits were NOT deducted.',
            ];
        } catch (\Throwable $e) {
            Log::error('Occasion prompt webhook failed', ['occasion_id' => $occasion->id, 'error' => $e->getMessage()]);

            return [
                'ok'      => false,
                'message' => 'Could not reach the prompt engine. Credits were NOT deducted.',
            ];
        }
    }

    private function applyPromptWebhookResult(Occasion $occasion, array $result): Occasion
    {
        if (! ($result['ok'] ?? false)) {
            $this->markPromptFailed($occasion, $result['message'] ?? 'Prompt generation failed.');

            return $occasion->fresh();
        }

        if ($result['sync'] ?? false) {
            $prompts = $result['data'] ?? [];
            $occasion->update(array_merge($prompts, [
                'status'               => 'ready',
                'prompt_error_message' => null,
            ]));
            $this->deductPromptCreditOnce($occasion);

            return $occasion->fresh();
        }

        $occasion->update([
            'status'               => 'pending_prompt',
            'prompt_error_message' => null,
        ]);

        return $occasion->fresh();
    }

    private function authorizeOccasion(Occasion $occasion): void
    {
        if (auth()->user()->role !== 'admin' && (int) $occasion->user_id !== (int) auth()->id()) {
            abort(403);
        }
    }

    private function assertPromptCreditsAvailable(): ?string
    {
        $wallet = $this->getWallet();

        if (! $wallet) {
            return 'No active subscription wallet found.';
        }

        if (! isset($wallet->is_admin) && $wallet->prompt_credits <= 0) {
            return 'Insufficient Prompt Credits. Please upgrade your plan.';
        }

        return null;
    }

    public function index()
    {
        $wallet = $this->getWallet();

        // Admins can see all campaigns; Users only see their own.
        if (auth()->user()->role === 'admin') {
            $occasions = Occasion::latest()->get();
        } else {
            $occasions = auth()->user()->occasions()->latest()->get();
        }

        $occasions = $occasions->map(function (Occasion $occasion) {
            if ($this->isPromptPipelineFailed($occasion)) {
                if (filled($occasion->prompt_error_message) && $occasion->status !== 'failed') {
                    Occasion::whereKey($occasion->id)->update(['status' => 'failed']);
                    $occasion->status = 'failed';
                }

                return $occasion;
            }

            if (! $occasion->prompt_credit_deducted && filled($occasion->image_prompt)) {
                $occasion = $this->finalizePromptIfReady($occasion);
            }

            return $this->finalizeImageIfReady(
                $this->finalizeBrandingCreditsIfReady($occasion)
            );
        });
        
        // Auto-refresh index every 5s while n8n writes DNA or renders assets
        $hasPending = $occasions->contains(function (Occasion $occasion) {
            if ($this->isPromptPipelineFailed($occasion)) {
                return false;
            }
            if ($occasion->status === 'pending_prompt') {
                return true;
            }
            if (($occasion->image_status ?? '') === 'making' && blank($occasion->image_url)) {
                return true;
            }
            if (($occasion->merge_status ?? '') === 'processing') {
                return true;
            }
            // Only poll while DNA is still empty and status is not yet ready/failed
            if (
                blank($occasion->image_prompt)
                && ! in_array($occasion->status, ['ready', 'failed'], true)
                && $occasion->status === 'pending_prompt'
            ) {
                return true;
            }

            return false;
        });

        $walletAllowances = $this->walletAllowances();

        $templateAssets = ProductAsset::where('user_id', auth()->id())
            ->templates()
            ->latest()
            ->get();

        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        [$approvalMap, $requiresApproval] = ApprovalController::buildMakerApprovalContext($user);

        $approvalHistory = $isAdmin
            ? ApprovalController::emptyStudioApprovalHistory()
            : ApprovalController::studioApprovalHistory('occasion', (int) $user->id);

        $socialPostsQuery = OccasionSocialPost::with('occasion')
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $user->id));

        $socialPosts = (clone $socialPostsQuery)->latest()->get();

        $postHistoryStats = [
            'total'     => $socialPosts->count(),
            'published' => $socialPosts->where('status', 'published')->count(),
            'scheduled' => $socialPosts->where('status', 'scheduled')->count(),
            'pending'   => $socialPosts->where('status', 'pending')->count(),
            'failed'    => $socialPosts->whereIn('status', ['failed', 'n8n_rejected'])->count(),
        ];

        $captionLanguages = CaptionLanguageOptions::all();

        return view('occasions.index', compact(
            'occasions', 'wallet', 'walletAllowances', 'hasPending', 'templateAssets',
            'approvalMap', 'requiresApproval', 'approvalHistory',
            'socialPosts', 'postHistoryStats', 'captionLanguages'
        ));
    }

    // =======================================================
    // 2. LOAD THE CREATE FORM (WITH REGENERATE LOGIC)
    // =======================================================
    public function create(Request $request)
    {
        $wallet = $this->getWallet();

        // If user clicked 'Regenerate', load the old occasion and flash it to the form
        if ($request->has('duplicate')) {
            $duplicate = Occasion::find($request->duplicate);
            if ($duplicate) {
                // Flash the data so the old() helper in blade picks it up
                $request->session()->flashInput([
                    'target_month'      => $duplicate->target_month,
                    'target_year'       => $duplicate->target_year,
                    'occasion_identity' => $duplicate->occasion_identity,
                    'visual_direction'  => $duplicate->visual_direction,
                    'custom_text'       => $duplicate->custom_text,
                ]);
            }
        }

        $calendarPresets = OccasionCalendarPresets::maps();

        return view('occasions.create', [
            'wallet'          => $wallet,
            'occasionsMap'    => $calendarPresets['occasionsMap'],
            'masterFestivals' => $calendarPresets['masterFestivals'],
        ]);
    }

    // =======================================================
    // 3. GENERATE CAMPAIGN DNA (PROMPTS) -> SEND TO N8N
    // =======================================================
    public function store(Request $request)
    {
        if ($message = $this->assertPromptCreditsAvailable()) {
            return back()->withErrors(['error' => $message]);
        }

        $validated = $request->validate([
            'target_month'        => 'required|integer',
            'target_year'         => 'required|integer',
            'occasion_identity'   => 'required|string|max:255',
            'visual_direction'    => 'required|string',
            'custom_text'         => 'nullable|string',
            'custom_text_payload' => 'nullable|string',
        ]);

        $occasion = auth()->user()->occasions()->create([
            'target_month'        => $validated['target_month'],
            'target_year'         => $validated['target_year'],
            'occasion_identity'   => $validated['occasion_identity'],
            'visual_direction'    => $validated['visual_direction'],
            'custom_text'         => $validated['custom_text'],
            'custom_text_payload' => $validated['custom_text_payload'],
            'status'              => 'pending_prompt',
            'prompt_credit_deducted' => false,
        ]);

        $this->queuePromptWebhook($occasion);

        return redirect()
            ->route('occasions.index')
            ->with('success', 'Occasion pipeline started! AI is writing your DNA.');
    }

    public function retryPrompt(Occasion $occasion)
    {
        $this->authorizeOccasion($occasion);

        if ($message = $this->assertPromptCreditsAvailable()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $occasion->update([
            'status'                 => 'pending_prompt',
            'prompt_error_message'   => null,
            'prompt_credit_deducted' => false,
            'image_prompt'           => null,
            'video_prompt'           => null,
            'audio_prompt'           => null,
            'negative_prompt'        => null,
        ]);

        $this->queuePromptWebhook($occasion->fresh());

        return response()->json([
            'success'    => true,
            'status'     => 'pending_prompt',
            'has_prompts' => false,
            'message'    => 'Re-render started. AI is writing your DNA.',
        ]);
    }

    public function promptStatus(Occasion $occasion)
    {
        $this->authorizeOccasion($occasion);

        if ($this->isPromptPipelineFailed($occasion)) {
            $occasion->refresh();
        } else {
            $occasion = $this->finalizePromptIfReady($occasion);
        }

        return response()->json([
            'status'                 => $occasion->status,
            'prompt_error_message'   => $occasion->prompt_error_message,
            'has_prompts'            => filled($occasion->image_prompt),
            'image_prompt'           => $occasion->image_prompt,
            'prompt_credit_deducted' => (bool) $occasion->prompt_credit_deducted,
        ]);
    }

    public function imageStatus(Occasion $occasion)
    {
        $this->authorizeOccasion($occasion);

        $occasion = $this->finalizeImageIfReady($occasion);
        $occasion = $this->finalizeBrandingCreditsIfReady($occasion);

        $imageStatus = filled($occasion->image_url)
            ? 'completed'
            : ($occasion->image_status ?? 'pending');

        return response()->json([
            'image_status'        => $imageStatus,
            'image_url'           => PublicMediaUrl::forMedia($occasion->image_url),
            'branded_image_url'   => PublicMediaUrl::forMedia($occasion->branded_image_url),
            'merged_image_url'    => PublicMediaUrl::forMedia($occasion->merged_image_url),
            'merge_status'        => filled($occasion->merged_image_url)
                ? 'completed'
                : ($occasion->merge_status ?? 'pending'),
        ]);
    }

    // =======================================================
    // LIVE PROMPT EDITING
    // =======================================================
    public function updatePrompts(Request $request, $id)
    {
        // Ensure user owns the occasion
        $query = Occasion::where('id', $id);
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }
        $occasion = $query->firstOrFail();

        $occasion->image_prompt = $request->input('image_prompt');
        $occasion->video_prompt = $request->input('video_prompt');
        $occasion->audio_prompt = $request->input('audio_prompt');
        $occasion->negative_prompt = $request->input('negative_prompt');

        if ($occasion->save()) {
            $occasion = $this->finalizePromptIfReady($occasion);

            return response()->json([
                'success' => true,
                'image_prompt' => $occasion->image_prompt,
                'video_prompt' => $occasion->video_prompt,
                'audio_prompt' => $occasion->audio_prompt,
                'status' => $occasion->status,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Database save failed.'], 500);
    }

    // =======================================================
    // 4. MAKE PICTURE (AJAX)
    // =======================================================
    public function makePicture(Occasion $occasion)
    {
        // Ensure user owns the occasion (Admins bypass this)
        if (auth()->user()->role !== 'admin' && $occasion->user_id !== auth()->id()) {
            abort(403);
        }

        $wallet = $this->getWallet();

        if (in_array($occasion->status, ['failed', 'pending_prompt'], true) || blank($occasion->image_prompt)) {
            return response()->json(['success' => false, 'message' => 'Campaign DNA is not ready. Fix or re-render prompts first.']);
        }

        if (!$wallet || (!isset($wallet->is_admin) && $wallet->image_credits <= 0)) {
            return response()->json(['success' => false, 'message' => 'Insufficient Image Credits']);
        }

        if (!isset($wallet->is_admin) && !$this->deductCredit('image_credits')) {
            return response()->json(['success' => false, 'message' => 'Insufficient Image Credits']);
        }

        $occasion->update(['image_status' => 'making']);

        // CONNECTED TO YOUR NEW WEBHOOK
        $identity = (string) $occasion->occasion_identity;

        Http::post('https://n8n.egeneration.co/webhook/eGStudio_occasion_MakePicture', [
            'occasion_id'            => $occasion->id,
            'prompt'                 => $occasion->image_prompt,
            'seed'                   => rand(100000, 999999),
            'poster_style'           => 'bengali_social_poster_varied',
            'poster_style_family'    => $this->posterStyleFamilyFor($identity),
            'poster_directive'       => config('occasion_presets.posterDirective'),
            'design_brief'           => config('occasion_presets.graphicDesignerBrief'),
            'typography_directive'   => $this->typographyDirectiveFor($identity),
            'composition_directive'  => $this->compositionDirectiveFor($identity),
            'negative_prompt_hint'   => config('occasion_presets.negativePromptBase'),
            'aspect_ratio'              => config('occasion_presets.aspectRatio', '16:9'),
            'aspectRatio'               => config('occasion_presets.aspectRatio', '16:9'),
            'aspect_ratio_directive'    => config('occasion_presets.aspectRatioDirective'),
            'semantic_poster_framework' => config('occasion_presets.semanticPosterFramework'),
            'semantic_poster_directive' => $this->semanticDirectiveFor($identity),
        ]);

        return response()->json(['success' => true]);
    }

   
    // =======================================================
    // NEW: ADD BRANDING LOGO PIPELINE (WORKS LOCAL & PROD)
    // =======================================================
    public function addBrandingLogo(Request $request, Occasion $occasion)
    {
        if (auth()->user()->role !== 'admin' && $occasion->user_id !== auth()->id()) {
            abort(403);
        }

        if ($message = $this->assertBrandingImageCredits()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        $request->validate([
            'logo'      => 'required|image|mimes:png,jpg,jpeg|max:5120',
            'placement' => 'required|string',
        ]);

        if (! $occasion->image_url) {
            return response()->json(['success' => false, 'message' => 'No original image exists to brand.'], 400);
        }

        $logoFile = $request->file('logo');
        $logoFile->store('logos', 'public');

        $safeImageUrl = str_starts_with($occasion->image_url, 'http')
            ? $occasion->image_url
            : asset('storage/'.$occasion->image_url);

        try {
            $response = Http::withoutVerifying()
                ->timeout(120)
                ->attach('logo', file_get_contents($logoFile->getRealPath()), $logoFile->getClientOriginalName())
                ->post('https://n8n.egeneration.co/webhook/eGStudio_ApplyBranding_image_occasional', [
                    'id'        => $occasion->id,
                    'image_url' => $safeImageUrl,
                    'placement' => $request->placement,
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => $response->json()['message'] ?? 'Branding pipeline started. 1 Image Branding Credit will be deducted when your logo\'d image is ready.',
                ]);
            }

            $n8nError = $response->json()['message'] ?? 'n8n processing failed (HTTP '.$response->status().')';

            return response()->json(['success' => false, 'message' => 'Branding failed: '.$n8nError], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Branding engine offline. Credits were NOT deducted.'], 500);
        }
    }

    // =======================================================
    // MERGE WITH CUSTOM TEMPLATE (n8n merge_pic_occasional)
    // =======================================================
    public function mergeTemplate(Request $request)
    {
        $request->validate([
            'id'                => 'required|exists:occasions,id',
            'template_asset_id' => 'nullable|integer|exists:product_assets,id',
            'template_url'      => 'nullable|url',
            'template_image'    => 'nullable|image|max:5120',
        ]);

        if (! $request->hasFile('template_image') && ! $request->filled('template_asset_id')) {
            return response()->json(['success' => false, 'message' => 'A template image or library template is required.'], 422);
        }

        $query = Occasion::where('id', $request->id);

        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $occasion = $query->firstOrFail();

        if (! $occasion->image_url) {
            return response()->json(['success' => false, 'message' => 'No image exists to merge.'], 400);
        }

        if ($message = $this->assertBrandingImageCredits()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        $occasion->update(['merge_status' => 'processing']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/merge_pic_occasional';

        try {
            $client = Http::withoutVerifying()->timeout(120);

            $mainImagePath = storage_path('app/public/' . $occasion->image_url);
            if (file_exists($mainImagePath)) {
                $client = $client->attach('image', file_get_contents($mainImagePath), basename($occasion->image_url));
            }

            $templateUrl = $request->template_url;

            if ($request->hasFile('template_image')) {
                $file = $request->file('template_image');
                $client = $client->attach('template_image', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
            } elseif ($request->filled('template_asset_id')) {
                $templateAsset = ProductAsset::where('user_id', auth()->id())
                    ->templates()
                    ->findOrFail($request->template_asset_id);

                $templatePath = storage_path('app/public/' . $templateAsset->file_path);
                if (file_exists($templatePath)) {
                    $client = $client->attach(
                        'template_image',
                        file_get_contents($templatePath),
                        basename($templateAsset->file_path)
                    );
                }

                $templateUrl = $templateAsset->public_url;
            }

            $response = $client->post($webhookUrl, [
                'id'            => $occasion->id,
                'occasion_id'   => $occasion->id,
                'image_url'     => str_starts_with($occasion->image_url, 'http') ? $occasion->image_url : asset('storage/' . $occasion->image_url),
                'template_url'  => $templateUrl,
                'executionMode' => 'production',
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Merge pipeline started! 1 Image Branding Credit will be deducted when your merged image is ready.',
                ]);
            }

            $occasion->update(['merge_status' => 'failed']);

            return response()->json(['success' => false, 'message' => 'n8n rejected merge request. Credits were NOT deducted.'], 500);
        } catch (\Exception $e) {
            $occasion->update(['merge_status' => 'failed']);

            return response()->json(['success' => false, 'message' => 'Merge engine connection failed. Credits were NOT deducted.'], 500);
        }
    }

    // =======================================================
    // 5. MAKE VIDEO (AJAX) - DISABLED TEMPORARILY
    // =======================================================
    public function makeVideo(Occasion $occasion)
    {
        // ... (Keep the code just in case you want to turn it back on later)
        return response()->json(['success' => false, 'message' => 'Video generation is temporarily disabled.']);
    }

    // =======================================================
    // 6. DELETE CAMPAIGN
    // =======================================================
    public function destroy(Occasion $occasion)
    {
        // Ensure user owns the occasion (Admins bypass this)
        if (auth()->user()->role !== 'admin' && $occasion->user_id !== auth()->id()) {
            abort(403);
        }

        $occasion->delete();
        
        return back()->with('success', 'Campaign successfully purged.');
    }

    public function gallery(Request $request)
    {
        $wallet = $this->getWallet();

        // Fetch occasions that actually have an image generated
        $query = Occasion::whereNotNull('image_url');
        
        // Normal users only see their own gallery; Admins see everything
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }

        // Apply Tab Filters
        $tab = $request->query('tab', 'all');
        if ($tab === 'branded') {
            $query->whereNotNull('branded_image_url');
        } elseif ($tab === 'non_branded') {
            $query->whereNull('branded_image_url');
        } elseif ($tab === 'merge') {
            $query->whereNotNull('merged_image_url');
        }

        $assets = GalleryAssetPaginator::paginateOccasionMedia($query->latest(), $tab);

        return view('occasions.gallery', compact('assets', 'wallet', 'tab'));
    }

    // =======================================================
    // AI CAPTION GENERATOR
    // =======================================================
    public function generateCaption(Request $request, $id)
    {
        $occasion = Occasion::where('id', $id)->firstOrFail();
        if (auth()->user()->role !== 'admin' && $occasion->user_id !== auth()->id()) { abort(403); }

        $languageValues = array_column(config('caption_language_options.languages', []), 'value');

        $request->validate([
            'image_url'        => 'required|string',
            'caption_language' => 'required|in:' . implode(',', $languageValues ?: ['bangla', 'english']),
        ]);

        $langOption = collect(config('caption_language_options.languages', []))
            ->firstWhere('value', $request->caption_language);

        try {
            $response = Http::withoutVerifying()->timeout(120)->post('https://n8n.egeneration.co/webhook/caption_generation_occasional', [
                'identity'               => $occasion->occasion_identity,
                'image_prompt'           => $occasion->image_prompt,
                'custom_text'            => $occasion->custom_text,
                'visual_direction'       => $occasion->visual_direction,
                'image_url'              => $request->image_url,
                'caption_language'        => $request->caption_language,
                'caption_language_label'  => $langOption['label'] ?? $request->caption_language,
                'caption_language_native' => $langOption['native'] ?? ($langOption['label'] ?? $request->caption_language),
                'executionMode'          => 'production',
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $caption = '';
                
                // Safely extract the text no matter how n8n formatted it
                if (isset($responseData[0]['caption'])) {
                    $caption = $responseData[0]['caption']; // n8n array format
                } elseif (isset($responseData['caption'])) {
                    $caption = $responseData['caption'];    // n8n object format
                } elseif (isset($responseData[0]['text'])) {
                    $caption = $responseData[0]['text'];
                } elseif (isset($responseData['text'])) {
                    $caption = $responseData['text'];
                } else {
                    $caption = $response->body();           // Fallback to raw body
                }

                // Clean up any extra quotes or weird spacing n8n might add
                $caption = trim($caption, "\"' \t\n\r\0\x0B");

                return response()->json(['success' => true, 'caption' => $caption]);
            }
            
            return response()->json(['success' => false, 'message' => 'AI returned an error code.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Caption engine offline or timed out.']);
        }
    }

    public function publishToSocial(Request $request, $id)
    {
        $user = auth()->user();
        $occasion = Occasion::where('id', $id)->firstOrFail();

        if ($user->role !== 'admin' && $occasion->user_id !== $user->id) {
            abort(403);
        }

        if ($message = $this->assertSocialPostCredits()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        $request->validate([
            'caption'      => 'required|string',
            'media_url'    => 'required|string',
            'media_source' => 'required|in:branded,merged',
            'scheduled_at' => 'nullable|date',
        ]);

        $mediaSource = $request->media_source;
        $isBranded = $mediaSource === 'branded';
        $isMerged = $mediaSource === 'merged';

        if ($isBranded && empty($occasion->branded_image_url)) {
            return response()->json(['success' => false, 'message' => 'No logo\'d image available for this campaign.'], 422);
        }
        if ($isMerged && empty($occasion->merged_image_url)) {
            return response()->json(['success' => false, 'message' => 'No merged image available for this campaign.'], 422);
        }

        $approvalBlock = ApprovalController::publishBlockedReason(
            $user,
            'occasion',
            $occasion->id,
            $request->media_url,
            $occasion
        );
        if ($approvalBlock) {
            return response()->json(['success' => false, 'message' => $approvalBlock], 403);
        }

        $publicMediaUrl = str_starts_with($request->media_url, 'http') ? $request->media_url : asset('storage/' . $request->media_url);
        $isScheduled = !empty($request->scheduled_at);

        // CREATE TRACKING RECORD
        $socialPost = OccasionSocialPost::create([
            'occasion_id'  => $occasion->id,
            'user_id'      => $user->id,
            'platform'     => 'facebook',
            'media_url'    => $publicMediaUrl,
            'is_branded'   => $isBranded ? 1 : 0,
            'caption'      => $request->caption,
            'status'       => $isScheduled ? 'scheduled' : 'pending',
            'scheduled_at' => $isScheduled ? \Carbon\Carbon::parse($request->scheduled_at)->setTimezone('UTC') : null,
        ]);

        // Send to n8n Webhook
        try {
            $response = Http::withoutVerifying()->timeout(120)->post('https://n8n.egeneration.co/webhook/publishToSocial_occasional', [
                'post_id'       => $socialPost->id,
                'occasion_id'   => $occasion->id,
                'caption'       => $socialPost->caption,
                'media_url'     => $socialPost->media_url,
                'media_type'    => 'image',
                'media_source'  => $mediaSource,
                'is_branded'    => $isBranded,
                'is_merged'     => $isMerged,
                'identity'      => $occasion->occasion_identity,
                'scheduled_at'  => $socialPost->scheduled_at,
                'user_id'       => $user->id,
                'executionMode' => 'production',
            ]);

            if ($response->successful()) {
                if ($user->role !== 'admin') {
                    $this->deductCredit('social_post_credits');
                }

                $socialPost->update(['status' => $isScheduled ? 'scheduled' : 'published']);
                $msg = $isScheduled
                    ? 'Post scheduled! 1 Social Post Credit deducted.'
                    : 'Published to Facebook! 1 Social Post Credit deducted.';

                $occasion->update(['custom_text' => $request->caption]);

                return response()->json(['success' => true, 'message' => $msg]);
            }

            $socialPost->update(['status' => 'n8n_rejected']);

            return response()->json(['success' => false, 'message' => 'Publishing failed via n8n. Credits were NOT deducted.'], 500);
        } catch (\Exception $e) {
            $socialPost->update(['status' => 'failed']);

            return response()->json(['success' => false, 'message' => 'Connection to n8n failed. Credits were NOT deducted.'], 500);
        }
    }

    public function destroyPostHistory($id)
    {
        $user = auth()->user();
        $post = OccasionSocialPost::findOrFail($id);

        if ($user->role !== 'admin' && (int) $post->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete this post.'], 403);
        }

        $post->delete();

        return response()->json(['success' => true, 'message' => 'Post removed from history.']);
    }

    // =======================================================
    // AI AUTO-FILL STUDIO FORM
    // =======================================================
    public function autoFill(Request $request)
    {
        // 1. Validate the expanded payload (Now accepting Theme & Text Arrays)
        $request->validate([
            'occasion_name'       => 'required|string|max:255',
            'target_month_number' => 'nullable|integer',
            'target_month_name'   => 'nullable|string',
            'target_year'         => 'nullable|integer',
            'is_registered'       => 'nullable|boolean',
            'ai_instruction'      => 'nullable|string',
            'existing_themes'     => 'nullable|array', // Accepts the 3 themes
            'existing_texts'      => 'nullable|array'  // Accepts the text options
        ]);

        try {
            // 2. Forward the ENTIRE validated package directly to n8n
            $response = Http::withoutVerifying()->timeout(60)->post(
                'https://n8n.egeneration.co/webhook/autofill_occasional_studio', 
                $request->all()
            );

            if ($response->successful()) {
                $data = $response->json();
                
                // 3. Catch array or object formats safely
                $visual = $data[0]['visual_direction'] ?? $data['visual_direction'] ?? '';
                $text   = $data[0]['marketing_text'] ?? $data['marketing_text'] ?? '';

                if (empty($visual)) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'AI failed to generate a visual direction.'
                    ]);
                }

                // 4. Return the AI results back to the Blade file
                return response()->json([
                    'success'          => true,
                    'visual_direction' => $visual,
                    'marketing_text'   => $text
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'n8n Webhook returned an error.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI Auto-Fill Engine is offline or timed out.']);
        }
    }
}