<?php

use MrBohem\Larasync\Services\ConnectionLabelService;

beforeEach(function () {
    $this->service = new ConnectionLabelService();
});

// ── getLabel() ─────────────────────────────────────────────────

it('returns Local for sqlite driver', function () {
    expect($this->service->getLabel('sqlite', null))->toBe('Local');
});

it('returns Local for sqlite driver even with remote host', function () {
    expect($this->service->getLabel('sqlite', 'db.example.com'))->toBe('Local');
});

it('returns Local for localhost host', function () {
    expect($this->service->getLabel('mysql', 'localhost'))->toBe('Local');
});

it('returns Local for 127.0.0.1 host', function () {
    expect($this->service->getLabel('mysql', '127.0.0.1'))->toBe('Local');
});

it('returns Local for ::1 ipv6 loopback', function () {
    expect($this->service->getLabel('pgsql', '::1'))->toBe('Local');
});

it('returns Local for empty host string', function () {
    expect($this->service->getLabel('mysql', ''))->toBe('Local');
});

it('returns Local for null host', function () {
    expect($this->service->getLabel('mysql', null))->toBe('Local');
});

it('returns Cloud for remote host', function () {
    expect($this->service->getLabel('mysql', 'db.example.com'))->toBe('Cloud');
});

it('returns Cloud for pgsql with remote host', function () {
    expect($this->service->getLabel('pgsql', 'cloud.example.com'))->toBe('Cloud');
});

it('is case-insensitive for localhost detection', function () {
    expect($this->service->getLabel('mysql', 'LOCALHOST'))->toBe('Local');
    expect($this->service->getLabel('mysql', 'LocalHost'))->toBe('Local');
});

it('trims whitespace from host', function () {
    expect($this->service->getLabel('mysql', ' 127.0.0.1 '))->toBe('Local');
});

// ── buildDisplayName() ─────────────────────────────────────────

it('uses basename for sqlite database', function () {
    $result = $this->service->buildDisplayName('db1', 'sqlite', null, '/path/to/db_one.sqlite');
    expect($result)->toBe('DB1 · db_one.sqlite');
});

it('uses host for network databases', function () {
    $result = $this->service->buildDisplayName('db2', 'mysql', 'cloud.example.com', 'mydb');
    expect($result)->toBe('DB2 · cloud.example.com');
});

it('falls back to database name when no host', function () {
    $result = $this->service->buildDisplayName('db1', 'mysql', null, 'mydb');
    expect($result)->toBe('DB1 · mydb');
});

it('returns plain label when no host or database', function () {
    $result = $this->service->buildDisplayName('db1', 'mysql', null, null);
    expect($result)->toBe('DB1');
});

it('uppercases the prefix', function () {
    $result = $this->service->buildDisplayName('db2', 'sqlite', null, 'test.sqlite');
    expect($result)->toBe('DB2 · test.sqlite');
});

// ── computeLabels() ────────────────────────────────────────────

it('computes matching labels for two local databases', function () {
    $result = $this->service->computeLabels(
        ['driver' => 'sqlite', 'host' => null, 'database' => 'db_one.sqlite'],
        ['driver' => 'sqlite', 'host' => null, 'database' => 'db_two.sqlite']
    );

    expect($result['db1_label'])->toBe('Local')
        ->and($result['db2_label'])->toBe('Local')
        ->and($result['labels_match'])->toBeTrue()
        ->and($result['db1_display'])->toBe('DB1 · db_one.sqlite')
        ->and($result['db2_display'])->toBe('DB2 · db_two.sqlite');
});

it('computes matching labels for two cloud databases', function () {
    $result = $this->service->computeLabels(
        ['driver' => 'mysql', 'host' => 'cloud1.example.com', 'database' => 'prod_db1'],
        ['driver' => 'pgsql', 'host' => 'cloud2.example.com', 'database' => 'prod_db2']
    );

    expect($result['db1_label'])->toBe('Cloud')
        ->and($result['db2_label'])->toBe('Cloud')
        ->and($result['labels_match'])->toBeTrue()
        ->and($result['db1_display'])->toBe('DB1 · cloud1.example.com')
        ->and($result['db2_display'])->toBe('DB2 · cloud2.example.com');
});

it('does not disambiguate when labels differ', function () {
    $result = $this->service->computeLabels(
        ['driver' => 'sqlite', 'host' => null, 'database' => ':memory:'],
        ['driver' => 'mysql', 'host' => 'remote.example.com', 'database' => 'prod_db']
    );

    expect($result['db1_label'])->toBe('Local')
        ->and($result['db2_label'])->toBe('Cloud')
        ->and($result['labels_match'])->toBeFalse()
        ->and($result['db1_display'])->toBe('Local')
        ->and($result['db2_display'])->toBe('Cloud');
});
