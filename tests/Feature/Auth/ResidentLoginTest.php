<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_resident_sign_in_page_posts_to_resident_authentication(): void
    {
        $this->get(route('resident.login'))
            ->assertOk()
            ->assertSee('Sign in to your resident portal')
            ->assertSee('action="'.route('resident.login').'"', false)
            ->assertSee('Create a resident account');
    }

    public function test_resident_sign_in_authenticates_and_redirects_to_the_portal(): void
    {
        $society = $this->makeSociety('Resident Login Society', 'resident-login@test.com');
        $resident = $this->makeUser($society, 'resident', [
            'email' => 'portal-resident@test.com',
            'password' => 'Password@123',
        ]);

        $this->post(route('resident.login'), [
            'email' => $resident->email,
            'password' => 'Password@123',
        ])
            ->assertRedirect(route('resident.portal'));

        $this->assertAuthenticatedAs($resident);
    }

    public function test_staff_accounts_cannot_use_the_resident_sign_in(): void
    {
        $society = $this->makeSociety('Staff Login Society', 'staff-login@test.com');
        $admin = $this->admin($society);

        $this->from(route('resident.login'))
            ->post(route('resident.login'), [
                'email' => $admin->email,
                'password' => 'Password@123',
            ])
            ->assertRedirect(route('resident.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
