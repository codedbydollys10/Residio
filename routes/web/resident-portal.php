<?php

use App\Http\Controllers\Residents\ResidentPortalController;
use Illuminate\Support\Facades\Route;

Route::get('resident-portal', [ResidentPortalController::class, 'index'])
    ->name('resident.portal');
Route::middleware('feature:communication')->group(function () {
    Route::get('resident-portal/messages', [ResidentPortalController::class, 'messages'])
        ->name('resident.portal.messages');
    Route::post('resident-portal/messages', [ResidentPortalController::class, 'sendMessage'])
        ->name('resident.portal.messages.send');
});
