<?php

use Illuminate\Support\Facades\Route;
use MrBohem\Larasync\Http\Livewire\SyncDashboard;
use MrBohem\Larasync\Http\Livewire\Settings;

Route::middleware(['web'])->group(function () {
    Route::get('sync-db', SyncDashboard::class)->name('larasync.dashboard');
    Route::get('sync-db/settings', Settings::class)->name('larasync.settings');
});
