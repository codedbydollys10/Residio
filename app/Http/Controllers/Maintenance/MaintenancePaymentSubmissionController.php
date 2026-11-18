<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceBill;
use App\Models\MaintenancePaymentSubmission;
use App\Services\Maintenance\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenancePaymentSubmissionController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    public function store(Request $request, MaintenanceBill $bill): RedirectResponse
    {
        $this->authorizeResidentBill($request, $bill);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:upi,bank_transfer'],
            'reference' => ['required', 'string', 'max:180'],
        ]);

        DB::transaction(function () use ($request, $bill, $data): void {
            $lockedBill = MaintenanceBill::query()->lockForUpdate()->findOrFail($bill->id);
            $this->authorizeResidentBill($request, $lockedBill);

            if (in_array($lockedBill->status, ['paid', 'cancelled'], true)
                || (float) $data['amount'] > $lockedBill->balance) {
                throw ValidationException::withMessages([
                    'amount' => 'The submitted amount cannot exceed the current bill balance.',
                ]);
            }

            MaintenancePaymentSubmission::create([
                'society_id' => $lockedBill->society_id,
                'maintenance_bill_id' => $lockedBill->id,
                'user_id' => $request->user()->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => trim($data['reference']),
                'status' => 'pending',
            ]);
        });

        return redirect()->route('maintenance.bills.show', $bill)
            ->with('success', 'Payment details submitted. Your society must verify the transfer before the bill is marked paid.');
    }

    public function approve(Request $request, MaintenanceBill $bill, MaintenancePaymentSubmission $submission): RedirectResponse
    {
        $this->authorize('collect', $bill);
        $this->ensureSubmissionBelongsToBill($submission, $bill);

        DB::transaction(function () use ($request, $bill, $submission): void {
            $lockedSubmission = MaintenancePaymentSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $lockedBill = MaintenanceBill::query()->lockForUpdate()->findOrFail($bill->id);
            $this->ensureSubmissionBelongsToBill($lockedSubmission, $lockedBill);
            $this->ensurePending($lockedSubmission);

            if (in_array($lockedBill->status, ['paid', 'cancelled'], true)
                || $lockedSubmission->amount > $lockedBill->balance) {
                throw ValidationException::withMessages([
                    'submission' => 'The submitted amount no longer matches the outstanding bill balance. Review the bill before approving.',
                ]);
            }

            $this->billing->recordPayment(
                $lockedBill,
                $lockedSubmission->amount,
                $lockedSubmission->method,
                $lockedSubmission->reference,
                now()->toDateTimeString(),
                'Resident-submitted payment reference verified by society staff.',
            );

            $lockedSubmission->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('maintenance.bills.show', $bill)
            ->with('success', 'Payment reference approved and payment recorded.');
    }

    public function reject(Request $request, MaintenanceBill $bill, MaintenancePaymentSubmission $submission): RedirectResponse
    {
        $this->authorize('collect', $bill);
        $this->ensureSubmissionBelongsToBill($submission, $bill);
        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $bill, $submission, $data): void {
            $lockedSubmission = MaintenancePaymentSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            $this->ensureSubmissionBelongsToBill($lockedSubmission, $bill);
            $this->ensurePending($lockedSubmission);

            $lockedSubmission->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
            ]);
        });

        return redirect()->route('maintenance.bills.show', $bill)
            ->with('success', 'Payment submission rejected. The bill balance has not changed.');
    }

    private function authorizeResidentBill(Request $request, MaintenanceBill $bill): void
    {
        abort_unless(
            (int) $request->user()->id === (int) $bill->user_id
            && (int) $request->user()->society_id === (int) $bill->society_id,
            404,
        );
    }

    private function ensureSubmissionBelongsToBill(MaintenancePaymentSubmission $submission, MaintenanceBill $bill): void
    {
        abort_unless(
            (int) $submission->maintenance_bill_id === (int) $bill->id
            && (int) $submission->society_id === (int) $bill->society_id,
            404,
        );
    }

    private function ensurePending(MaintenancePaymentSubmission $submission): void
    {
        if ($submission->status !== 'pending') {
            throw ValidationException::withMessages([
                'submission' => 'This payment submission has already been reviewed.',
            ]);
        }
    }
}
