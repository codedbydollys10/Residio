<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Analytics\DashboardAnalytics;
use App\Services\Society\SocietyDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardAnalytics $analytics,
        protected SocietyDirectory $directory,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->isSuperAdmin()) {
            return view('superadmin.dashboard', [
                'stats' => $this->analytics->platformStats(),
            ]);
        }

        if ($request->user()->hasAnyRole(['resident', 'tenant'])) {
            return redirect()->route('resident.portal');
        }

        return view('dashboard', [
            'stats' => $this->analytics->societyStats(),
            'currentSociety' => current_society(),
            'nearbySocieties' => $this->directory->nearby(current_society()),
        ]);
    }

    /** JSON feed for the Chart.js widgets (AJAX). */
    public function chart(Request $request, string $type): JsonResponse
    {
        return response()->json(
            $this->analytics->chart($type, $request->user())
        );
    }
}
