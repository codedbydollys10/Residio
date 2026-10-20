<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $moduleGroups = collect(config('communityos.modules'))
            ->reject(fn (array $module) => $module['group'] === 'Platform')
            ->groupBy('group');

        $roles = [
            [
                'key' => 'society-admin',
                'daily' => 'Oversees the community, people, finances and day-to-day operations.',
            ],
            [
                'key' => 'accountant',
                'daily' => 'Keeps bills, payments, vendor expenses and ledgers up to date.',
            ],
            [
                'key' => 'security-guard',
                'daily' => 'Checks visitors in and out and manages the gate desk.',
            ],
            [
                'key' => 'maintenance-staff',
                'daily' => 'Works through assigned complaints and maintenance tasks.',
            ],
            [
                'key' => 'resident',
                'name' => 'Resident / Tenant',
                'description' => config('communityos.roles.resident.description').' '.config('communityos.roles.tenant.description'),
                'daily' => 'Checks notices and bills, raises requests and books shared facilities.',
            ],
            [
                'key' => 'vendor',
                'daily' => 'Receives service work and keeps job progress and invoices organised.',
            ],
        ];

        return view('home', compact('plans', 'moduleGroups', 'roles'));
    }
}
