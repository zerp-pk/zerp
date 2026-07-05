<?php

use App\Http\Controllers\UpdaterController;
use Illuminate\Support\Facades\Route;

Route::prefix('updater')->name('updater.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [UpdaterController::class, 'index'])->name('index');
    Route::post('/update', [UpdaterController::class, 'update'])->name('update');
});