<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CgiGeneration;
use App\Models\ProductAsset; 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\CgiSocialPost;
use App\Models\Logo;
use App\Support\CaptionLanguageOptions;
use App\Support\CgiBusinessPresets;
use App\Support\GalleryAssetPaginator;

/**
 * Class CgiGenerationController
 * * * ARCHITECTURE OVERVIEW:
 * Handles the complete lifecycle of CGI Neural Assets including:
 * - Initial DNA / Prompt generation via n8n
 * - High-fidelity Image Rendering
 * - Video Synthesis 
 * - Corporate Branding Overlays (Image & Video via inline modal)
 * - Social Media Broadcasting
 * * * SECURITY & BILLING INTEGRATION:
 * Fully integrated with the SaaS Credit/Wallet System. 
 * Bypasses legacy Spatie permissions for asset generation in favor of real-time 
 * database wallet deductions (`branding_image_credits`, `video_credits`, etc.)
 */
class CgiGenerationController extends Controller
{
    /**
     * =========================================================================
     * CORE PIPELINE: VIEW DIRECTIVES (INDEX)
     * =========================================================================
     * Retrieves the primary workspace for the user.
     * Admins can view all generated assets across the platform.
     * Regular users are scoped strictly to their own generation history.
     */
    public function index()
    {
        // 1. The Security Gate: Ensure the user has the exact clearance required to view the page.
        if (!auth()->user()->can('view_cgi_index')) {
            abort(403, 'SYSTEM ALERT: You lack clearance to access the Directive Studio.');
        }

        $user    = auth()->user();
        $isAdmin = $user->isAdmin();

        // 2. Intelligent Data Scoping: Admins get global view, Users get scoped view.
        $perPage = 10;

        if ($isAdmin) {
            $generations = CgiGeneration::latest()->paginate($perPage)->withQueryString();
        } else {
            $generations = CgiGeneration::where('user_id', Auth::id())
                ->latest()
                ->paginate($perPage)
                ->withQueryString();
        }

        $templateAssets = ProductAsset::where('user_id', auth()->id())
            ->templates()
            ->latest()
            ->get();

        $userLogos = $isAdmin
            ? Logo::latest()->get()
            : Logo::where('user_id', $user->id)->latest()->get();

        // Approval context so the studio can show approve/reject status + the
        // approver's note, and lock publishing of unapproved merged assets.
        [$approvalMap, $requiresApproval] = $this->buildApprovalContext($user);

        $approvalHistory = $isAdmin
            ? ApprovalController::emptyStudioApprovalHistory()
            : ApprovalController::studioApprovalHistory('cgi', (int) $user->id);

        $captionLanguages = CaptionLanguageOptions::all();

        return view('cgi.index', compact(
            'generations', 'templateAssets', 'userLogos', 'approvalMap', 'requiresApproval', 'approvalHistory', 'captionLanguages'
        ));
    }

    /**
     * =========================================================================
     * CORE PIPELINE: CREATE NEW DIRECTIVE (VIEW)
     * =========================================================================
     * Renders the input form for initiating a new CGI generation sequence.
     * Pre-loads the user's historical asset library for rapid selection.
     */
    public function create()
    {
        // Fetch historical assets for the asset library modal
        $productAssets = ProductAsset::where('user_id', auth()->id())
            ->products()
            ->latest()
            ->get();

        return view('cgi.create', [
            'productAssets' => $productAssets,
            'businessPresets' => CgiBusinessPresets::all(),
        ]);
    }

