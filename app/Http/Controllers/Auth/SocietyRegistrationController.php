<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterSocietyRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Society\SocietyRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Public self-service tenant signup: creates a Society, its admin user and a
 * trial subscription, then logs the new admin straight in.
 */
class SocietyRegistrationController extends Controller
{
    public function __construct(protected SocietyRegistrationService $registration) {}

    public function show(): View
    {
        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get();
        $selectedPlan = request()->query('plan')
            ? $plans->firstWhere('slug', request()->query('plan'))
            : null;

        return view('auth.register', compact('plans', 'selectedPlan'));
    }

    public function store(RegisterSocietyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $society = $this->registration->register(
            societyData: [
                'name'  => $data['society_name'],
                'city'  => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
            ],
            adminData: [
                'name'     => $data['admin_name'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ],
            plan: isset($data['plan_id'])
                ? SubscriptionPlan::active()->find($data['plan_id'])
                : SubscriptionPlan::active()->orderBy('sort_order')->first(),
        );

        $admin = User::query()->where('society_id', $society->id)->firstOrFail();
        Auth::login($admin);

        return redirect()->route('dashboard');
    }
}
