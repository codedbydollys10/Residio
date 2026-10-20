<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Jobs\Communication\DeliverBroadcast;
use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Flat;
use App\Models\MaintenanceBillDocument;
use App\Models\MessageTemplate;
use App\Models\Tower;
use App\Services\Communication\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    // -------------------------------------------------------------------------
    // Broadcast: create + send resolves recipients from audience + marks sent
    // -------------------------------------------------------------------------

    public function test_admin_can_create_broadcast(): void
    {
        $society = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin   = $this->admin($society);

        $response = $this->actingAs($admin)->post('/communication/broadcasts', [
            'title'    => 'Test Broadcast',
            'message'  => 'Hello residents!',
            'channels' => ['email'],
            'audience' => 'all',
        ]);

        $broadcast = Broadcast::first();
        $this->assertNotNull($broadcast);
        $response->assertRedirect("/communication/broadcasts/{$broadcast->id}");
        $this->assertEquals('draft', $broadcast->status);
        $this->assertEquals('Test Broadcast', $broadcast->title);
    }

    public function test_send_broadcast_resolves_recipients_and_queues_job(): void
    {
        Queue::fake();

        $society  = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin    = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');

        // Create a draft broadcast.
        $broadcast = Broadcast::create([
            'society_id' => $society->id,
            'title'      => 'Residents Blast',
            'message'    => 'Important update for residents.',
            'channels'   => ['email'],
            'audience'   => 'residents',
            'status'     => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post("/communication/broadcasts/{$broadcast->id}/send")
            ->assertRedirect("/communication/broadcasts/{$broadcast->id}");

        $broadcast->refresh();

        // Status should be 'queued' and recipients created.
        $this->assertEquals('queued', $broadcast->status);
        $this->assertGreaterThan(0, $broadcast->recipients_count);

        // BroadcastRecipient rows were created.
        $this->assertDatabaseHas('broadcast_recipients', [
            'broadcast_id' => $broadcast->id,
            'user_id'      => $resident->id,
            'channel'      => 'email',
            'status'       => 'pending',
        ]);

        // DeliverBroadcast job was dispatched.
        Queue::assertPushed(DeliverBroadcast::class, fn ($job) => $job->broadcastId === $broadcast->id);
    }

    public function test_deliver_broadcast_job_marks_recipients_sent(): void
    {
        $society  = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin    = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');

        $broadcast = Broadcast::create([
            'society_id' => $society->id,
            'title'      => 'Job Test',
            'message'    => 'Testing the job.',
            'channels'   => ['email'],
            'audience'   => 'all',
            'status'     => 'draft',
            'created_by' => $admin->id,
        ]);

        BroadcastRecipient::create([
            'society_id'   => $society->id,
            'broadcast_id' => $broadcast->id,
            'user_id'      => $resident->id,
            'channel'      => 'email',
            'status'       => 'pending',
        ]);

        // Run the job synchronously.
        (new DeliverBroadcast($broadcast->id))->handle();

        $this->assertDatabaseHas('broadcast_recipients', [
            'broadcast_id' => $broadcast->id,
            'user_id'      => $resident->id,
            'status'       => 'sent',
        ]);

        $broadcast->refresh();
        $this->assertEquals('sent', $broadcast->status);
        $this->assertNotNull($broadcast->sent_at);
    }

    // -------------------------------------------------------------------------
    // Templates
    // -------------------------------------------------------------------------

    public function test_admin_can_create_a_template(): void
    {
        $society = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin   = $this->admin($society);

        $this->actingAs($admin)->post('/communication/templates', [
            'name'    => 'Welcome Email',
            'channel' => 'email',
            'subject' => 'Welcome!',
            'body'    => 'Hello {{ name }}, welcome!',
        ])->assertRedirect(route('communication.templates.index'));

        $this->assertDatabaseHas('message_templates', [
            'society_id' => $society->id,
            'name'       => 'Welcome Email',
            'channel'    => 'email',
        ]);
    }

    // -------------------------------------------------------------------------
    // Internal messaging
    // -------------------------------------------------------------------------

    public function test_admin_can_start_a_conversation_and_post_messages(): void
    {
        $society  = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $admin    = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');

        /** @var CommunicationService $service */
        $service = app(CommunicationService::class);

        $conversation = $service->startConversation(
            [$resident->id],
            'Parking query',
            'Are slots available this weekend?',
            $admin->id,
        );

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals('Parking query', $conversation->subject);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id'         => $admin->id,
            'body'            => 'Are slots available this weekend?',
        ]);

        // Both admin and resident are participants.
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id'         => $admin->id,
        ]);
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id'         => $resident->id,
        ]);

        // Resident posts a reply.
        $service->postMessage($conversation, $resident->id, 'Yes, slots B-12 and B-13 are free.');

        $this->assertEquals(2, Message::where('conversation_id', $conversation->id)->count());

        $conversation->refresh();
        $this->assertNotNull($conversation->last_message_at);
    }

    public function test_admin_can_message_a_resident_directly_with_their_maintenance_pdf_attached(): void
    {
        $society = $this->makeSociety('Direct Message Society', 'direct-message@test.com');
        $admin = $this->admin($society);
        $resident = $this->makeUser($society, 'resident', [
            'name' => 'Message Resident',
            'email' => 'message-resident@test.com',
        ]);
        $tower = Tower::create([
            'society_id' => $society->id,
            'name' => 'Message Tower',
            'code' => 'MSG',
            'total_floors' => 1,
            'units_per_floor' => 2,
        ]);
        $flat = Flat::create([
            'society_id' => $society->id,
            'tower_id' => $tower->id,
            'number' => 'MSG-101',
            'owner_id' => $resident->id,
        ]);
        $document = MaintenanceBillDocument::create([
            'society_id' => $society->id,
            'flat_id' => $flat->id,
            'resident_user_id' => $resident->id,
            'uploaded_by' => $admin->id,
            'period' => '2026-10',
            'storage_path' => 'maintenance-bills/1/private.pdf',
            'original_filename' => 'october-bill.pdf',
            'file_size' => 100,
            'status' => 'available',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('communication.messages.compose', [
            'recipient_id' => $resident->id,
            'document_id' => $document->id,
        ]))->assertOk()->assertSee('Message Resident')->assertSee('october-bill.pdf')->assertSee('name="attachment"', false);

        $this->post(route('communication.messages.direct'), [
            'recipient_id' => $resident->id,
            'subject' => 'October maintenance',
            'body' => 'Please see your attached bill.',
            'maintenance_bill_document_id' => $document->id,
        ])->assertRedirect();

        $message = Message::query()->where('maintenance_bill_document_id', $document->id)->firstOrFail();
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $message->conversation_id,
            'user_id' => $resident->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'maintenance_bill_document_id' => $document->id,
            'body' => 'Please see your attached bill.',
        ]);
        $this->get(route('communication.messages.show', $message->conversation_id))
            ->assertOk()
            ->assertSee('name="attachment"', false);
    }

    public function test_admin_cannot_attach_another_residents_bill_to_a_direct_message(): void
    {
        $society = $this->makeSociety('Message Privacy Society', 'message-privacy@test.com');
        $admin = $this->admin($society);
        $residentA = $this->makeUser($society, 'resident', ['email' => 'message-a@test.com']);
        $residentB = $this->makeUser($society, 'resident', ['email' => 'message-b@test.com']);
        $tower = Tower::create([
            'society_id' => $society->id,
            'name' => 'Privacy Tower',
            'code' => 'PRV',
            'total_floors' => 1,
            'units_per_floor' => 2,
        ]);
        $flat = Flat::create([
            'society_id' => $society->id,
            'tower_id' => $tower->id,
            'number' => 'PRV-101',
            'owner_id' => $residentB->id,
        ]);
        $document = MaintenanceBillDocument::create([
            'society_id' => $society->id,
            'flat_id' => $flat->id,
            'resident_user_id' => $residentB->id,
            'uploaded_by' => $admin->id,
            'period' => '2026-10',
            'storage_path' => 'maintenance-bills/1/private-resident.pdf',
            'original_filename' => 'resident-b.pdf',
            'file_size' => 100,
            'status' => 'available',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('communication.messages.direct'), [
            'recipient_id' => $residentA->id,
            'subject' => 'Wrong recipient',
            'body' => 'This must not send.',
            'maintenance_bill_document_id' => $document->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_direct_message_can_include_a_private_pdf_attachment(): void
    {
        Storage::fake('local');

        $society = $this->makeSociety('Message Attachment Society', 'message-attachment@test.com');
        $admin = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');
        $otherResident = $this->makeUser($society, 'resident');

        $this->actingAs($admin)->post(route('communication.messages.direct'), [
            'recipient_id' => $resident->id,
            'subject' => 'October bill',
            'body' => 'Please see the attached bill.',
            'attachment' => UploadedFile::fake()->create('october-bill.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $message = Message::query()->where('user_id', $admin->id)->firstOrFail();
        $this->assertSame('october-bill.pdf', $message->attachment_name);
        Storage::disk('local')->assertExists($message->attachment_path);

        $this->actingAs($resident)
            ->get(route('communication.messages.attachment', $message))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($otherResident)
            ->get(route('communication.messages.attachment', $message))
            ->assertForbidden();

        $this->actingAs($admin)->post(route('communication.messages.direct'), [
            'recipient_id' => $resident->id,
            'subject' => 'Bill image',
            'body' => 'Here is the bill image.',
            'attachment' => UploadedFile::fake()->create('bill-photo.jpg', 20, 'image/jpeg'),
        ])->assertRedirect();

        $imageMessage = Message::query()->where('attachment_name', 'bill-photo.jpg')->firstOrFail();
        $this->assertSame('image/jpeg', $imageMessage->attachment_mime);
        Storage::disk('local')->assertExists($imageMessage->attachment_path);
    }

    public function test_message_attachment_rejects_unsupported_file_types(): void
    {
        Storage::fake('local');

        $society = $this->makeSociety('Message Validation Society', 'message-validation@test.com');
        $admin = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');

        $this->actingAs($admin)->post(route('communication.messages.direct'), [
            'recipient_id' => $resident->id,
            'subject' => 'Unsupported attachment',
            'body' => 'This should fail validation.',
            'attachment' => UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('conversations', 0);
        Storage::disk('local')->assertDirectoryEmpty('message-attachments');
    }

    // -------------------------------------------------------------------------
    // Tenant isolation
    // -------------------------------------------------------------------------

    public function test_broadcasts_are_isolated_between_societies(): void
    {
        $alpha = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $this->actingAs($this->admin($alpha))->post('/communication/broadcasts', [
            'title'    => 'Alpha Only Broadcast',
            'message'  => 'Secret alpha message.',
            'channels' => ['email'],
            'audience' => 'all',
        ]);

        $beta = $this->makeSociety('Beta Society', 'beta@test.com');

        // Beta admin must not see Alpha's broadcast (tenant scope).
        $this->flushSession();
        $this->actingAs($this->admin($beta))
            ->get('/communication')
            ->assertOk()
            ->assertDontSee('Alpha Only Broadcast');

        $this->assertEquals(1, Broadcast::withoutGlobalScopes()->count());
    }

    // -------------------------------------------------------------------------
    // Resident permission denied for broadcast
    // -------------------------------------------------------------------------

    public function test_resident_cannot_send_broadcasts(): void
    {
        $society  = $this->makeSociety('Alpha Society', 'alpha@test.com');
        $resident = $this->makeUser($society, 'resident');

        $this->actingAs($resident)
            ->post('/communication/broadcasts', [
                'title'    => 'Resident Broadcast Attempt',
                'message'  => 'This should fail.',
                'channels' => ['email'],
                'audience' => 'all',
            ])
            ->assertForbidden();
    }
}