    /**
     * =========================================================================
     * CORE PIPELINE: INITIALIZE DIRECTIVE (STORE)
     * =========================================================================
     * Handles the initial product upload, deducts 1 Directive Prompt Credit,
     * and triggers the n8n webhook to generate the DNA (prompts).
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;

        // ---------------------------------------------------------
        // 1. SAAS GATEKEEPER & CREDIT AUTHORIZATION
        // ---------------------------------------------------------
        // Verify active subscription and deduct credits if applicable.
        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('is_active_selection', 'true') 
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->first();

            if (!$activeWallet) {
                return redirect()->route('cgi.index')
                    ->with('error', 'You have no active package. Please activate or purchase a plan.');
            }

            if ($activeWallet->directive_credits < 1) {
                return redirect()->route('cgi.index')
                    ->with('error', 'Insufficient Prompt Credits in your active wallet. Please refill.');
            }
        }

        // ---------------------------------------------------------
        // 2. VALIDATION LAYER
        // ---------------------------------------------------------
        // Using manual Validator to intercept and return beautiful frontend Toast errors.
        $validator = Validator::make($request->all(), [
            'business_type'    => 'required|string|in:' . implode(',', CgiBusinessPresets::keys()),
            'product_name'     => 'required|string|max:255',
            'product_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'marketing_angle'  => 'required|string|max:255',
            'visual_prop'      => 'required|string|max:255',
            'atmosphere'       => 'required|string|max:255',
            'camera_motion'    => 'required|string|max:255',
            'composition'      => 'required|string|max:255',
            'lighting_style'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Form Error: ' . $validator->errors()->first());
        }

        $recordId = (string) Str::uuid();

        // ---------------------------------------------------------
        // 3. SECURE FILE & ASSET HANDLING
        // ---------------------------------------------------------
        // Resolve the image path whether it's a fresh upload or a library selection.
        $imagePath = null;
        $localFilePath = null; 
        $fileName = null;

        if ($request->hasFile('product_image')) {
            // Process fresh upload
            $imagePath = $request->file('product_image')->store('products', 'public');
            $localFilePath = storage_path('app/public/' . $imagePath);
            $fileName = $request->file('product_image')->getClientOriginalName();
            
            // Save to Asset Library for future use
            ProductAsset::create([
                'user_id' => $user->id,
                'asset_type' => ProductAsset::TYPE_PRODUCT,
                'name' => pathinfo($fileName, PATHINFO_FILENAME),
                'file_path' => $imagePath,
            ]);
            
        } elseif ($request->filled('selected_asset_path')) {
            // Process internal asset selection
            $imagePath = $request->input('selected_asset_path');
            $localFilePath = storage_path('app/public/' . $imagePath);
            $fileName = basename($imagePath);
            
        } elseif ($request->filled('previous_image_url')) {
            // Process external URL selection (fallback)
            $fullUrl = $request->input('previous_image_url');
            $parsedUrl = parse_url($fullUrl, PHP_URL_PATH); 
            $pathParts = explode('storage/', $parsedUrl);
            $imagePath = isset($pathParts[1]) ? $pathParts[1] : basename($parsedUrl);
            
            $localFilePath = storage_path('app/public/' . $imagePath);
            $fileName = basename($imagePath);
            
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'You must upload a new product image or select one from your library.');
        }

        // CRITICAL SAFETY CHECK: Abort pipeline if physical file cannot be located.
        if (!$localFilePath || !file_exists($localFilePath)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'System Error: Source image file could not be located on the server. Pipeline aborted.');
        }

        // ---------------------------------------------------------
        // 4. DATABASE INITIALIZATION
        // ---------------------------------------------------------
        // Create the core tracking record.
        CgiGeneration::create([
            'id'               => $recordId,
            'user_id'          => Auth::id(),
            'product_name'     => $request->product_name,
            'business_type'    => $request->business_type,
            'product_image'    => $imagePath,
            'marketing_angle'  => $request->marketing_angle,
            'visual_prop'      => $request->visual_prop,
            'atmosphere'       => $request->atmosphere,
            'camera_motion'    => $request->camera_motion,
            'composition'      => $request->composition,
            'lighting_style'   => $request->lighting_style,
            'status'           => 'processing',
            'image_status'     => 'processing',
        ]);

        // Destination Webhook for Neural Prompts
        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_spark';

        try {
            // ---------------------------------------------------------
            // 5. MULTIPART UPLOAD TRANSMISSION TO N8N
            // ---------------------------------------------------------
            // Using fopen to stream the file directly to n8n, preventing RAM spikes.
            $response = Http::withoutVerifying()
                ->timeout(120)
                ->attach('product_image', fopen($localFilePath, 'r'), $fileName)
                ->post($webhookUrl, [
                    'id'               => $recordId,
                    'product_name'     => $request->product_name,
                    'business_type'    => $request->business_type,
                    'marketing_angle'  => $request->marketing_angle,
                    'visual_prop'      => $request->visual_prop,
                    'atmosphere'       => $request->atmosphere,
                    'camera_motion'    => $request->camera_motion,
                    'composition'      => $request->composition,
                    'lighting_style'   => $request->lighting_style,
                ]);

            // ---------------------------------------------------------
            // 6. RESPONSE HANDLING & CREDIT DEDUCTION
            // ---------------------------------------------------------
            if ($response->successful()) {
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('directive_credits');
                }
                
                $msg = $response->json()['message'] ?? 'Prompt Flow Initialized! 1 Credit Deducted.';
                return redirect()->route('cgi.index')->with('success', $msg);
            } else {
                $n8nError = $response->json()['message'] ?? 'Webhook rejected the payload (HTTP ' . $response->status() . ')';
                CgiGeneration::where('id', $recordId)->update(['status' => 'failed', 'image_status' => 'failed']);
                return redirect()->route('cgi.index')->with('error', 'Pipeline failed: ' . $n8nError);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('n8n Timeout', ['message' => $e->getMessage()]);
            CgiGeneration::where('id', $recordId)->update(['status' => 'failed', 'image_status' => 'failed']);
            return redirect()->route('cgi.index')->with('error', 'The AI is taking too long to respond. Please check your dashboard in a minute.');
        } catch (\Exception $e) {
            Log::error('Connection to n8n Failed', ['message' => $e->getMessage()]);
            CgiGeneration::where('id', $recordId)->update(['status' => 'failed', 'image_status' => 'failed']);
            return redirect()->route('cgi.index')->with('error', 'Pipeline failed to start. Credits were NOT deducted.');
        }
    }

    /**
     * =========================================================================
     * CORE PIPELINE: MODIFY NEURAL PROMPTS
     * =========================================================================
     * Allows the user to manually override the AI-generated prompts before
     * proceeding to the final render stage.
     */
    public function updatePrompts(Request $request, $id)
    {
        $generation = CgiGeneration::where('id', (string)$id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $generation->image_prompt = $request->input('image_prompt');
        $generation->video_prompt = $request->input('video_prompt');
        $generation->audio_prompt = $request->input('audio_prompt');

        if ($generation->save()) {
            return response()->json([
                'success' => true,
                'image_prompt' => $generation->image_prompt,
                'video_prompt' => $generation->video_prompt,
                'audio_prompt' => $generation->audio_prompt
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Database save failed.'], 500);
    }

    /**
     * =========================================================================
     * CORE PIPELINE: RENDER PRIMARY IMAGE
     * =========================================================================
     * Fires the secondary webhook to convert the validated prompts into
     * a high-fidelity image output. Costs 1 Image Credit.
     */
    public function makePicture(Request $request, $id)
    {
        $user = auth()->user();
        $activeWallet = null;

        // Verify Image Credits
        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            if (!$activeWallet || $activeWallet->image_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Image Credits. Please upgrade.'], 403);
            }
        }

        $generation = CgiGeneration::where('id', (string)$id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Lock the row to 'making' state
        $generation->image_status = 'making';
        $generation->save();

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_MakePicture_spark'; 

        try {
            // Determine public accessibility of the source image mask
            $publicImageUrl = str_starts_with($generation->product_image, 'http') 
                ? $generation->product_image 
                : asset('storage/' . $generation->product_image);

            // Dispatch generation payload.
            // Mandatory brand directives are appended at send-time so they always
            // apply to the final render without polluting the user-editable prompt.
            $response = Http::withoutVerifying()->timeout(120)->asJson()->post($webhookUrl, [
                'id' => $generation->id,
                'image_prompt' => $this->applyBrandDirectives($generation->image_prompt, $generation->business_type),
                'negative_prompt' => $generation->negative_prompt,
                'product_image' => $publicImageUrl
            ]);

            if ($response->successful()) {
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('image_credits');
                }

                $msg = $response->json()['message'] ?? 'Image rendering started!';
                return response()->json(['success' => true, 'new_status' => 'making', 'message' => $msg]);
            } else {
                $n8nError = $response->json()['message'] ?? 'Webhook rejected request';
                $generation->update(['image_status' => 'failed']);
                return response()->json(['success' => false, 'message' => 'Generation Failed: ' . $n8nError], 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $generation->update(['image_status' => 'failed']);
            return response()->json(['success' => false, 'message' => 'Generation timed out. Please check back later.'], 500);
        } catch (\Exception $e) {
            $generation->update(['image_status' => 'processing']); 
            return response()->json(['success' => false, 'message' => 'Connection failed. Credits not deducted.'], 500);
        }
    }

    /**
     * =========================================================================
     * CORE PIPELINE: RENDER VIDEO
     * =========================================================================
     * Takes the finalized generated image and converts it into a moving 
     * cinematic sequence using the secondary prompt. Costs 1 Video Credit.
     */
    public function makeVideo(Request $request, $id)
    {
        $user = auth()->user();
        $activeWallet = null;

        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            if (!$activeWallet || $activeWallet->video_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Video Credits. Please upgrade.'], 403);
            }
        }

        $generation = CgiGeneration::where('id', (string)$id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // [UPDATE 1]: Set to making and CLEAR any previous errors
        $generation->update([
            'video_status' => 'making',
            'video_error_message' => null
        ]);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/video_generation_spark'; 

        try {
            $publicImageUrl = str_starts_with($generation->image_url, 'http') 
                ? $generation->image_url 
                : asset('storage/' . $generation->image_url);

            $payload = [
                "instances" => [
                    [
                        "prompt" => $generation->video_prompt . " " . $generation->audio_prompt,
                        "image_url" => $publicImageUrl, 
                        "audio_prompt" => $generation->audio_prompt, 
                    ]
                ],
                "parameters" => [
                    "sampleCount" => 1,
                    "durationSeconds" => 8,
                    "negativePrompt" => $generation->negative_prompt,
                    "aspectRatio" => "16:9"
                ],
                "id" => $generation->id 
            ];

            $response = Http::withoutVerifying()->timeout(120)->asJson()->post($webhookUrl, $payload);

            if ($response->successful()) {
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('video_credits');
                }

                $msg = $response->json()['message'] ?? 'Video generation sequence initiated!';
                return response()->json(['success' => true, 'message' => $msg]);
            } else {
                $n8nError = $response->json()['message'] ?? 'Webhook rejected request';
                
                // [UPDATE 2]: Save immediate webhook rejections to the database
                $generation->update([
                    'video_status' => 'failed',
                    'video_error_message' => 'Webhook Error: ' . $n8nError
                ]);
                return response()->json(['success' => false, 'message' => 'Video Generation Failed: ' . $n8nError], 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // [UPDATE 3]: Save timeout errors to the database
            $generation->update([
                'video_status' => 'failed',
                'video_error_message' => 'Connection timed out. Please check back later.'
            ]);
            return response()->json(['success' => false, 'message' => 'Generation timed out. Please check back later.'], 500);
        } catch (\Exception $e) {
            // [UPDATE 4]: Changed status from 'processing' to 'failed' to prevent infinite spinners
            $generation->update([
                'video_status' => 'failed', 
                'video_error_message' => 'Pipeline Connection Failed: ' . $e->getMessage()
            ]); 
            return response()->json(['success' => false, 'message' => 'Pipeline Connection Failed. Credits not deducted.'], 500);
        }
    }

    /**
     * =========================================================================
     * BRANDING PIPELINE: OVERLAY LOGO ON IMAGE
     * =========================================================================
     * Receives a user-uploaded logo from the inline modal and transmits it
     * securely via multipart/form-data to n8n. Costs 1 Image Brand Credit.
     */
    public function applyBrandingImage(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;

        $request->validate([
            'id' => 'required|exists:cgi_generations,id',
            'logo' => 'required_without:logo_id|nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'logo_id' => 'required_without:logo|nullable|integer|exists:logos,id',
            'placement' => 'required|string|in:bottom_right,bottom_left,top_right,top_left,center',
        ]);

        if ($user->role !== 'admin') {
            
            // Legacy Permission Check REMOVED. Relying strictly on Wallet balances.

            $activeWallet = \App\Models\UserPackage::with('package')->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            // Fallback validation against the package allowance configuration
            if (!$activeWallet || !$activeWallet->package || $activeWallet->package->branding_image_allowance <= 0) {
                return response()->json(['success' => false, 'message' => 'Your current package does not allow custom image branding.'], 403);
            }

            // Real-time Database Wallet Check
            if ($activeWallet->branding_image_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Image Branding Credits. Please Top-up.'], 403);
            }
        }

        $query = CgiGeneration::where('id', $request->id);
        
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $generation = $query->firstOrFail();

        if (!$generation->image_url) {
            return response()->json(['success' => false, 'message' => 'No original image exists to brand.'], 400);
        }

        try {
            $logoFile = $this->resolveBrandingLogoUpload($request);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // Lock UI to processing state
        $generation->update(['image_status' => 'making']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_ApplyBranding_image_spark';

        try {
            $publicImageUrl = str_starts_with($generation->image_url, 'http') ? $generation->image_url : asset('storage/' . $generation->image_url);

            // Upload the logo to n8n directly using multipart stream
            $response = Http::withoutVerifying()->timeout(120)
                ->attach('logo', fopen($logoFile['path'], 'r'), $logoFile['name'])
                ->post($webhookUrl, [
                    'id' => $generation->id,
                    'image_url' => $publicImageUrl,
                    'placement' => $request->placement,
                ]);

            if ($response->successful()) {
                // Deduct specific Branding Image Credit from Wallet
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('branding_image_credits');
                }
                
                $msg = $response->json()['message'] ?? 'Image branding pipeline initiated successfully!';
                return response()->json(['success' => true, 'message' => $msg]);
            } else {
                $n8nError = $response->json()['message'] ?? 'n8n processing failed (HTTP ' . $response->status() . ')';
                $generation->update(['image_status' => 'completed']);
                return response()->json(['success' => false, 'message' => 'Branding Failed: ' . $n8nError], 500);
            }
        } catch (\Exception $e) {
            $generation->update(['image_status' => 'completed']);
            return response()->json(['success' => false, 'message' => 'Branding engine offline. Credits not deducted.'], 500);
        } finally {
            if (!empty($logoFile['temp']) && is_file($logoFile['path'])) {
                @unlink($logoFile['path']);
            }
        }
    }

    /**
     * =========================================================================
     * BRANDING PIPELINE: OVERLAY LOGO ON VIDEO
     * =========================================================================
     * Receives a user-uploaded logo from the inline modal and transmits it
     * securely via multipart/form-data to n8n. Costs 1 Video Brand Credit.
     */
    public function applyBrandingVideo(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;

        $request->validate([
            'id' => 'required|exists:cgi_generations,id',
            'logo' => 'required_without:logo_id|nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'logo_id' => 'required_without:logo|nullable|integer|exists:logos,id',
            'placement' => 'required|string|in:bottom_right,bottom_left,top_right,top_left,center',
        ]);

        if ($user->role !== 'admin') {
            
            // Legacy Permission Check REMOVED. Relying strictly on Wallet balances.

            $activeWallet = \App\Models\UserPackage::with('package')->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            // Fallback validation against the package allowance configuration
            if (!$activeWallet || !$activeWallet->package || $activeWallet->package->branding_video_allowance <= 0) {
                return response()->json(['success' => false, 'message' => 'Your current package does not allow custom video branding.'], 403);
            }

            // Real-time Database Wallet Check
            if ($activeWallet->branding_video_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Video Branding Credits. Please Top-up.'], 403);
            }
        }

        $query = CgiGeneration::where('id', $request->id);
        
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $generation = $query->firstOrFail();

        if (!$generation->video_url) {
            return response()->json(['success' => false, 'message' => 'No video exists to brand.'], 400);
        }

        try {
            $logoFile = $this->resolveBrandingLogoUpload($request);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // Lock UI to processing state
        $generation->update(['video_status' => 'making']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_ApplyBranding_video_spark';

        try {
            $publicVideoUrl = str_starts_with($generation->video_url, 'http') ? $generation->video_url : asset('storage/' . $generation->video_url);

            // Upload the logo to n8n directly using multipart stream
            $response = Http::withoutVerifying()->timeout(120)
                ->attach('logo', fopen($logoFile['path'], 'r'), $logoFile['name'])
                ->post($webhookUrl, [
                    'id' => $generation->id,
                    'video_url' => $publicVideoUrl,
                    'placement' => $request->placement,
                ]);

            if ($response->successful()) {
                // Deduct specific Branding Video Credit from Wallet
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('branding_video_credits');
                }
                
                $msg = $response->json()['message'] ?? 'Video branding pipeline initiated successfully!';
                return response()->json(['success' => true, 'message' => $msg]);
            } else {
                $n8nError = $response->json()['message'] ?? 'n8n processing failed (HTTP ' . $response->status() . ')';
                $generation->update(['video_status' => 'completed']);
                return response()->json(['success' => false, 'message' => 'Branding Failed: ' . $n8nError], 500);
            }
        } catch (\Exception $e) {
            $generation->update(['video_status' => 'completed']);
            return response()->json(['success' => false, 'message' => 'Branding engine offline. Credits not deducted.'], 500);
        } finally {
            if (!empty($logoFile['temp']) && is_file($logoFile['path'])) {
                @unlink($logoFile['path']);
            }
        }
    }

    /**
     * Resolve an uploaded logo file or a saved library logo for branding webhooks.
     *
     * @return array{path: string, name: string, temp?: bool}
     */
    protected function resolveBrandingLogoUpload(Request $request): array
    {
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            return [
                'path' => $file->getRealPath(),
                'name' => $file->getClientOriginalName(),
            ];
        }

        $user = auth()->user();
        $logoQuery = Logo::where('id', $request->logo_id);
        if ($user->role !== 'admin') {
            $logoQuery->where('user_id', $user->id);
        }
        $logo = $logoQuery->first();

        if (!$logo) {
            throw new \InvalidArgumentException('Selected logo was not found.');
        }

        $filePath = $logo->file_path;
        if (str_starts_with($filePath, 'http')) {
            $tmp = tempnam(sys_get_temp_dir(), 'cgi_logo_');
            $contents = Http::withoutVerifying()->timeout(30)->get($filePath)->body();
            file_put_contents($tmp, $contents);
            $name = basename(parse_url($filePath, PHP_URL_PATH) ?: '') ?: ($logo->name ?: 'logo.png');

            return ['path' => $tmp, 'name' => $name, 'temp' => true];
        }

        if (!Storage::disk('public')->exists($filePath)) {
            throw new \InvalidArgumentException('Saved logo file is missing. Please re-upload it.');
        }

        $absolute = Storage::disk('public')->path($filePath);

        return [
            'path' => $absolute,
            'name' => basename($filePath) ?: ($logo->name ?: 'logo.png'),
        ];
    }

    /**
     * =========================================================================
     * BRANDING PIPELINE: ADD FOOTER LOGOS
     * =========================================================================
     * Receives one or two footer logos and transmits them to n8n for processing.
     */
    public function applyFooter(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;

        $request->validate([
            'id'         => 'required|exists:cgi_generations,id',
            'logo_left'  => 'nullable|image|max:5120',
            'logo_right' => 'nullable|image|max:5120',
        ]);

        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::with('package')->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            if (! $activeWallet || ! $activeWallet->package || $activeWallet->package->branding_image_allowance <= 0) {
                return response()->json(['success' => false, 'message' => 'Your current package does not allow image branding.'], 403);
            }

            if ($activeWallet->branding_image_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Image Branding Credits. Please top up.'], 403);
            }
        }

        $query = CgiGeneration::where('id', $request->id);

        if ($user->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $generation = $query->firstOrFail();

        if (! $generation->image_url) {
            return response()->json(['success' => false, 'message' => 'No image exists to add footer.'], 400);
        }

        $generation->update(['footer_status' => 'making']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/add_footer';

        try {
            $client = Http::withoutVerifying()->timeout(120);

            // 1. Attach the main image as a physical file (since n8n can't reach local URLs)
            $mainImagePath = storage_path('app/public/' . $generation->image_url);
            if (file_exists($mainImagePath)) {
                $client = $client->attach('image', file_get_contents($mainImagePath), basename($generation->image_url));
            }

            // 2. Attach the logos using physical file contents
            if ($request->hasFile('logo_left')) {
                $file = $request->file('logo_left');
                $client = $client->attach('logo_left', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
            }
            if ($request->hasFile('logo_right')) {
                $file = $request->file('logo_right');
                $client = $client->attach('logo_right', file_get_contents($file->getRealPath()), $file->getClientOriginalName());
            }

            // 3. Dispatch the request
            $response = $client->post($webhookUrl, [
                'id'            => $generation->id,
                'image_url'     => str_starts_with($generation->image_url, 'http') ? $generation->image_url : asset('storage/' . $generation->image_url),
                'executionMode' => 'production'
            ]);

            if ($response->successful()) {
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('branding_image_credits');
                }

                return response()->json(['success' => true, 'message' => 'Footer branding pipeline initiated!']);
            }

            $generation->update(['footer_status' => 'pending']);

            return response()->json(['success' => false, 'message' => 'n8n rejected footer request (HTTP '.$response->status().'). Credits were NOT deducted.'], 500);
        } catch (\Exception $e) {
            $generation->update(['footer_status' => 'pending']);

            return response()->json(['success' => false, 'message' => 'Footer engine offline or connection timed out. Credits were NOT deducted.'], 500);
        }
    }

    /**
     * =========================================================================
     * MERGE PIPELINE: MERGE WITH CUSTOM TEMPLATE
     * =========================================================================
     * Triggers n8n to merge the generated image with a user-provided template URL.
     */
    public function mergeTemplate(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;

        $request->validate([
            'id'                => 'required|exists:cgi_generations,id',
            'template_asset_id' => 'nullable|integer|exists:product_assets,id',
            'template_url'      => 'nullable|url',
            'template_image'    => 'nullable|image|max:5120',
        ]);

        if (! $request->hasFile('template_image') && ! $request->filled('template_asset_id')) {
            return response()->json(['success' => false, 'message' => 'A template image or library template is required.'], 422);
        }

        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::with('package')->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            if (! $activeWallet || ! $activeWallet->package || $activeWallet->package->branding_image_allowance <= 0) {
                return response()->json(['success' => false, 'message' => 'Your current package does not allow image branding.'], 403);
            }

            if ($activeWallet->branding_image_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Image Branding Credits. Please top up.'], 403);
            }
        }

        $query = CgiGeneration::where('id', $request->id);

        if ($user->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $generation = $query->firstOrFail();

        if (! $generation->image_url) {
            return response()->json(['success' => false, 'message' => 'No image exists to merge.'], 400);
        }

        $generation->update(['merge_status' => 'processing']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/merge_pic';

        try {
            $client = Http::withoutVerifying()->timeout(120);

            // 1. Attach the main image as a physical file
            $mainImagePath = storage_path('app/public/' . $generation->image_url);
            if (file_exists($mainImagePath)) {
                $client = $client->attach('image', file_get_contents($mainImagePath), basename($generation->image_url));
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
                'id'            => $generation->id,
                'image_url'     => str_starts_with($generation->image_url, 'http') ? $generation->image_url : asset('storage/' . $generation->image_url),
                'template_url'  => $templateUrl,
                'executionMode' => 'production'
            ]);

            if ($response->successful()) {
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('branding_image_credits');
                }

                return response()->json(['success' => true, 'message' => 'Merge pipeline started!']);
            }

            $generation->update(['merge_status' => 'failed']);

            return response()->json(['success' => false, 'message' => 'n8n rejected merge request. Credits were NOT deducted.'], 500);
        } catch (\Exception $e) {
            $generation->update(['merge_status' => 'failed']);

            return response()->json(['success' => false, 'message' => 'Merge engine connection failed. Credits were NOT deducted.'], 500);
        }
    }

    /**
     * =========================================================================
     * MERGE PIPELINE: MERGE VIDEO WITH CUSTOM TEMPLATE
     * =========================================================================
     * Triggers n8n to merge the generated video with a user-provided template.
     * Mirrors mergeTemplate() but targets the video asset + merge_video webhook.
     */
    public function mergeVideoTemplate(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;

        $request->validate([
            'id'                => 'required|exists:cgi_generations,id',
            'template_asset_id' => 'nullable|integer|exists:product_assets,id',
            'template_url'      => 'nullable|url',
            'template_image'    => 'nullable|image|max:5120',
        ]);

        if (! $request->hasFile('template_image') && ! $request->filled('template_asset_id')) {
            return response()->json(['success' => false, 'message' => 'A template image or library template is required.'], 422);
        }

        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::with('package')->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            if (! $activeWallet || ! $activeWallet->package || $activeWallet->package->branding_video_allowance <= 0) {
                return response()->json(['success' => false, 'message' => 'Your current package does not allow custom video branding.'], 403);
            }

            if ($activeWallet->branding_video_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Video Branding Credits. Please top up.'], 403);
            }
        }

        $query = CgiGeneration::where('id', $request->id);

        if ($user->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $generation = $query->firstOrFail();

        if (! $generation->video_url) {
            return response()->json(['success' => false, 'message' => 'No video exists to merge.'], 400);
        }

        $generation->update(['merge_video_status' => 'processing']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/merge_video';

        try {
            $client = Http::withoutVerifying()->timeout(120);

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
                'id'            => $generation->id,
                'video_url'     => str_starts_with($generation->video_url, 'http') ? $generation->video_url : asset('storage/' . $generation->video_url),
                'template_url'  => $templateUrl,
                'executionMode' => 'production'
            ]);

            if ($response->successful()) {
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('branding_video_credits');
                }

                return response()->json(['success' => true, 'message' => 'Video merge pipeline started!']);
            }

            $generation->update(['merge_video_status' => 'failed']);

            return response()->json(['success' => false, 'message' => 'n8n rejected video merge request. Credits were NOT deducted.'], 500);
        } catch (\Exception $e) {
            $generation->update(['merge_video_status' => 'failed']);

            return response()->json(['success' => false, 'message' => 'Video merge engine connection failed. Credits were NOT deducted.'], 500);
        }
    }

    /**
     * AI social caption for Create Post (image or video) via n8n.
     */
    public function generateCaption(Request $request, $id)
    {
        $user = auth()->user();
        $generation = CgiGeneration::findOrFail($id);

        if ($user->role !== 'admin' && (string) $generation->user_id !== (string) $user->id) {
            abort(403);
        }

        $languageValues = array_column(config('caption_language_options.languages', []), 'value');

        $request->validate([
            'media_url'        => 'required|string',
            'media_type'       => 'required|in:image,video',
            'is_branded'       => 'nullable',
            'caption_language' => 'required|in:' . implode(',', $languageValues ?: ['bangla', 'english']),
        ]);

        $langOption = collect(config('caption_language_options.languages', []))
            ->firstWhere('value', $request->caption_language);

        $publicMediaUrl = str_starts_with($request->media_url, 'http')
            ? $request->media_url
            : url($request->media_url);

        $isBranded = filter_var($request->is_branded, FILTER_VALIDATE_BOOLEAN);

        $payload = [
            'generation_id'           => $generation->id,
            'product_name'            => $generation->product_name,
            'marketing_angle'         => $generation->marketing_angle,
            'media_type'              => $request->media_type,
            'is_branded'              => $isBranded,
            'caption_language'        => $request->caption_language,
            'caption_language_label'  => $langOption['label'] ?? $request->caption_language,
            'caption_language_native' => $langOption['native'] ?? ($langOption['label'] ?? $request->caption_language),
            'executionMode'           => 'production',
        ];

        if ($request->media_type === 'image') {
            $payload['image_prompt'] = $generation->image_prompt;
            $payload['image_url']    = $publicMediaUrl;
        } else {
            $payload['video_prompt'] = $generation->video_prompt;
            $payload['video_url']    = $publicMediaUrl;
        }

        try {
            $response = Http::withoutVerifying()->timeout(120)->post(
                'https://n8n.egeneration.co/webhook/cgi_Auto_caption',
                $payload
            );

            if ($response->successful()) {
                $caption = $this->extractCaptionFromN8n($response->json(), $response->body());

                if ($caption !== '') {
                    return response()->json(['success' => true, 'caption' => $caption]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Caption engine returned an empty response.',
                    'fallback_caption' => $this->defaultSocialCaption($generation),
                ], 422);
            }

            Log::warning('cgi_Auto_caption rejected', [
                'generation_id' => $generation->id,
                'status'        => $response->status(),
                'body'          => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI caption service returned an error.',
                'fallback_caption' => $this->defaultSocialCaption($generation),
            ], 502);
        } catch (\Exception $e) {
            Log::error('cgi_Auto_caption failed', [
                'generation_id' => $generation->id,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Caption engine offline or timed out.',
                'fallback_caption' => $this->defaultSocialCaption($generation),
            ], 503);
        }
    }

    /**
     * @param  mixed  $json
     */
    protected function extractCaptionFromN8n($json, string $rawBody = ''): string
    {
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $json = $decoded;
            } else {
                return trim($json, "\"' \t\n\r\0\x0B");
            }
        }

        if (!is_array($json)) {
            return trim($rawBody, "\"' \t\n\r\0\x0B");
        }

        if (isset($json[0]) && is_array($json[0])) {
            $json = $json[0];
        }

        if (isset($json['output'])) {
            if (is_string($json['output'])) {
                $decoded = json_decode($json['output'], true);
                if (is_array($decoded)) {
                    return $this->extractCaptionFromN8n($decoded);
                }
                return trim($json['output'], "\"' \t\n\r\0\x0B");
            }
            if (is_array($json['output'])) {
                return $this->extractCaptionFromN8n($json['output']);
            }
        }

        foreach (['caption', 'text', 'message', 'content', 'social_caption', 'post_caption'] as $key) {
            if (!empty($json[$key]) && is_string($json[$key])) {
                return trim($json[$key], "\"' \t\n\r\0\x0B");
            }
        }

        if (isset($json['data']) && is_array($json['data'])) {
            return $this->extractCaptionFromN8n($json['data']);
        }

        return trim($rawBody, "\"' \t\n\r\0\x0B");
    }

    /**
     * =========================================================================
     * SOCIAL BROADCAST PIPELINE
     * =========================================================================
     * Transmits the finalized media asset directly to social media platforms.
     */
    public function publishToSocial(Request $request, $id)
    {
        $user = auth()->user();
        $activeWallet = null;
        
        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            if (!$activeWallet || $activeWallet->social_post_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Social Posting Credits. Please upgrade.'], 403);
            }
        }

        $request->validate([
            'caption' => 'nullable|string',
            'media_url' => 'required|string',
            'type' => 'required|in:image,video',
            'is_branded' => 'required',
        ]);

        $generation = CgiGeneration::findOrFail($id);

        $approvalBlock = ApprovalController::publishBlockedReason(
            $user,
            'cgi',
            $generation->id,
            $request->media_url,
            $generation
        );
        if ($approvalBlock) {
            return response()->json(['success' => false, 'message' => $approvalBlock], 403);
        }

        $isBrandedBool = filter_var($request->is_branded, FILTER_VALIDATE_BOOLEAN);

        $publicMediaUrl = str_starts_with($request->media_url, 'http') 
                            ? $request->media_url 
                            : url($request->media_url);

        $caption = trim((string) ($request->caption ?? ''));
        if ($caption === '') {
            $caption = $this->defaultSocialCaption($generation);
        }

        $socialPost = CgiSocialPost::create([
            'cgi_generation_id' => $generation->id,
            'platform' => 'facebook',
            'media_type' => $request->type,
            'is_branded' => $isBrandedBool ? 1 : 0,
            'media_url' => $publicMediaUrl,
            'caption' => $caption,
            'status' => 'pending' 
        ]);

        $n8nWebhookUrl = 'https://n8n.egeneration.co/webhook/publishToSocial_spark'; 

        try {
            $response = Http::timeout(120)->post($n8nWebhookUrl, [
                'post_id' => $socialPost->id,
                'caption' => $socialPost->caption,
                'media_url' => $socialPost->media_url,
                'media_type' => $socialPost->media_type,
                'is_branded' => $isBrandedBool, 
                'product_name' => $generation->product_name
            ]);

            if ($response->successful()) {
                
                $socialPost->update([
                    'status' => 'published',
                    'published_at' => now()
                ]); 
                
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('social_post_credits');
                }

                $msg = $response->json()['message'] ?? 'Published to Social Media Successfully!';
                return response()->json(['success' => true, 'message' => $msg]);
            } else {
                $n8nError = $response->json()['message'] ?? 'n8n rejected the request.';
                $socialPost->update(['status' => 'n8n_rejected']);
                return response()->json(['success' => false, 'message' => 'Publishing Failed: ' . $n8nError], 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $socialPost->update(['status' => 'connection_failed']);
            return response()->json(['success' => false, 'message' => 'Publishing timed out. Please check Facebook.'], 500);
        } catch (\Exception $e) {
            $socialPost->update(['status' => 'connection_failed']);
            return response()->json(['success' => false, 'message' => 'Failed to connect to n8n. Credits not deducted.'], 500);
        }
    }

    /**
     * Default Facebook caption when the composer is left empty.
     */
    protected function defaultSocialCaption(CgiGeneration $generation): string
    {
        $lines = array_filter([
            $generation->product_name,
        ]);

        if ($generation->marketing_angle) {
            $benefits = array_filter(array_map('trim', explode(',', $generation->marketing_angle)));
            if ($benefits) {
                $lines[] = implode(' • ', $benefits);
            }
        }

        return implode("\n\n", $lines);
    }

    /**
     * =========================================================================
     * GALLERIES, HISTORY & CLEANUP
     * =========================================================================
     * Helper routes for viewing historical generated assets.
     */
    public function videoGallery()
    {
        $user = auth()->user();

        // SMART ADMIN CHECK
        $isAdmin = false;
        if (isset($user->role) && in_array(strtolower($user->role), ['admin', 'super admin', 'system admin'])) {
            $isAdmin = true;
        } elseif (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('Super Admin'))) {
            $isAdmin = true;
        } elseif ($user->id === 1) { 
            $isAdmin = true;
        }

        // Base Query for Videos — every generation that has at least one video URL saved
        $query = \App\Models\CgiGeneration::with('user')
            ->where(function ($q) {
                foreach (['video_url', 'branded_video_url', 'merged_video_url'] as $column) {
                    $q->orWhere(function ($sub) use ($column) {
                        $sub->whereNotNull($column)->where($column, '!=', '');
                    });
                }
            })
            ->orderBy('created_at', 'desc');

        // SECURITY & CLIENT LIST LOGIC
        if ($isAdmin) {
            // If Admin: Fetch ALL users for the dropdown (even those with 0 videos)
            $clients = \App\Models\User::orderBy('name', 'asc')->get();
        } else {
            // If Client: Lock query to their ID and they don't need a client list
            $query->where('user_id', $user->id);
            $clients = collect([$user]); 
        }

        $canViewBranded = $isAdmin || $user->can('view_branded_assets');
        $assets = GalleryAssetPaginator::paginateCgiMedia($query, 'video', $canViewBranded);

        [$approvalMap, $requiresApproval] = $this->buildApprovalContext($user);

        return view('cgi.gallery', compact('assets', 'clients', 'isAdmin', 'approvalMap', 'requiresApproval'));
    }

    public function imageGallery()
    {
        $user = auth()->user();

        // SMART ADMIN CHECK
        $isAdmin = false;
        if (isset($user->role) && in_array(strtolower($user->role), ['admin', 'super admin', 'system admin'])) {
            $isAdmin = true;
        } elseif (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('Super Admin'))) {
            $isAdmin = true;
        } elseif ($user->id === 1) { 
            $isAdmin = true;
        }

        // Base Query for Images — every generation that has at least one image URL saved
        $query = \App\Models\CgiGeneration::with('user')
            ->where(function ($q) {
                foreach (['image_url', 'branded_image_url', 'merged_image_url'] as $column) {
                    $q->orWhere(function ($sub) use ($column) {
                        $sub->whereNotNull($column)->where($column, '!=', '');
                    });
                }
            })
            ->orderBy('created_at', 'desc');

        // SECURITY & CLIENT LIST LOGIC
        if ($isAdmin) {
            // If Admin: Fetch ALL users for the dropdown
            $clients = \App\Models\User::orderBy('name', 'asc')->get();
        } else {
            // If Client: Lock query to their ID and they don't need a client list
            $query->where('user_id', $user->id);
            $clients = collect([$user]); 
        }

        $canViewBranded = $isAdmin || $user->can('view_branded_assets');
        $assets = GalleryAssetPaginator::paginateCgiMedia($query, 'image', $canViewBranded);

        [$approvalMap, $requiresApproval] = $this->buildApprovalContext($user);

        return view('cgi.image-gallery', compact('assets', 'clients', 'isAdmin', 'approvalMap', 'requiresApproval'));
    }

    /**
     * Builds a lookup of the current maker's approval records keyed by the
     * normalised media filename, so the gallery can show live status badges.
     * Returns [array $approvalMap, bool $requiresApproval].
     */
    private function buildApprovalContext($user): array
    {
        return ApprovalController::buildMakerApprovalContext($user);
    }

    public function postHistory()
    {
        $posts = CgiSocialPost::with('generation')
            ->whereHas('generation', function($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cgi.post-history', compact('posts'));
    }

    public function destroy($id)
    {
        CgiGeneration::where('id', $id)->where('user_id', Auth::id())->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Directive deleted permanently.');
    }

    /**
     * =========================================================================
     * BRAND CREATIVE DIRECTIVES (MANDATORY)
     * =========================================================================
     * Appends the client's non-negotiable creative rules to the image prompt
     * right before it is dispatched to the renderer. This enforces the client's
     * poster style on every generation WITHOUT adding any new placement field
     * to the Director form, and without overwriting the user-editable prompt
     * stored in the database.
     *
     * Reference style (lighting brand poster):
     *  - The SAME lighting product is shown installed and switched ON inside the
     *    scene, visibly lighting up the space to demonstrate real-life usage.
     *  - Photorealistic real-life scene illuminated naturally by the product.
     *  - Product showcased again as the hero cutout in the bottom-right corner.
     *  - Clean, darker negative space up top reserved for a marketing headline.
     *  - Requested marketing text rendered as a clean headline, not crowded bullets.
     */
    private function applyBrandDirectives(?string $imagePrompt, ?string $businessType = 'lighting'): string
    {
        $imagePrompt = trim((string) $imagePrompt);
        $businessType = CgiBusinessPresets::isValid((string) $businessType) ? (string) $businessType : 'lighting';
        $directives = CgiBusinessPresets::brandDirectives($businessType);

        return $imagePrompt === ''
            ? $directives
            : $imagePrompt . "\n\n" . $directives;
    }

    /**
     * =========================================================================
     * AI AUTO-FILL PIPELINE
     * =========================================================================
     * Analyzes a reference image (uploaded or from library) to automatically
     * populate the CGI creation form using AI.
     */
    public function autoFill(Request $request)
    {
        $request->validate([
            'product_image'       => 'nullable|image|max:5120',
            'selected_asset_path' => 'nullable|string',
            'business_type'       => 'nullable|string|in:' . implode(',', CgiBusinessPresets::keys()),
        ]);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/autofill_cgi_studio';

        try {
            $client = Http::withoutVerifying()->timeout(120);
            $fileName = 'image.jpg';
            $fileContents = null;

            // Case A: User uploaded a NEW file
            if ($request->hasFile('product_image')) {
                $file = $request->file('product_image');
                $fileContents = file_get_contents($file->getRealPath());
                $fileName = $file->getClientOriginalName();
            } 
            // Case B: User selected an EXISTING asset from library
            elseif ($request->filled('selected_asset_path')) {
                $path = $request->selected_asset_path;
                // Get the absolute local path to the file in storage
                $absolutePath = storage_path('app/public/' . $path);
                
                if (file_exists($absolutePath)) {
                    $fileContents = file_get_contents($absolutePath);
                    $fileName = basename($path);
                } else {
                    return response()->json(['success' => false, 'message' => 'Library asset not found on server.'], 404);
                }
            } 
            else {
                return response()->json(['success' => false, 'message' => 'Please provide a product image.'], 400);
            }

            // Send physical file as multipart/form-data with 'image' key
            $response = $client->attach('image', $fileContents, $fileName)
                ->post($webhookUrl, [
                    'executionMode' => 'production',
                    'business_type' => $request->input('business_type', 'lighting'),
                ]);

            if ($response->successful()) {
                $rawData = $response->json();
                $processedData = [];

                // 1. Handle "output" wrapping from n8n
                $targetData = isset($rawData['output']) ? $rawData['output'] : $rawData;

                // 2. If the data is still a string (common with AI agents), try to parse it
                if (is_string($targetData)) {
                    // Try to fix common AI JSON errors (like missing commas between quotes)
                    $cleanString = preg_replace('/"([^"]+)"\s+"/', '"$1", "', $targetData);
                    $decoded = json_decode($cleanString, true);
                    if ($decoded) {
                        $processedData = $decoded;
                    }
                } else {
                    $processedData = is_array($targetData) ? $targetData : [];
                }

                $mapped = \App\Support\CgiAutofillMapper::map(is_array($processedData) ? $processedData : []);

                return response()->json([
                    'success' => true,
                    'data'    => $mapped,
                    'raw'     => $processedData,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'AI Analysis failed. System rejected the media.'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI Engine is currently offline. Please try manual entry.'
            ], 500);
        }
    }
}