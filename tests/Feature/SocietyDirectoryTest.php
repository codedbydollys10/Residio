<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocietyDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_directory_shows_active_societies_without_requiring_opt_in(): void
    {
        $home = $this->makeSociety('Home Society', 'home-directory@test.com');
        $home->update([
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'is_publicly_listed' => true,
        ]);
        $admin = $this->admin($home);

        $nearby = $this->makeSociety('Listed Pune Society', 'listed-pune@test.com');
        $nearby->update([
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'address_line1' => 'Private Street 14',
            'email' => 'private-contact@test.com',
            'is_publicly_listed' => true,
        ]);

        $notListed = $this->makeSociety('Unlisted Pune Society', 'private-pune@test.com');
        $notListed->update(['city' => 'Pune', 'state' => 'Maharashtra']);

        $farAway = $this->makeSociety('Listed Mumbai Society', 'listed-mumbai@test.com');
        $farAway->update([
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'is_publicly_listed' => true,
        ]);
        $pending = $this->makeSociety('Pending Society', 'pending-directory@test.com');
        $pending->update(['status' => 'pending', 'city' => 'Pune', 'state' => 'Maharashtra']);

        $this->actingAs($admin)
            ->get(route('society-directory.index'))
            ->assertOk()
            ->assertSee('Home Society')
            ->assertSee('Listed Pune Society')
            ->assertSee('Unlisted Pune Society')
            ->assertSee('Listed Mumbai Society')
            ->assertDontSee('Pending Society')
            ->assertDontSee('Private Street 14')
            ->assertDontSee('private-contact@test.com');

        $this->get(route('society-directory.index', ['city' => '', 'state' => 'Maharashtra']))
            ->assertOk()
            ->assertSee('Home Society')
            ->assertSee('Listed Pune Society')
            ->assertSee('Unlisted Pune Society')
            ->assertSee('Listed Mumbai Society')
            ->assertDontSee('Pending Society');
    }

    public function test_admin_and_resident_dashboards_show_nearby_listed_societies(): void
    {
        $home = $this->makeSociety('Dashboard Home', 'dashboard-home@test.com');
        $home->update(['city' => 'Pune', 'state' => 'Maharashtra']);
        $admin = $this->admin($home);
        $resident = $this->makeUser($home, 'resident');

        $nearby = $this->makeSociety('Dashboard Nearby', 'dashboard-nearby@test.com');
        $nearby->update([
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'is_publicly_listed' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Societies near you')
            ->assertSee('Dashboard Nearby');

        $this->actingAs($resident)
            ->get(route('resident.portal'))
            ->assertOk()
            ->assertSee('Societies near you')
            ->assertSee('Dashboard Nearby');

        $this->get(route('society-directory.index'))
            ->assertOk()
            ->assertSee('Dashboard Nearby');
    }

    public function test_new_societies_are_listed_automatically(): void
    {
        $society = $this->makeSociety('Newly Registered Society', 'newly-registered@test.com');
        $admin = $this->admin($society);

        $this->assertDatabaseHas('societies', [
            'id' => $society->id,
            'is_publicly_listed' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('society-directory.index'))
            ->assertOk()
            ->assertSee('Newly Registered Society');
    }

    public function test_duplicate_society_names_are_shown_once_using_the_newest_active_record(): void
    {
        $home = $this->makeSociety('Directory Home', 'directory-home@test.com');
        $admin = $this->admin($home);

        $older = $this->makeSociety('Sonam Paradise', 'older-sonam@test.com');
        $older->update(['city' => 'Incorrect City', 'state' => 'Incorrect State']);

        $newer = $this->makeSociety('sonam paradise ', 'newer-sonam@test.com');
        $newer->update(['city' => 'Mumbai', 'state' => 'Maharashtra']);

        $response = $this->actingAs($admin)
            ->get(route('society-directory.index'))
            ->assertOk()
            ->assertSee('Mumbai, Maharashtra')
            ->assertDontSee('Incorrect City, Incorrect State');

        $this->assertSame(1, substr_count($response->getContent(), 'sonam paradise'));
    }
}
