<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminManagementController;

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/create-admin', [AdminManagementController::class, 'create'])->name('superadmin.createAdmin');
    Route::post('/create-admin', [AdminManagementController::class, 'store'])->name('superadmin.storeAdmin');
});

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';
