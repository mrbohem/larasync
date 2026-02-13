<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

it('merges the larasync config', function () {
    expect(config('larasync'))->toBeArray()
        ->and(config('larasync'))->toHaveKey('db1')
        ->and(config('larasync'))->toHaveKey('db2')
        ->and(config('larasync'))->toHaveKey('ignored_tables');
});

it('has correct default config values', function () {
    expect(config('larasync.db1.driver'))->toBe('sqlite')
        ->and(config('larasync.db2.driver'))->toBe('mysql')
        ->and(config('larasync.ignored_tables'))->toBeArray()
        ->and(config('larasync.ignored_tables'))->toContain('sessions');
});

it('registers the larasync artisan command', function () {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain('larasync');
});

it('registers the sync dashboard route', function () {
    $route = Route::getRoutes()->getByName('larasync.dashboard');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('sync-db');
});

it('uses web middleware on the dashboard route', function () {
    $route = Route::getRoutes()->getByName('larasync.dashboard');

    expect($route->middleware())->toContain('web');
});
