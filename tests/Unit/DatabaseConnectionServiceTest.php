<?php

use Illuminate\Support\Facades\Config;
use MrBohem\Larasync\Services\DatabaseConnectionService;

beforeEach(function () {
    $this->service = new DatabaseConnectionService();
});

// ── isConfigured() ─────────────────────────────────────────────

it('returns true when driver and database are configured', function () {
    Config::set('larasync.db1.driver', 'mysql');
    Config::set('larasync.db1.database', 'testdb');

    expect($this->service->isConfigured('db1'))->toBeTrue();
});

it('returns false when driver is missing', function () {
    Config::set('larasync.db1.driver', null);
    Config::set('larasync.db1.database', 'testdb');

    expect($this->service->isConfigured('db1'))->toBeFalse();
});

it('returns false when database is missing', function () {
    Config::set('larasync.db1.driver', 'mysql');
    Config::set('larasync.db1.database', null);

    expect($this->service->isConfigured('db1'))->toBeFalse();
});

it('returns false when both driver and database are missing', function () {
    Config::set('larasync.db1.driver', null);
    Config::set('larasync.db1.database', null);

    expect($this->service->isConfigured('db1'))->toBeFalse();
});

// ── buildConfig() ──────────────────────────────────────────────

it('builds mysql config with host, port, username, and password', function () {
    $config = $this->service->buildConfig(
        driver: 'mysql',
        host: '127.0.0.1',
        port: '3306',
        database: 'mydb',
        username: 'root',
        password: 'secret'
    );

    expect($config)->toBeArray()
        ->and($config['driver'])->toBe('mysql')
        ->and($config['host'])->toBe('127.0.0.1')
        ->and($config['port'])->toBe('3306')
        ->and($config['database'])->toBe('mydb')
        ->and($config['username'])->toBe('root')
        ->and($config['password'])->toBe('secret')
        ->and($config['charset'])->toBe('utf8mb4');
});

it('builds pgsql config with host, port, username, and password', function () {
    $config = $this->service->buildConfig(
        driver: 'pgsql',
        host: 'localhost',
        port: '5432',
        database: 'pgdb',
        username: 'pguser',
        password: 'pgpass'
    );

    expect($config)->toBeArray()
        ->and($config['driver'])->toBe('pgsql')
        ->and($config['host'])->toBe('localhost')
        ->and($config['port'])->toBe('5432');
});

it('builds sqlite config without host/port and uses database_path()', function () {
    $config = $this->service->buildConfig(
        driver: 'sqlite',
        host: null,
        port: null,
        database: 'database.sqlite',
        username: null,
        password: null
    );

    expect($config)->toBeArray()
        ->and($config['driver'])->toBe('sqlite')
        ->and($config['database'])->toContain('database.sqlite')
        ->and($config)->not->toHaveKey('host')
        ->and($config)->not->toHaveKey('port');
});

it('returns null when driver is empty', function () {
    $config = $this->service->buildConfig(
        driver: '',
        host: 'localhost',
        port: '3306',
        database: 'mydb',
        username: 'root',
        password: 'secret'
    );

    expect($config)->toBeNull();
});

// ── testConnection() ───────────────────────────────────────────

it('returns true for valid sqlite in-memory connection', function () {
    $config = [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ];

    expect($this->service->testConnection($config, 'valid_test'))->toBeTrue();
});

it('returns false for invalid connection config', function () {
    $config = [
        'driver' => 'mysql',
        'host' => '255.255.255.255',
        'port' => '9999',
        'database' => 'nonexistent',
        'username' => 'nobody',
        'password' => 'wrong',
    ];

    expect($this->service->testConnection($config, 'invalid_test'))->toBeFalse();
});

// ── registerConnection() ──────────────────────────────────────

it('registers a connection in Laravel config', function () {
    $config = [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ];

    $this->service->registerConnection('my_temp_conn', $config);

    expect(config('database.connections.my_temp_conn'))->toBe($config);
});
