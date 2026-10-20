<?php

declare(strict_types=1);

namespace Tests\Feature\Facilities;

use App\Models\Facility;
use App\Models\FacilityBooking;
use App\Http\Resources\FacilityResource;
use Database\Seeders\Modules\FacilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_admin_can_create_a_facility(): void
    {
        $society = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin   = $this->admin($society);

        $response = $this->actingAs($admin)->post('/facilities', [
            'name'              => 'Clubhouse',
            'type'              => 'clubhouse',
            'description'       => 'Main clubhouse',
            'capacity'          => 100,
            'charge'            => 500,
            'requires_approval' => true,
            'opening_time'      => '08:00',
            'closing_time'      => '22:00',
            'slot_minutes'      => 60,
            'is_active'         => true,
        ]);

        $facility = Facility::first();

        $this->assertNotNull($facility);
        $response->assertRedirect("/facilities/{$facility->id}");
        $this->assertEquals('Clubhouse', $facility->name);
        $this->assertEquals('clubhouse', $facility->type);
        $this->assertEquals(500.0, $facility->charge);
        $this->assertTrue($facility->requires_approval);
    }

    public function test_admin_can_set_a_facility_image_url_and_the_card_displays_it(): void
    {
        $society = $this->makeSociety('Image Society', 'image-society@test.com');
        $admin = $this->admin($society);

        $this->actingAs($admin)->post('/facilities', [
            'name' => 'Clubhouse',
            'type' => 'clubhouse',
            'image_url' => '/images/facilities/clubhouse.jpg',
            'charge' => 0,
            'slot_minutes' => 60,
            'is_active' => true,
        ])->assertRedirect();

        $facility = Facility::firstOrFail();
        $this->assertSame('/images/facilities/clubhouse.jpg', $facility->image_url);
        $this->assertSame('/images/facilities/clubhouse.jpg', $facility->imageUrl);
        $this->assertSame('/images/facilities/clubhouse.jpg', (new FacilityResource($facility))->resolve()['imageUrl']);

        $this->actingAs($admin)
            ->get('/facilities')
            ->assertOk()
            ->assertSee('src="/images/facilities/clubhouse.jpg"', false)
            ->assertSee('alt="Clubhouse image"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('classList.remove(\'d-none\')', false)
            ->assertSee('justify-content-center d-none', false)
            ->assertSee('btn-primary flex-fill text-white', false);
    }

    public function test_facility_seeder_adds_badminton_court_and_is_safe_to_rerun(): void
    {
        $society = $this->makeSociety('Green Valley', 'green-valley@test.com');
        $this->assertSame('green-valley', $society->slug);

        (new FacilitySeeder())->run();
        (new FacilitySeeder())->run();

        $this->assertSame(6, Facility::where('society_id', $society->id)->count());
        $this->assertDatabaseHas('facilities', [
            'society_id' => $society->id,
            'name' => 'Badminton Court',
            'image_url' => '/images/facilities/badminton.jpg',
        ]);
    }

    public function test_resident_can_book_a_facility(): void
    {
        $society  = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $resident = $this->makeUser($society, 'resident');

        // Admin creates the facility first (no tenant context issue)
        $admin = $this->admin($society);
        $this->actingAs($admin)->post('/facilities', [
            'name'              => 'Gym',
            'type'              => 'gym',
            'requires_approval' => true,
            'charge'            => 0,
            'slot_minutes'      => 60,
            'is_active'         => true,
        ]);

        $facility = Facility::first();
        $this->assertNotNull($facility);

        $response = $this->actingAs($resident)->post("/facilities/{$facility->id}/book", [
            'facility_id'  => $facility->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time'   => '09:00',
            'end_time'     => '10:00',
            'guests'       => 0,
        ]);

        $booking = FacilityBooking::first();
        $this->assertNotNull($booking);
        $this->assertEquals($resident->id, $booking->user_id);
    }

    public function test_admin_can_approve_a_booking(): void
    {
        $society  = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin    = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');

        $this->actingAs($admin)->post('/facilities', [
            'name'              => 'Pool',
            'type'              => 'pool',
            'requires_approval' => true,
            'charge'            => 100,
            'slot_minutes'      => 60,
            'is_active'         => true,
        ]);

        $facility = Facility::first();

        $booking = FacilityBooking::create([
            'society_id' => $society->id,
            'facility_id' => $facility->id,
            'user_id' => $resident->id,
            'booking_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'guests' => 1,
            'amount' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->post("/bookings/{$booking->id}/approve");

        $booking->refresh();
        $this->assertEquals('approved', $booking->status);
        $this->assertEquals($admin->id, $booking->approved_by);
    }

    public function test_facilities_are_isolated_between_tenants(): void
    {
        $alpha = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $this->actingAs($this->admin($alpha))->post('/facilities', [
            'name'         => 'Alpha Gym',
            'type'         => 'gym',
            'charge'       => 0,
            'slot_minutes' => 60,
            'is_active'    => true,
        ]);

        $beta = $this->makeSociety('Beta Society', 'beta@test.com');

        $this->flushSession();

        // Beta admin must not see Alpha's facility
        $this->actingAs($this->admin($beta))
            ->get('/facilities')
            ->assertOk()
            ->assertDontSee('Alpha Gym');

        // Only one facility in DB total
        $this->assertEquals(1, Facility::withoutGlobalScopes()->count());
    }

    public function test_resident_cannot_delete_a_facility(): void
    {
        $society  = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin    = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');

        $this->actingAs($admin)->post('/facilities', [
            'name'         => 'Hall',
            'type'         => 'hall',
            'charge'       => 0,
            'slot_minutes' => 60,
            'is_active'    => true,
        ]);

        $facility = Facility::first();
        $this->assertNotNull($facility);

        $response = $this->actingAs($resident)->delete("/facilities/{$facility->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'deleted_at' => null]);
    }
}
