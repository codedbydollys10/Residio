<?php

use App\Http\Controllers\Maintenance\HeadController;
use App\Http\Controllers\Maintenance\MaintenanceController;
use App\Http\Controllers\Maintenance\MaintenanceBillDocumentController;
use Illuminate\Support\Facades\Route;

/*
| Maintenance Billing (web). Mounted inside the authenticated, tenant-scoped
| group defined in routes/web.php. Gated by the "billing" plan feature.
*/
Route::middleware('feature:billing')->group(function () {

    // Dashboard / bills list
    Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('maintenance/documents/directory', [MaintenanceBillDocumentController::class, 'directory'])->name('maintenance.documents.directory');
    Route::get('maintenance/documents/flats/{flatId}', [MaintenanceBillDocumentController::class, 'history'])->whereNumber('flatId')->name('maintenance.documents.history');
    Route::get('maintenance/bill-documents/{document}/preview', [MaintenanceBillDocumentController::class, 'preview'])->name('maintenance.documents.preview');
    Route::get('maintenance/bill-documents/{document}/download', [MaintenanceBillDocumentController::class, 'download'])->name('maintenance.documents.download');
    Route::get('resident/maintenance-bills', [MaintenanceBillDocumentController::class, 'residentBills'])->name('resident.bills.index');
    Route::get('resident/maintenance-bills/{document}/preview', [MaintenanceBillDocumentController::class, 'preview'])->name('resident.bills.preview');
    Route::get('resident/maintenance-bills/{document}/download', [MaintenanceBillDocumentController::class, 'download'])->name('resident.bills.download');
    // Generate bills
    Route::get('maintenance/generate', [MaintenanceController::class, 'generateForm'])->name('maintenance.generate');
    Route::post('maintenance/generate', [MaintenanceController::class, 'generate'])->name('maintenance.generate.post');

    // Export CSV
    Route::get('maintenance/export', [MaintenanceController::class, 'export'])->name('maintenance.export');

    // Maintenance heads (charge definitions)
    Route::resource('maintenance/heads', HeadController::class)
        ->names('maintenance.heads')
        ->parameters(['heads' => 'head']);

    // Bill detail
    Route::get('maintenance/bills/{bill}', [MaintenanceController::class, 'show'])->name('maintenance.bills.show');

    // Record payment
    Route::post('maintenance/bills/{bill}/pay', [MaintenanceController::class, 'recordPayment'])->name('maintenance.pay');

    // Waive
    Route::post('maintenance/bills/{bill}/waive', [MaintenanceController::class, 'waive'])->name('maintenance.waive');

    // Invoice PDF (printable)
    Route::get('maintenance/bills/{bill}/invoice', [MaintenanceController::class, 'invoice'])->name('maintenance.invoice');

    // Receipt (printable)
    Route::get('maintenance/bills/{bill}/receipt/{payment}', [MaintenanceController::class, 'receipt'])->name('maintenance.receipt');
});
