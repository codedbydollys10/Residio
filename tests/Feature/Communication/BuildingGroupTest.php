<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\Conversation;
use App\Models\Flat;
use App\Models\Resident;
use App\Models\Tower;
use App\Models\User;
use App\Services\Communication\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_residents_of_the_same_building_can_chat_and_other_buildings_are_private(): void
    {
        $society = $this->makeSociety('Building Group Society', 'building-groups@test.com');
        $secretary = $this->admin($society);
        $secretary->update(['designation' => 'Society Secretary']);

        $tower = Tower::create([
            'society_id' => $society->id,
            'name' => 'Tower A',
            'code' => 'A',
            'type' => 'tower',
            'status' => 'active',
        ]);
        $otherTower = Tower::create([
            'society_id' => $society->id,
            'name' => 'Tower B',
            'code' => 'B',
            'type' => 'tower',
            'status' => 'active',
        ]);

        $residentA = $this->makeBuildingResident($society->id, $tower->id, 'Resident A', 'resident-a');
        $residentB = $this->makeBuildingResident($society->id, $tower->id, 'Resident B', 'resident-b');
        $outsider = $this->makeBuildingResident($society->id, $otherTower->id, 'Tower B Resident', 'tower-b-resident');
        $flatOwnerWithoutResidentRow = $this->makeUser(
            $society,
            'resident',
            ['name' => 'Flat Owner', 'email' => 'flat-owner@test.com'],
        );
        Flat::create([
            'society_id' => $society->id,
            'tower_id' => $tower->id,
            'number' => 'A-103',
            'status' => 'occupied',
            'owner_id' => $flatOwnerWithoutResidentRow->id,
        ]);

        $this->actingAs($residentA)
            ->get(route('communication.building-groups.index'))
            ->assertOk()
            ->assertSee('Tower A Group')
            ->assertDontSee('Tower B Group');

        $groupResponse = $this->get(route('communication.building-groups.open', $tower));
        $conversation = Conversation::where('tower_id', $tower->id)->firstOrFail();
        $groupResponse->assertRedirect(route('communication.messages.show', $conversation));

        $messaging = app(CommunicationService::class);
        $messaging->postMessage($conversation, $residentB->id, 'First message from Resident B');
        $messaging->postMessage($conversation, $residentA->id, 'Later reply from Resident A');

        $this->get(route('communication.messages.show', $conversation))
            ->assertOk()
            ->assertSee('Resident B')
            ->assertSee('Flat Owner')
            ->assertSee($secretary->name)
            ->assertSee('Group admin · Secretary')
            ->assertSee('whatsapp-chat-messages', false)
            ->assertSee('whatsapp-chat-composer', false)
            ->assertSee('groupInfoModal', false)
            ->assertDontSee('col-lg-4', false)
            ->assertSeeInOrder(['First message from Resident B', 'Later reply from Resident A']);
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $flatOwnerWithoutResidentRow->id,
        ]);

        $this->actingAs($flatOwnerWithoutResidentRow)
            ->get(route('communication.messages.show', $conversation))
            ->assertOk();

        $this->actingAs($residentA);
        $this->post(route('communication.messages.store'), [
            'conversation_id' => $conversation->id,
            'body' => 'Hello Tower A!',
        ])->assertRedirect(route('communication.messages.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $residentA->id,
            'body' => 'Hello Tower A!',
        ]);

        $this->actingAs($outsider)
            ->get(route('communication.messages.show', $conversation))
            ->assertForbidden();

        $this->get(route('communication.building-groups.open', $tower))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->get(route('communication.building-groups.index'))
            ->assertOk()
            ->assertSee('Tower A Group')
            ->assertSee('Tower B Group');

        $towerBGroup = Conversation::where('tower_id', $otherTower->id)->firstOrFail();
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $towerBGroup->id,
            'user_id' => $outsider->id,
        ]);
        $this->assertDatabaseMissing('conversation_participants', [
            'conversation_id' => $towerBGroup->id,
            'user_id' => $residentA->id,
        ]);
    }

    private function makeBuildingResident(int $societyId, int $towerId, string $name, string $emailPrefix): User
    {
        $user = $this->makeUser(
            \App\Models\Society::findOrFail($societyId),
            'resident',
            ['name' => $name, 'email' => $emailPrefix.'@test.com'],
        );

        $flat = Flat::create([
            'society_id' => $societyId,
            'tower_id' => $towerId,
            'number' => $emailPrefix,
            'status' => 'occupied',
            'owner_id' => $user->id,
        ]);

        Resident::create([
            'society_id' => $societyId,
            'user_id' => $user->id,
            'flat_id' => $flat->id,
            'type' => 'owner',
            'name' => $name,
            'email' => $user->email,
            'is_primary' => true,
            'status' => 'active',
        ]);

        return $user;
    }
}
