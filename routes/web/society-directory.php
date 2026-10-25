<?php

use App\Http\Controllers\SocietyDirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('society-directory', [SocietyDirectoryController::class, 'index'])
    ->name('society-directory.index');
