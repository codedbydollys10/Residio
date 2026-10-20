<?php

declare(strict_types=1);

namespace Tests\Feature\Maintenance;

use App\Models\Flat;
use App\Models\MaintenanceBillDocument;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceBillDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        Storage::fake('local');
    }

    public function test_resident_can_only_view_and_download_documents_published_to_their_account(): void
    {
        $society = $this->makeSociety('Private Document Society', 'private-doc@test.com');
        $residentA = $this->makeUser($society, 'resident', ['email' => 'resident-a@test.com']);
        $residentB = $this->makeUser($society, 'resident', ['email' => 'resident-b@test.com']);
        $flatA = $this->makeFlat($society, ['number' => 'D-101', 'owner_id' => $residentA->id]);
        $flatB = $this->makeFlat($society, ['number' => 'D-102', 'owner_id' => $residentB->id]);
        $documentA = $this->makeDocument($society, $flatA, $residentA);
        $documentB = $this->makeDocument($society, $flatB, $residentB);
        Storage::disk('local')->put($documentA->storage_path, '%PDF-1.4 document A');
        Storage::disk('local')->put($documentB->storage_path, '%PDF-1.4 document B');

        $this->actingAs($residentA)->get(route('resident.bills.index'))
            ->assertOk()->assertSee($documentA->original_filename)->assertDontSee($documentB->original_filename);
        $this->get(route('resident.bills.preview', $documentA))->assertOk();
        $this->get(route('resident.bills.download', $documentA))->assertOk();
        $this->get(route('resident.bills.preview', $documentB))->assertNotFound();
        $this->get(route('resident.bills.download', $documentB))->assertNotFound();
    }

    public function test_non_secretary_cannot_open_society_contact_directory(): void
    {
        $society = $this->makeSociety('Access Control Society', 'access-control@test.com');
        $resident = $this->makeUser($society, 'resident', ['email' => 'access-resident@test.com']);

        $this->actingAs($resident)->get(route('maintenance.documents.directory'))->assertForbidden();
    }

    public function test_secretary_can_open_contact_directory_and_pdf_upload_is_removed(): void
    {
        $society = $this->makeSociety('Directory Society', 'directory-test@test.com');
        $secretary = $this->admin($society);
        $resident = $this->makeUser($society, 'resident', [
            'name' => 'Directory Resident',
            'email' => 'directory-resident@test.com',
            'phone' => '+919876543210',
        ]);
        $this->makeFlat($society, ['number' => 'DIR-101', 'owner_id' => $resident->id]);

        $this->actingAs($secretary)->get(route('maintenance.index'))
            ->assertOk()
            ->assertDontSee('Upload Bill PDFs');
        $this->get(route('maintenance.documents.directory', ['search' => 'Directory Resident']))
            ->assertOk()
            ->assertSee('Directory Resident')
            ->assertSee('tel:+919876543210')
            ->assertDontSee('Upload bills');
        $this->get('/maintenance/bills/bulk')->assertNotFound();
        $this->post('/maintenance/bills/bulk')->assertMethodNotAllowed();
    }

    public function test_directory_shows_contact_actions_for_accounts_without_phone_or_email(): void
    {
        $society = $this->makeSociety('Contact Actions Society', 'contact-actions@test.com');
        $secretary = $this->admin($society);
        $resident = $this->makeUser($society, 'resident', [
            'name' => 'No Contact Resident',
            'email' => '',
            'phone' => null,
        ]);
        $flat = $this->makeFlat($society, ['number' => 'CONTACT-101', 'owner_id' => $resident->id]);

        $this->actingAs($secretary)
            ->get(route('maintenance.documents.directory'))
            ->assertOk()
            ->assertSee('No Contact Resident')
            ->assertSee(route('communication.messages.compose', ['recipient_id' => $resident->id]), false)
            ->assertSee('aria-label="Resident details unavailable for No Contact Resident"', false)
            ->assertSee('aria-label="Message No Contact Resident"', false)
            ->assertSee('aria-label="Email unavailable for No Contact Resident"', false)
            ->assertDontSee('No contact method');
    }

    public function test_unlinked_flat_can_open_add_owner_form_with_flat_preselected(): void
    {
        $society = $this->makeSociety('Unlinked Flat Society', 'unlinked-flat@test.com');
        $secretary = $this->admin($society);
        $flat = $this->makeFlat($society, ['number' => 'UNLINKED-101']);

        $this->actingAs($secretary)
            ->get(route('residents.create', ['flat_id' => $flat->id]))
            ->assertOk()
            ->assertSee('value="'.$flat->id.'" selected', false);
    }

    private function makeFlat(Society $society, array $attributes = []): Flat
    {
        $tower = Tower::firstOrCreate(
            ['society_id' => $society->id, 'code' => 'DOC'],
            ['name' => 'Document Tower', 'total_floors' => 2, 'units_per_floor' => 5, 'status' => 'active'],
        );

        return Flat::create(array_merge([
            'society_id' => $society->id,
            'tower_id' => $tower->id,
            'number' => 'D-101',
            'status' => 'occupied',
        ], $attributes));
    }

    private function makeDocument(Society $society, Flat $flat, User $resident): MaintenanceBillDocument
    {
        return MaintenanceBillDocument::create([
            'society_id' => $society->id,
            'flat_id' => $flat->id,
            'resident_user_id' => $resident->id,
            'uploaded_by' => $this->admin($society)->id,
            'period' => '2026-10',
            'storage_path' => 'maintenance-bills/'.$society->id.'/'.uniqid('', true).'.pdf',
            'original_filename' => 'bill-'.$flat->number.'.pdf',
            'file_size' => 18,
            'status' => 'available',
            'published_at' => now(),
        ]);
    }

}
