<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_active_plans_and_not_inactive_plans(): void
    {
        SubscriptionPlan::create([
            'name' => 'Public Starter',
            'slug' => 'public-starter',
            'billing_cycle' => 'monthly',
            'price' => 2500,
            'is_active' => true,
            'sort_order' => 1,
            'features' => ['visitors', 'billing'],
        ]);
        SubscriptionPlan::create([
            'name' => 'Hidden Legacy',
            'slug' => 'hidden-legacy',
            'billing_cycle' => 'annual',
            'price' => 1000,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Residio')
            ->assertDontSee('CommunityOS')
            ->assertSee('Public Starter')
            ->assertDontSee('Hidden Legacy')
            ->assertSee(route('register', ['plan' => 'public-starter']), false);
    }

    public function test_authenticated_user_sees_dashboard_call_to_action(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Go to dashboard');
    }

    public function test_hero_video_is_muted_and_loops(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('autoplay muted loop playsinline preload="metadata"', false)
            ->assertSee(asset('videos/communityos-hero.mp4'), false);
    }
}
