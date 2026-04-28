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

        // 2. Intelligent Data Scoping: Admins get global view, Users get scoped view.
        if (auth()->user()->role === 'admin') {
            $generations = CgiGeneration::latest()->get();
        } else {
            $generations = CgiGeneration::where('user_id', Auth::id())
                ->latest()
                ->get();
        }
            
        return view('cgi.index', compact('generations'));
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
            ->latest()
            ->get();

        return view('cgi.create', compact('productAssets'));
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

            // Dispatch generation payload
            $response = Http::withoutVerifying()->timeout(120)->asJson()->post($webhookUrl, [
                'id' => $generation->id,
                'image_prompt' => $generation->image_prompt,
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

        // Requires a Logo upload from the Frontend modal!
        $request->validate([
            'id' => 'required|exists:cgi_generations,id',
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:5120',
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

        $generation = CgiGeneration::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (!$generation->image_url) {
            return response()->json(['success' => false, 'message' => 'No original image exists to brand.'], 400);
        }

        // Lock UI to processing state
        $generation->update(['image_status' => 'making']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_ApplyBranding_image_spark';

        try {
            $publicImageUrl = str_starts_with($generation->image_url, 'http') ? $generation->image_url : asset('storage/' . $generation->image_url);

            // Upload the logo to n8n directly using multipart stream
            $response = Http::withoutVerifying()->timeout(120)
                ->attach('logo', fopen($request->file('logo')->getRealPath(), 'r'), $request->file('logo')->getClientOriginalName())
                ->post($webhookUrl, [
                    'id' => $generation->id,
                    'image_url' => $publicImageUrl,
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

        // Requires a Logo upload from the Frontend modal!
        $request->validate([
            'id' => 'required|exists:cgi_generations,id',
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:5120',
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

        $generation = CgiGeneration::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (!$generation->video_url) {
            return response()->json(['success' => false, 'message' => 'No video exists to brand.'], 400);
        }

        // Lock UI to processing state
        $generation->update(['video_status' => 'making']);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_ApplyBranding_video_spark';

        try {
            $publicVideoUrl = str_starts_with($generation->video_url, 'http') ? $generation->video_url : asset('storage/' . $generation->video_url);

            // Upload the logo to n8n directly using multipart stream
            $response = Http::withoutVerifying()->timeout(120)
                ->attach('logo', fopen($request->file('logo')->getRealPath(), 'r'), $request->file('logo')->getClientOriginalName())
                ->post($webhookUrl, [
                    'id' => $generation->id,
                    'video_url' => $publicVideoUrl,
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
        }
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
        
        $isBrandedBool = filter_var($request->is_branded, FILTER_VALIDATE_BOOLEAN);
        $isBrandedString = $isBrandedBool ? 'true' : 'false';

        $publicMediaUrl = str_starts_with($request->media_url, 'http') 
                            ? $request->media_url 
                            : url($request->media_url);

        $socialPost = CgiSocialPost::create([
            'cgi_generation_id' => $generation->id,
            'platform' => 'facebook',
            'media_type' => $request->type,
            'is_branded' => $isBrandedString, 
            'media_url' => $publicMediaUrl,
            'caption' => $request->caption,
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
     * =========================================================================
     * GALLERIES, HISTORY & CLEANUP
     * =========================================================================
     * Helper routes for viewing historical generated assets.
     */
    public function videoGallery()
    {
        $videos = CgiGeneration::where('user_id', Auth::id())
            ->where(function($query) {
                $query->whereNotNull('video_url')
                      ->orWhereNotNull('branded_video_url');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cgi.gallery', compact('videos'));
    }

    public function imageGallery()
    {
        $images = CgiGeneration::where('user_id', Auth::id())
            ->where(function($query) {
                $query->whereNotNull('image_url')
                      ->orWhereNotNull('branded_image_url');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cgi.image-gallery', compact('images'));
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
}