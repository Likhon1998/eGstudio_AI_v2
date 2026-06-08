<?php

namespace App\Http\Controllers;

use App\Models\CgiGeneration;
use App\Services\DashboardStatsService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        if (Auth::user()->isApprover()) {
            return redirect()->route('approvals.index');
        }

        $userId = (int) Auth::id();

        DashboardStatsService::forgetForUser($userId);

        $processingCount = CgiGeneration::where('user_id', $userId)
            ->where('status', 'processing')
            ->count()
            + \App\Models\Occasion::where('user_id', $userId)
                ->whereIn('image_status', ['making', 'processing'])
                ->count();

        $stats = DashboardStatsService::forUser($userId);

        return view('dashboard', compact('processingCount', 'stats'));
    }
}
