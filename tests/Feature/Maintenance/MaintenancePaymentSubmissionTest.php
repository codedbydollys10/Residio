<?php

declare(strict_types=1);

namespace Tests\Feature\Maintenance;

use App\Models\MaintenanceBill;
use App\Models\MaintenancePaymentSubmission;
use App\Models\Society;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenancePaymentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_resident_submission_waits_for_staff_approval_before_recording_payment(): void
    {
        $society = $this->makeSociety('Payment Submission Society', 'payment-submission@test.com');
        $admin = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');
        $bill = $this->makeBill($society, $resident);

        $this->actingAs($resident)
            ->post(route('maintenance.payment-submissions.store', $bill), [
                'amount' => 400,
                'method' => 'upi',
                'reference' => 'UPI-REFERENCE-400',
            ])
            ->assertRedirect(route('maintenance.bills.show', $bill));

        $submission = MaintenancePaymentSubmission::firstOrFail();
        $this->assertSame('pending', $submission->status);
        $this->assertSame(0.0, $bill->refresh()->paid_amount);

        $this->get(route('maintenance.bills.show', $bill))
            ->assertOk()
            ->assertSee('Resident payment submissions')
            ->assertSee('pending')
            ->assertSee('UPI-REFERENCE-400');

        $this->actingAs($admin)
            ->get(route('maintenance.bills.show', $bill))
            ->assertOk()
            ->assertSee(route('maintenance.payment-submissions.approve', [$bill, $submission]), false)
            ->assertSee('Approve');

        $this->actingAs($admin)
            ->post(route('maintenance.payment-submissions.approve', [$bill, $submission]))
            ->assertRedirect(route('maintenance.bills.show', $bill));

        $this->assertSame('approved', $submission->refresh()->status);
        $this->assertSame(400.0, $bill->refresh()->paid_amount);
        $this->assertDatabaseHas('maintenance_payments', [
            'maintenance_bill_id' => $bill->id,
            'amount' => 400,
            'method' => 'upi',
            'reference' => 'UPI-REFERENCE-400',
            'recorded_by' => $admin->id,
        ]);
    }

    public function test_resident_cannot_submit_payment_for_another_residents_bill(): void
    {
        $society = $this->makeSociety('Private Payment Society', 'private-payment@test.com');
        $resident = $this->makeUser($society, 'resident');
        $otherResident = $this->makeUser($society, 'resident');
        $bill = $this->makeBill($society, $otherResident);

        $this->actingAs($resident)
            ->post(route('maintenance.payment-submissions.store', $bill), [
                'amount' => 100,
                'method' => 'bank_transfer',
                'reference' => 'NOT-MY-BILL',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('maintenance_payment_submissions', 0);
    }

    public function test_staff_rejection_does_not_change_the_bill_balance(): void
    {
        $society = $this->makeSociety('Rejected Payment Society', 'rejected-payment@test.com');
        $admin = $this->admin($society);
        $resident = $this->makeUser($society, 'resident');
        $bill = $this->makeBill($society, $resident);
        $submission = MaintenancePaymentSubmission::create([
            'society_id' => $society->id,
            'maintenance_bill_id' => $bill->id,
            'user_id' => $resident->id,
            'amount' => 400,
            'method' => 'bank_transfer',
            'reference' => 'BANK-REFERENCE-400',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('maintenance.payment-submissions.reject', [$bill, $submission]), [
                'review_notes' => 'Could not verify the transfer.',
            ])
            ->assertRedirect(route('maintenance.bills.show', $bill));

        $this->assertSame('rejected', $submission->refresh()->status);
        $this->assertSame('Could not verify the transfer.', $submission->review_notes);
        $this->assertSame(0.0, $bill->refresh()->paid_amount);
        $this->assertDatabaseCount('maintenance_payments', 0);
    }

    private function makeBill(Society $society, User $resident): MaintenanceBill
    {
        return MaintenanceBill::create([
            'society_id' => $society->id,
            'bill_number' => 'PAY-'.uniqid(),
            'user_id' => $resident->id,
            'period' => '2026-10',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);
    }
}
