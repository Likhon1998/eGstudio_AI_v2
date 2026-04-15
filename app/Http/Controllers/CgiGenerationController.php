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

class CgiGenerationController extends Controller
{
    /**
     * Display the history of CGI directives.
     */
    public function index()
    {
        // 1. The Security Gate
        if (!auth()->user()->can('view_cgi_index')) {
            abort(403, 'SYSTEM ALERT: You lack clearance to access the Directive Studio.');
        }

        // 2. Intelligent Data Scoping
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
     * Show the form for creating a new directive.
     */
    public function create()
    {
        $productAssets = ProductAsset::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('cgi.create', compact('productAssets'));
    }

    /**
     * Process the form and trigger the initial prompt generation flow in n8n.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;

        // --- 1. SAAS GATEKEEPER ---
        if ($user->role !== 'admin') {
            $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('is_active_selection', 'true') 
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->first();

            if (!$activeWallet) {
                return redirect()->route('cgi.index')->with('error', 'You have no active package. Please activate or purchase a plan.');
            }

            if ($activeWallet->directive_credits < 1) {
                return redirect()->route('cgi.index')->with('error', 'Insufficient Prompt Credits in your active wallet. Please refill.');
            }
        }

        // --- 2. Validation (FIXED: Manual Validator stops the silent redirect bug) ---
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
            // Converts hidden validation errors into a visible frontend Toast!
            return redirect()->back()
                ->withInput()
                ->with('error', 'Form Error: ' . $validator->errors()->first());
        }

        $recordId = (string) Str::uuid();

        // --- 3. Handle the Image Paths Securely ---
        $imagePath = null;
        $localFilePath = null; 
        $fileName = null;

        if ($request->hasFile('product_image')) {
            $imagePath = $request->file('product_image')->store('products', 'public');
            $localFilePath = storage_path('app/public/' . $imagePath);
            $fileName = $request->file('product_image')->getClientOriginalName();
            
            ProductAsset::create([
                'user_id' => $user->id,
                'name' => pathinfo($fileName, PATHINFO_FILENAME), 
                'file_path' => $imagePath,
            ]);
            
        } elseif ($request->filled('selected_asset_path')) {
            $imagePath = $request->input('selected_asset_path');
            $localFilePath = storage_path('app/public/' . $imagePath);
            $fileName = basename($imagePath);
            
        } elseif ($request->filled('previous_image_url')) {
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

        // --- CRITICAL SAFETY CHECK ---
        if (!$localFilePath || !file_exists($localFilePath)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'System Error: Source image file could not be located on the server. Pipeline aborted.');
        }

        // --- 4. Create initial record ---
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

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_spark';

        try {
            // --- 5. CHAINED MULTIPART UPLOAD (Using fopen to prevent memory crashes) ---
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

            // --- 6. CHECK FOR SUCCESS ---
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
     * Trigger the second stage: Image Rendering Flow.
     */
    public function makePicture(Request $request, $id)
    {
        $user = auth()->user();
        $activeWallet = null;

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

        $generation->image_status = 'making';
        $generation->save();

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_MakePicture_spark'; 

        try {
            $publicImageUrl = str_starts_with($generation->product_image, 'http') 
                ? $generation->product_image 
                : asset('storage/' . $generation->product_image);

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
                $n8nError = $response->json()['message'] ?? 'Webhook rejected request (HTTP ' . $response->status() . ')';
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
     * Update prompt content manually via the dashboard modals.
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

    public function destroy($id)
    {
        CgiGeneration::where('id', $id)->where('user_id', Auth::id())->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Directive deleted.');
    }

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

        $generation->update(['video_status' => 'making']);

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
                $n8nError = $response->json()['message'] ?? 'Webhook rejected request (HTTP ' . $response->status() . ')';
                $generation->update(['video_status' => 'failed']);
                return response()->json(['success' => false, 'message' => 'Video Generation Failed: ' . $n8nError], 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $generation->update(['video_status' => 'failed']);
            return response()->json(['success' => false, 'message' => 'Generation timed out. Please check back later.'], 500);
        } catch (\Exception $e) {
            $generation->update(['video_status' => 'processing']); 
            return response()->json(['success' => false, 'message' => 'Pipeline Connection Failed. Credits not deducted.'], 500);
        }
    }

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

    public function applyBranding(Request $request)
    {
        $user = auth()->user();
        $activeWallet = null;
        
        if ($user->role !== 'admin') {
            if (!$user->can('apply_branding')) {
                return response()->json(['success' => false, 'message' => 'Your security clearance does not allow neural branding.'], 403);
            }

            $activeWallet = \App\Models\UserPackage::with('package')->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->where(function ($query) { $query->whereNull('expires_at')->orWhere('expires_at', '>', now()); })->first();

            if (!$activeWallet || !$activeWallet->package || $activeWallet->package->branding_allowance <= 0) {
                return response()->json(['success' => false, 'message' => 'Your current package does not allow custom branding.'], 403);
            }

            if ($activeWallet->branding_credits < 1) {
                return response()->json(['success' => false, 'message' => 'Out of Branding Credits. Please upgrade.'], 403);
            }
        }

        $request->validate([
            'id' => 'required|exists:cgi_generations,id',
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:5120', 
        ]);

        $generation = CgiGeneration::where('id', $request->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $generation->update([
            'image_status' => 'making',
            'video_status' => 'making'
        ]);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_ApplyBranding_spark';

        try {
            $publicImageUrl = str_starts_with($generation->image_url, 'http') ? $generation->image_url : asset('storage/' . $generation->image_url);
            $publicVideoUrl = str_starts_with($generation->video_url, 'http') ? $generation->video_url : asset('storage/' . $generation->video_url);

            $response = Http::withoutVerifying()->timeout(120)
                ->attach(
                    'logo', 
                    fopen($request->file('logo')->getRealPath(), 'r'),
                    $request->file('logo')->getClientOriginalName()
                )
                ->post($webhookUrl, [
                    'id' => $generation->id,
                    'image_url' => $publicImageUrl,
                    'video_url' => $publicVideoUrl,
                ]);

            if ($response->successful()) {
                if ($user->role !== 'admin' && $activeWallet) {
                    $activeWallet->decrement('branding_credits');
                }

                $msg = $response->json()['message'] ?? 'Branding pipeline initiated!';
                return response()->json(['success' => true, 'message' => $msg]);
            } else {
                $n8nError = $response->json()['message'] ?? 'n8n processing failed (HTTP ' . $response->status() . ')';
                $generation->update(['image_status' => 'completed', 'video_status' => 'completed']);
                return response()->json(['success' => false, 'message' => 'Branding Failed: ' . $n8nError], 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $generation->update(['image_status' => 'completed', 'video_status' => 'completed']);
            return response()->json(['success' => false, 'message' => 'Branding timed out. Please try again.'], 500);
        } catch (\Exception $e) {
            $generation->update(['image_status' => 'completed', 'video_status' => 'completed']);
            return response()->json(['success' => false, 'message' => 'Branding engine offline. Credits not deducted.'], 500);
        }
    }

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
                
                // === THE FIX IS HERE! Update to 'published' and record the timestamp ===
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
     * Display the user's social media posting history.
     */
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
}