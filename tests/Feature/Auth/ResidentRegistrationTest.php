<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Flat;
use App\Models\Resident;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_resident_registration_page_has_the_requested_fields(): void
    {
        $this->get(route('resident.register'))
            ->assertOk()
            ->assertSee('Society invite code')
            ->assertSee('Building name or wing')
            ->assertSee('Flat number')
            ->assertSee('Owner')
            ->assertSee('Tenant')
            ->assertSee('Phone number');
    }

    public function test_registration_structure_only_returns_buildings_and_flats_for_the_invite_code(): void
    {
        $society = $this->makeSociety('Structure Lookup Society', 'structure-lookup@test.com');
        $flat = $this->makeFlat($society->id);
        $otherSociety = $this->makeSociety('Other Structure Society', 'other-structure@test.com');
        $this->makeFlat($otherSociety->id, 'O-101');

        $this->getJson(route('resident.register.structure', ['invite_code' => strtolower($society->resident_invite_code)]))
            ->assertOk()
            ->assertJsonPath('towers.0.name', 'North Wing')
            ->assertJsonPath('towers.0.code', 'N')
            ->assertJsonPath('towers.0.flats.0', $flat->number)
            ->assertJsonMissing(['name' => 'Other Structure Society']);
    }

    public function test_owner_registration_creates_the_account_and_admin_resident_record(): void
    {
        $society = $this->makeSociety('Resident Registration Society', 'resident-registration@test.com');
        $flat = $this->makeFlat($society->id);

        $this->post(route('resident.register'), $this->registrationData($society->resident_invite_code, $flat->tower_id))
            ->assertRedirect(route('resident.portal'))
            ->assertSessionHas('success');

        $user = User::withoutGlobalScopes()->where('email', 'new-owner@test.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('resident'));
        $this->assertDatabaseHas('residents', [
            'society_id' => $society->id,
            'user_id' => $user->id,
            'flat_id' => $flat->id,
            'type' => 'owner',
            'name' => 'New Owner',
            'email' => 'new-owner@test.com',
            'phone' => '+919876543210',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('flats', [
            'id' => $flat->id,
            'owner_id' => $user->id,
            'status' => 'occupied',
        ]);

        $this->actingAs($this->admin($society))
            ->get(route('residents.index'))
            ->assertOk()
            ->assertSee('New Owner');
    }

    public function test_tenant_registration_creates_a_tenant_account(): void
    {
        $society = $this->makeSociety('Tenant Registration Society', 'tenant-registration@test.com');
        $flat = $this->makeFlat($society->id, 'W-202');
        $data = $this->registrationData($society->resident_invite_code, $flat->tower_id);
        $data['type'] = 'tenant';
        $data['email'] = 'new-tenant@test.com';
        $data['flat_number'] = 'W-202';

        $this->post(route('resident.register'), $data)
            ->assertRedirect(route('resident.portal'));

        $user = User::withoutGlobalScopes()->where('email', 'new-tenant@test.com')->firstOrFail();
        $this->assertTrue($user->hasRole('tenant'));
        $this->assertDatabaseHas('residents', [
            'user_id' => $user->id,
            'flat_id' => $flat->id,
            'type' => 'tenant',
        ]);
        $this->assertNull($flat->fresh()->owner_id);
    }

    public function test_registration_rejects_invalid_invite_codes_and_unknown_units(): void
    {
        $society = $this->makeSociety('Invite Validation Society', 'invite-validation@test.com');
        $flat = $this->makeFlat($society->id);

        $this->from(route('resident.register'))
            ->post(route('resident.register'), $this->registrationData('NOT-A-VALID-CODE', $flat->tower_id))
            ->assertRedirect(route('resident.register'))
            ->assertSessionHasErrors('invite_code');

        $data = $this->registrationData($society->resident_invite_code, 999999);
        $this->from(route('resident.register'))
            ->post(route('resident.register'), $data)
            ->assertRedirect(route('resident.register'))
            ->assertSessionHasErrors('tower_id');

        $this->assertDatabaseMissing('users', ['email' => 'new-owner@test.com']);
        $this->assertSame(0, Resident::withoutGlobalScopes()->where('society_id', $society->id)->count());
    }

    public function test_a_second_owner_cannot_register_against_the_same_flat(): void
    {
        $society = $this->makeSociety('Duplicate Owner Society', 'duplicate-owner@test.com');
        $flat = $this->makeFlat($society->id);

        $this->post(route('resident.register'), $this->registrationData($society->resident_invite_code, $flat->tower_id))
            ->assertRedirect(route('resident.portal'));
        $this->post(route('logout'));

        $secondOwner = $this->registrationData($society->resident_invite_code, $flat->tower_id);
        $secondOwner['email'] = 'second-owner@test.com';

        $this->from(route('resident.register'))
            ->post(route('resident.register'), $secondOwner)
            ->assertRedirect(route('resident.register'))
            ->assertSessionHasErrors('flat_number');

        $this->assertDatabaseMissing('users', ['email' => 'second-owner@test.com']);
    }

    public function test_resident_invite_code_is_available_to_admin_and_can_be_regenerated(): void
    {
        $society = $this->makeSociety('Invite Management Society', 'invite-management@test.com');
        $admin = $this->admin($society);
        $originalCode = $society->resident_invite_code;

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee($originalCode);

        $this->post(route('settings.resident-invite-code.rotate'))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $this->assertNotSame($originalCode, $society->fresh()->resident_invite_code);
    }

    private function registrationData(string $inviteCode, int $towerId): array
    {
        return [
            'invite_code' => strtolower($inviteCode),
            'type' => 'owner',
            'name' => 'New Owner',
            'phone' => '+919876543210',
            'email' => 'new-owner@test.com',
            'tower_id' => $towerId,
            'flat_number' => 'N-101',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ];
    }

    private function makeFlat(int $societyId, string $number = 'N-101'): Flat
    {
        $tower = Tower::firstOrCreate(
            ['society_id' => $societyId, 'code' => 'N'],
            ['name' => 'North Wing', 'type' => 'wing', 'status' => 'active'],
        );

        return Flat::create([
            'society_id' => $societyId,
            'tower_id' => $tower->id,
            'number' => $number,
            'status' => 'vacant',
        ]);
    }
}
