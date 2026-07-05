<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->name('installer.')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallerController::class, 'requirements'])->name('requirements');
    Route::get('/permissions', [InstallerController::class, 'permissions'])->name('permissions');
    Route::get('/environment', [InstallerController::class, 'environment'])->name('environment');
    Route::post('/environment', [InstallerController::class, 'environmentStore'])->name('environment.store');
    Route::get('/database', [InstallerController::class, 'database'])->name('database');
    Route::post('/database', [InstallerController::class, 'databaseStore'])->name('database.store');
    // Route::get('/addons', [InstallerController::class, 'addons'])->name('addons');
    // Route::post('/addons', [InstallerController::class, 'addonsStore'])->name('addons.store');
    Route::get('/final', [InstallerController::class, 'final'])->name('final');
});