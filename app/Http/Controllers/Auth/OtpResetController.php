<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http; // Required to talk to n8n
use App\Models\User;
use Carbon\Carbon;

class OtpResetController extends Controller
{
    // --- 1. SHOW EMAIL REQUEST FORM ---
    public function showRequestForm()
    {
        return view('auth.otp-request');
    }

    // --- 2. GENERATE OTP & TRIGGER n8n ---
    public function sendOtp(Request $request)
    {
        // STRICT CHECK: Reject instantly if email is not in the system
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Unrecognized identity ping. This email is not registered.'
        ]);

        // Generate a 6-character OTP
        $otp = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        // Save to DB (Expires in 5 minutes)
        DB::table('otp_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'otp' => Hash::make($otp),
                'expires_at' => Carbon::now()->addMinutes(5),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        // FIRE WEBHOOK TO n8n
        try {
            $response = Http::timeout(5)->post('https://n8n.egeneration.co/webhook/send-otp', [
                'email' => $request->email,
                'otp' => $otp
            ]);

            if ($response->failed()) {
                throw new \Exception('n8n rejected the payload.');
            }
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Communication offline: Could not reach the email server.']);
        }

        // Redirect to step 3 with the email saved in the session
        return redirect()->route('password.otp.form')->with('reset_email', $request->email);
    }

    // --- 3. BLADE 1: SHOW OTP INPUT ---
    public function showVerifyForm(Request $request)
    {
        $email = session('reset_email') ?? $request->email;
        
        if (!$email) {
            return redirect()->route('password.request')->withErrors(['email' => 'Session expired. Please start over.']);
        }

        return view('auth.otp-verify', compact('email'));
    }

    // --- 4. MIDDLEWARE: CHECK OTP ---
    public function checkOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email', 
            'otp' => 'required|string'
        ]);

        $record = DB::table('otp_resets')->where('email', $request->email)->first();

        if (!$record || Carbon::now()->greaterThan($record->expires_at)) {
            return back()->withErrors(['otp' => 'Invalid or expired temporary password.']);
        }

        if (!Hash::check($request->otp, $record->otp)) {
            return back()->withErrors(['otp' => 'Incorrect temporary password.']);
        }

        // OTP is correct! Store clearance in session and move to Blade 2
        session([
            'cleared_for_reset' => true, 
            'reset_email' => $request->email, 
            'valid_otp' => $request->otp
        ]);
        
        return redirect()->route('password.new.form');
    }

    // --- 5. BLADE 2: SHOW NEW PASSWORD INPUT ---
    public function showNewPasswordForm()
    {
        // Security Check: Block users who try to skip the OTP screen
        if (!session('cleared_for_reset')) {
            return redirect()->route('password.request')->withErrors(['email' => 'Unauthorized access. Please verify OTP first.']);
        }
        
        return view('auth.otp-new-password');
    }

    // --- 6. FINAL: UPDATE PASSWORD ---
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        $email = session('reset_email');
        
        // One final security check to ensure it didn't expire while they were typing
        $record = DB::table('otp_resets')->where('email', $email)->first();
        
        if (!$record || Carbon::now()->greaterThan($record->expires_at)) {
            session()->forget(['cleared_for_reset', 'reset_email', 'valid_otp']);
            return redirect()->route('password.request')->withErrors(['email' => 'Session timed out. Please request a new code.']);
        }

        // Securely Update Password
        $user = User::where('email', $email)->first();
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        // Destroy the used OTP and clear sessions
        DB::table('otp_resets')->where('email', $email)->delete();
        session()->forget(['cleared_for_reset', 'reset_email', 'valid_otp']);

        return redirect()->route('login')->with('status', 'Neural Identity updated! You can now log in.');
    }
}