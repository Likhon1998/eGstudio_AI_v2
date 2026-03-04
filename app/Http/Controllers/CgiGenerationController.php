<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CgiGeneration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CgiGenerationController extends Controller
{
    /**
     * Display the history of CGI directives.
     */
    public function index()
    {
        $generations = CgiGeneration::where('user_id', Auth::id())
            ->latest()
            ->get();
            
        return view('cgi.index', compact('generations'));
    }

    /**
     * Show the form for creating a new directive.
     */
    public function create()
    {
        return view('cgi.create');
    }

    /**
     * Process the form and trigger the initial prompt generation flow in n8n.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_name'    => 'required|string|max:255',
            'marketing_angle' => 'required|string|max:255',
            'visual_prop'     => 'required|string|max:255',
            'atmosphere'      => 'required|string|max:255',
            'camera_motion'   => 'required|string|max:255',
            'composition'     => 'required|string|max:255',
            'lighting_style'  => 'required|string|max:255',
        ]);

        $recordId = (string) Str::uuid();

        // Create initial record with 'processing' status for both prompts and images
        CgiGeneration::create([
            'id'              => $recordId,
            'user_id'         => Auth::id(),
            'product_name'    => $request->product_name,
            'marketing_angle' => $request->marketing_angle,
            'visual_prop'     => $request->visual_prop,
            'atmosphere'      => $request->atmosphere,
            'camera_motion'   => $request->camera_motion,
            'composition'     => $request->composition,
            'lighting_style'  => $request->lighting_style,
            'status'          => 'processing', // Global prompt status
            'image_status'    => 'processing', // Sub-status for image
        ]);

        $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_AI';

        try {
            $response = Http::withoutVerifying()->asJson()->post($webhookUrl, [
                'id'              => $recordId,
                'product_name'    => $request->product_name,
                'marketing_angle' => $request->marketing_angle,
                'visual_prop'     => $request->visual_prop,
                'atmosphere'      => $request->atmosphere,
                'camera_motion'   => $request->camera_motion,
                'composition'     => $request->composition,
                'lighting_style'  => $request->lighting_style,
            ]);

            if ($response->successful()) {
                return redirect()->route('cgi.index')->with('success', 'Prompt Flow Initialized!');
            }
        } catch (\Exception $e) {
            Log::error('Connection to n8n Failed', ['message' => $e->getMessage()]);
        }

        return redirect()->route('cgi.index')->with('error', 'Pipeline failed to start.');
    }

    /**
     * Trigger the second stage: Image Rendering Flow.
     * This is called when the user clicks "MAKE PICTURE".
     */
    public function makePicture(Request $request, $id)
{
    $generation = CgiGeneration::where('id', (string)$id)
        ->where('user_id', Auth::id())
        ->firstOrFail();

    // Set status to 'making' and save immediately
    $generation->image_status = 'making';
    $generation->save();

    $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_MakePicture'; 

    try {
        Http::withoutVerifying()->asJson()->post($webhookUrl, [
            'id' => $generation->id,
            'image_prompt' => $generation->image_prompt,
            'negative_prompt' => $generation->negative_prompt,
            'product_name' => $generation->product_name
        ]);

        return response()->json([
            'success' => true, 
            'new_status' => 'making', // Send the new status back to Alpine
            'message' => 'Image rendering started!'
        ]);
    } catch (\Exception $e) {
        $generation->update(['image_status' => 'processing']); 
        return response()->json(['success' => false, 'message' => 'Connection failed.'], 500);
    }
}
    /**
     * Update prompt content manually via the dashboard modals.
     */
    /**
 * Update prompt content manually via the dashboard modals.
 */
/**
 * Update prompt content manually via the dashboard modals.
 */
public function updatePrompts(Request $request, $id)
{
    $generation = CgiGeneration::where('id', (string)$id)
        ->where('user_id', Auth::id())
        ->firstOrFail();

    // Use specific inputs to ensure clear mapping
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
     * Central Callback Handler.
     * Handles Stage 1 (Prompts) and Stage 2 (Image URL) at different times.
     */
    public function callback(Request $request)
{
    $id = $request->input('id');
    $generation = CgiGeneration::where('id', $id)->first();

    if (!$generation) {
        return response()->json(['error' => 'Record not found'], 404);
    }

    // PATH D: The Branding is FINISHED (New Logic)
    if ($request->has('branded_image_url') || $request->has('branded_video_url')) {
        $updateData = [];
        if ($request->has('branded_image_url')) {
            $updateData['branded_image_url'] = $request->input('branded_image_url');
        }
        if ($request->has('branded_video_url')) {
            $updateData['branded_video_url'] = $request->input('branded_video_url');
        }
        
        $generation->update($updateData);
        return response()->json(['status' => 'success', 'message' => 'Branded Media Saved']);
    }

    // PATH C: The Video Render is FINISHED (Existing Logic)
    if ($request->has('video_url') && $request->video_url !== null) {
        $generation->update([
            'video_url'    => $request->input('video_url'),
            'video_status' => 'completed' 
        ]);
        return response()->json(['status' => 'success', 'message' => 'Video Render Saved']);
    }

    // PATH B: The 3D Render is FINISHED (Existing Logic)
    if ($request->has('image_url') && $request->image_url !== null) {
        $generation->update([
            'image_url'    => $request->input('image_url'),
            'image_status' => 'completed' 
        ]);
        return response()->json(['status' => 'success', 'message' => 'Image Render Saved']);
    }

    // PATH A: Initial Prompts arrived (Existing Logic)
    $generation->update([
        'status'          => 'completed',
        'image_prompt'    => $request->input('image_prompt'), 
        'video_prompt'    => $request->input('video_prompt'),
        'audio_prompt'    => $request->input('audio_prompt'),
        'negative_prompt' => $request->input('negative_prompt'),
    ]);

    return response()->json(['status' => 'success', 'message' => 'AI Prompts Stored']);
}

    public function destroy($id)
    {
        CgiGeneration::where('id', $id)->where('user_id', Auth::id())->firstOrFail()->delete();
        return redirect()->back()->with('success', 'Directive deleted.');
    }

    public function makeVideo(Request $request, $id)
{
    $generation = CgiGeneration::where('id', (string)$id)
        ->where('user_id', Auth::id())
        ->firstOrFail();

    $generation->update(['video_status' => 'making']);

    $webhookUrl = 'https://n8n.egeneration.co/webhook/video_generation'; 

    try {
        $payload = [
            "instances" => [
                [
                    // Combined string for the AI engine
                    "prompt" => $generation->video_prompt . " " . $generation->audio_prompt,
                    "image_url" => $generation->image_url, 
                    // NEW: Explicit audio prompt for n8n mapping
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

        Http::withoutVerifying()->asJson()->post($webhookUrl, $payload);

        return response()->json([
            'success' => true, 
            'message' => 'Video generation sequence initiated!'
        ]);
    } catch (\Exception $e) {
        $generation->update(['video_status' => 'processing']); 
        return response()->json(['success' => false, 'message' => 'Pipeline Connection Failed.'], 500);
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
    // Fetch generations that have either a standard image or a branded image
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
    // 1. Validate the incoming request
    $request->validate([
        'id' => 'required|exists:cgi_generations,id',
        'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:5120', // Max 5MB
    ]);

    // 2. Fetch the secure, authentic links directly from the Database
    $generation = CgiGeneration::where('id', $request->id)
        ->where('user_id', Auth::id())
        ->firstOrFail();

    // 3. Set status to 'making' so the frontend spinners activate
    $generation->update([
        'image_status' => 'making',
        'video_status' => 'making'
    ]);

    // 4. Define your n8n webhook URL
    $webhookUrl = 'https://n8n.egeneration.co/webhook/eGStudio_ApplyBranding';

    try {
        // 5. Send the multipart form request (Logo file + Database URLs) to n8n
        $response = Http::withoutVerifying()
            ->attach(
                'logo', 
                file_get_contents($request->file('logo')->getRealPath()), 
                $request->file('logo')->getClientOriginalName()
            )
            ->post($webhookUrl, [
                'id' => $generation->id,
                'image_url' => $generation->image_url,
                'video_url' => $generation->video_url,
            ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Branding pipeline initiated!'
            ]);
        }

        // If n8n returns an error, revert the status
        $generation->update([
            'image_status' => 'completed',
            'video_status' => 'completed'
        ]);
        return response()->json(['success' => false, 'message' => 'n8n processing failed.'], 500);

    } catch (\Exception $e) {
        // If the connection to n8n drops entirely, revert the status
        $generation->update([
            'image_status' => 'completed',
            'video_status' => 'completed'
        ]);
        return response()->json(['success' => false, 'message' => 'Branding engine offline.'], 500);
    }
}
}