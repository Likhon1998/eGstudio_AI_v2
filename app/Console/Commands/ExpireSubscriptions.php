<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Billing;
use Carbon\Carbon;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Check and expire user subscriptions that have passed their billing cycle.';

    public function handle()
    {
        $users = User::whereNotNull('package_id')->get();

        foreach ($users as $user) {
            if ($user->role === 'admin') continue;

            $latestBill = Billing::where('user_id', $user->id)->where('status', 'paid')->latest('paid_at')->first();
            
            if ($latestBill && $latestBill->package) {
                $paidAt = Carbon::parse($latestBill->paid_at);
                $cycle = $latestBill->package->billing_cycle;
                
                $expiryDate = null;
                if ($cycle === 'monthly') $expiryDate = $paidAt->copy()->addMonth();
                if ($cycle === 'yearly') $expiryDate = $paidAt->copy()->addYear();

                // If the current time is past the expiry date, WIPE THEIR ACCOUNT
                if ($expiryDate && now()->greaterThan($expiryDate)) {
                    $user->update([
                        'package_id' => null,
                        'directive_credits' => 0,
                        'image_credits' => 0,
                        'video_credits' => 0,
                        'branding_credits' => 0,
                        'social_post_credits' => 0,
                    ]);
                    
                    $this->info("Expired subscription for: {$user->email}");
                }
            }
        }
    }
}