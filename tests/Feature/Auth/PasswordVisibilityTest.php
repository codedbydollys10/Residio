<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_sign_in_forms_have_password_visibility_and_email_placeholders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('placeholder="name@example.com"', false);

        $this->get(route('resident.login'))
            ->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('placeholder="name@example.com"', false);
    }

    public function test_registration_forms_have_password_visibility_for_both_password_fields(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('placeholder="name@example.com"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('name="password_confirmation"', false);

        $this->get(route('resident.register'))
            ->assertOk()
            ->assertSee('placeholder="name@example.com"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('id="residentPasswordConfirmation"', false);
    }
}
