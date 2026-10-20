<?php

declare(strict_types=1);

namespace Tests\Feature\Residents;

use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\Flat;
use App\Models\MaintenanceBill;
use App\Models\MaintenanceBillDocument;
use App\Models\Notice;
use App\Models\Tower;
use App\Services\Communication\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_portal_shows_only_the_signed_in_residents_bills_messages_and_broadcasts(): void
    {
        $society = $this->makeSociety('Resident Portal Society', 'portal@test.com');
        $manager = $this->admin($society);
        $residentA = $this->makeUser($society, 'resident', ['name' => 'Resident A']);
        $residentB = $this->makeUser($society, 'resident', ['name' => 'Resident B']);
        $flatA = $this->makeFlat($society->id, $residentA->id, 'P-101');
        $flatB = $this->makeFlat($society->id, $residentB->id, 'P-102');

        $billA = $this->makeBill($society->id, $residentA->id, 'PORTAL-A-1');
        $billB = $this->makeBill($society->id, $residentB->id, 'PORTAL-B-1');
        $documentA = $this->makeDocument($society->id, $flatA->id, $residentA->id, $manager->id, 'portal-a.pdf');
        $documentB = $this->makeDocument($society->id, $flatB->id, $residentB->id, $manager->id, 'portal-b-private.pdf');

        $notice = Notice::create([
            'society_id' => $society->id,
            'title' => 'Community Update',
            'body' => 'Pool maintenance this weekend.',
            'category' => 'announcement',
            'author_id' => $manager->id,
            'audience' => 'all',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $broadcastA = $this->makeBroadcast($society->id, $manager->id, 'Update for Resident A');
        $broadcastB = $this->makeBroadcast($society->id, $manager->id, 'Private update for Resident B');
        BroadcastRecipient::create([
            'society_id' => $society->id,
            'broadcast_id' => $broadcastA->id,
            'user_id' => $residentA->id,
            'channel' => 'in_app',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        BroadcastRecipient::create([
            'society_id' => $society->id,
            'broadcast_id' => $broadcastB->id,
            'user_id' => $residentB->id,
            'channel' => 'in_app',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $conversationA = app(CommunicationService::class)->startConversation(
            [$residentA->id],
            'Message for A',
            'Your payment was received.',
            $manager->id,
        );
        $conversationB = app(CommunicationService::class)->startConversation(
            [$residentB->id],
            'Private message for B',
            'Your account details are ready.',
            $manager->id,
        );

        $this->actingAs($residentA)
            ->get(route('dashboard'))
            ->assertRedirect(route('resident.portal'));

        $this->get(route('resident.portal'))
            ->assertOk()
            ->assertSee($billA->bill_number)
            ->assertSee($documentA->original_filename)
            ->assertSee($notice->title)
            ->assertSee($broadcastA->title)
            ->assertSee($conversationA->subject)
            ->assertDontSee($billB->bill_number)
            ->assertDontSee($documentB->original_filename)
            ->assertDontSee($broadcastB->title)
            ->assertDontSee('Private message for B')
            ->assertDontSee('Your account details are ready.');

        $this->get(route('communication.messages.show', $conversationB))->assertForbidden();
        $nonParticipantManager = $this->makeUser($society, 'sub-admin');
        $this->actingAs($nonParticipantManager)
            ->get(route('communication.messages.show', $conversationB))
            ->assertForbidden();
    }

    public function test_resident_can_message_management_but_not_another_resident(): void
    {
        $society = $this->makeSociety('Portal Messaging Society', 'portal-messages@test.com');
        $manager = $this->admin($society);
        $resident = $this->makeUser($society, 'resident', ['name' => 'Portal Resident']);
        $otherResident = $this->makeUser($society, 'resident', ['name' => 'Another Resident']);

        $this->actingAs($resident)
            ->get(route('resident.portal.messages'))
            ->assertOk()
            ->assertSee($manager->name)
            ->assertDontSee($otherResident->name);

        $this->post(route('resident.portal.messages.send'), [
                'recipient_id' => $manager->id,
                'subject' => 'Billing question',
                'body' => 'Could you explain this charge?',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'user_id' => $resident->id,
            'body' => 'Could you explain this charge?',
        ]);

        $this->post(route('resident.portal.messages.send'), [
            'recipient_id' => $otherResident->id,
            'subject' => 'Private',
            'body' => 'This should not be sent.',
        ])->assertNotFound();

        $this->assertDatabaseMissing('messages', ['body' => 'This should not be sent.']);

        $this->actingAs($resident, 'sanctum')
            ->postJson('/api/v1/communication/messages', [
                'participant_ids' => [$otherResident->id],
                'body' => 'API message to another resident.',
            ])
            ->assertNotFound();

        $this->postJson('/api/v1/communication/messages', [
            'participant_ids' => [$manager->id],
            'subject' => 'API question',
            'body' => 'Sent to management from mobile.',
        ])->assertCreated();
    }

    public function test_resident_cannot_view_another_residents_maintenance_bill(): void
    {
        $society = $this->makeSociety('Portal Bill Privacy Society', 'portal-bills@test.com');
        $residentA = $this->makeUser($society, 'resident');
        $residentB = $this->makeUser($society, 'resident');
        $bill = $this->makeBill($society->id, $residentB->id, 'PRIVATE-BILL');

        $this->actingAs($residentA)
            ->get(route('maintenance.bills.show', $bill))
            ->assertForbidden();
    }

    private function makeBill(int $societyId, int $userId, string $number): MaintenanceBill
    {
        return MaintenanceBill::create([
            'society_id' => $societyId,
            'bill_number' => $number,
            'user_id' => $userId,
            'period' => '2026-10',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);
    }

    private function makeDocument(int $societyId, int $flatId, int $residentId, int $managerId, string $filename): MaintenanceBillDocument
    {
        return MaintenanceBillDocument::create([
            'society_id' => $societyId,
            'flat_id' => $flatId,
            'resident_user_id' => $residentId,
            'uploaded_by' => $managerId,
            'period' => '2026-10',
            'storage_path' => 'maintenance-bills/'.$societyId.'/'.$filename,
            'original_filename' => $filename,
            'file_size' => 20,
            'status' => 'available',
            'published_at' => now(),
        ]);
    }

    private function makeFlat(int $societyId, int $ownerId, string $number): Flat
    {
        $tower = Tower::firstOrCreate(
            ['society_id' => $societyId, 'code' => 'PORTAL'],
            ['name' => 'Portal Tower', 'total_floors' => 2, 'units_per_floor' => 5, 'status' => 'active'],
        );

        return Flat::create([
            'society_id' => $societyId,
            'tower_id' => $tower->id,
            'owner_id' => $ownerId,
            'number' => $number,
            'status' => 'occupied',
        ]);
    }

    private function makeBroadcast(int $societyId, int $managerId, string $title): Broadcast
    {
        return Broadcast::create([
            'society_id' => $societyId,
            'title' => $title,
            'message' => 'A resident-specific update.',
            'channels' => ['in_app'],
            'audience' => 'residents',
            'status' => 'sent',
            'sent_at' => now(),
            'created_by' => $managerId,
        ]);
    }
}
