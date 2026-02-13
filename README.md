# Larasync – Laravel Database Sync with UI Dashboard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mrbohem/larasync.svg?style=flat-square)](https://packagist.org/packages/mrbohem/larasync)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mrbohem/larasync/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mrbohem/larasync/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mrbohem/larasync/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mrbohem/larasync/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mrbohem/larasync.svg?style=flat-square)](https://packagist.org/packages/mrbohem/larasync)

**Larasync** is a Laravel package that lets you **compare and sync data between two databases** through a beautiful Livewire-powered web dashboard. Connect any combination of SQLite, MySQL, or PostgreSQL databases, see a side-by-side row-count comparison for every table, and sync individual tables or all tables at once — right from your browser.

---

## ✨ Features

- 🔌 **Connect any two databases** — SQLite, MySQL, or PostgreSQL
- 📊 **Side-by-side table comparison** — row counts, diff, and sync status at a glance
- 🔄 **One-click sync** — sync a single table or all tables sequentially
- ↔️ **Bi-directional sync** — choose `DB1 → DB2` or `DB2 → DB1`
- 🏷️ **Auto-labeling** — databases are auto-detected as *Local* or *Cloud*
- 🚫 **Ignored tables** — exclude tables (e.g. `sessions`, `telescope_*`) from comparison & sync
- 🖥️ **Beautiful Livewire dashboard** — real-time progress, logs, and status indicators
- ⚙️ **Zero config needed** — works out of the box with `.env` variables

---

## 📋 Requirements

- PHP **≥ 8.3**
- Laravel **11.x** or **12.x**
- Livewire **3.6+**

---

## 🚀 Installation

### 1. Install the package via Composer

```bash
composer require mrbohem/larasync --dev
```

### 2. (Optional) Publish the config file

```bash
php artisan vendor:publish --tag="larasync-config"
```

This creates `config/larasync.php` where you configure your two database connections and ignored tables.

### 3. (Optional) Publish the views

If you want to customize the dashboard UI:

```bash
php artisan vendor:publish --tag="larasync-views"
```

---

## ⚙️ Configuration

After publishing, open `config/larasync.php` and configure your two databases. You can set values directly or use `.env` variables:

### Config File

```php
return [
    'db1' => [
        'driver'   => env('LARASYNC_DB1_DRIVER', 'sqlite'),
        'host'     => env('LARASYNC_DB1_HOST'),
        'port'     => env('LARASYNC_DB1_PORT', '3306'),
        'database' => env('LARASYNC_DB1_DATABASE'),
        'username' => env('LARASYNC_DB1_USERNAME'),
        'password' => env('LARASYNC_DB1_PASSWORD', ''),
    ],

    'db2' => [
        'driver'   => env('LARASYNC_DB2_DRIVER', 'mysql'),
        'host'     => env('LARASYNC_DB2_HOST'),
        'port'     => env('LARASYNC_DB2_PORT', '3306'),
        'database' => env('LARASYNC_DB2_DATABASE'),
        'username' => env('LARASYNC_DB2_USERNAME'),
        'password' => env('LARASYNC_DB2_PASSWORD', ''),
    ],

    'ignored_tables' => [
        'sessions',
        'telescope_entries_tags',
        'telescope_entries',
        'telescope_monitoring',
        'pulse_entries',
        'pulse_values',
        'pulse_aggregates',
    ],
];
```

### `.env` Example

```dotenv
# ── Database 1 (e.g. local SQLite) ──
LARASYNC_DB1_DRIVER=sqlite
LARASYNC_DB1_DATABASE=database.sqlite

# ── Database 2 (e.g. remote MySQL) ──
LARASYNC_DB2_DRIVER=mysql
LARASYNC_DB2_HOST=your-cloud-db-host.com
LARASYNC_DB2_PORT=3306
LARASYNC_DB2_DATABASE=your_database
LARASYNC_DB2_USERNAME=your_username
LARASYNC_DB2_PASSWORD=your_password
```

> **Note:** For SQLite, the `database` value is resolved relative to Laravel's `database_path()` (i.e. the `database/` directory).

---

## 🖥️ Usage

### Access the Dashboard

Once installed, visit the sync dashboard in your browser:

```
https://your-app.test/sync-db
```

The route is automatically registered by the package at `/sync-db`.

### Dashboard Workflow

1. **Configure connections** — Enter or verify the credentials for DB1 and DB2 (pre-filled from config)
2. **Test connections** — Click the test button for each database to verify connectivity
3. **Compare** — Hit "Compare" to see a table-by-table row-count comparison
4. **Choose sync direction** — Select `DB1 → DB2` or `DB2 → DB1`
5. **Sync** — Sync individual tables with one click, or use "Sync All" to sync every table sequentially

### Supported Drivers

| Driver   | Value      | Notes                        |
|----------|------------|------------------------------|
| SQLite   | `sqlite`   | Always treated as *Local*    |
| MySQL    | `mysql`    | Local or Cloud auto-detected |
| PostgreSQL | `pgsql`  | Local or Cloud auto-detected |

### Ignoring Tables

Add table names to the `ignored_tables` array in `config/larasync.php` to exclude them from comparison and sync:

```php
'ignored_tables' => [
    'sessions',
    'telescope_entries',
    'jobs',
    'failed_jobs',
    // add your tables here...
],
```

---

## ⚠️ Important Notes

- **Sync is destructive** — Syncing a table will **truncate** the target table and replace all its data with the source table's data. Always back up your databases before syncing.
- **Foreign key constraints** are temporarily disabled during sync to avoid constraint violations.
- **Large tables** are synced in chunks of 500 rows for efficiency.
- The dashboard route (`/sync-db`) uses the `web` middleware by default. You may want to add authentication middleware in production — see [Protecting the Dashboard](#-protecting-the-dashboard).

---

## 🔒 Protecting the Dashboard

The `/sync-db` route uses only the `web` middleware by default. In production, you should protect it with authentication. You can do this by adding middleware in your app's route service provider or by overriding the package route:

```php
// In routes/web.php
use MrBohem\Larasync\Http\Livewire\SyncDashboard;

Route::get('sync-db', SyncDashboard::class)
    ->middleware(['web', 'auth', 'admin'])  // add your middleware
    ->name('larasync.dashboard');
```

---

## 🧪 Testing

```bash
composer test
```

---

## 👤 Credits

- [MrBohem](https://github.com/mrbohem)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
