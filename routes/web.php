<?php

use Illuminate\Support\Facades\Route;
use MrBohem\Larasync\Http\Livewire\SyncDashboard;

Route::get('sync-db', SyncDashboard::class)
    ->middleware(['web'])
    ->name('larasync.dashboard');
