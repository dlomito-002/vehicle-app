# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**Read `AGENTS.md` first** — it has the project conventions (Form Requests, enums, policies, upload trait, SQLite migration gotchas, domain areas) and is kept up to date by the project owner. This file only adds command references and an architecture map that AGENTS.md doesn't cover.

## Commands

```bash
# Install
composer install
npm install

# Dev servers (Laravel + Vite separately)
php artisan serve
npm run dev

# Or all at once (server + queue listener + logs + vite)
composer run dev

# Build frontend assets
npm run build

# Run all tests
php artisan test

# Run a single test file
php artisan test tests/Feature/VehicleReceptionTest.php

# Run a single test method
php artisan test --filter=test_method_name

# Run a suite
php artisan test --testsuite=Feature

# Format PHP (Laravel Pint)
vendor/bin/pint

# Fresh DB with seed data (destructive — drops all tables)
php artisan migrate:fresh --seed

# Clear config/route/view caches
php artisan optimize:clear

# List registered routes
php artisan route:list
```

Tests use `RefreshDatabase`, so `php artisan test` should never be pointed at a database with data worth keeping. DB is SQLite by default (`database/database.sqlite`).

## Architecture

Laravel 12.69.x, server-rendered Blade + Tailwind + Alpine.js — no SPA/JS framework, no API layer. Everything is a classic controller → Blade view request/response cycle; state lives in the database, not in a JS store.

**Request flow**: `routes/web.php` → controller (`app/Http/Controllers`) → Form Request for validation (`app/Http/Requests`) → Policy for authorization (`app/Policies`, invoked via `$this->authorize()`) → Eloquent model (`app/Models`) → Blade view (`resources/views`). There is no service/repository layer beyond `app/Support/` — controllers talk to models directly.

**Auth**: custom email-OTP login (`app/Http/Controllers/Auth/LoginController.php` + `App\Models\LoginVerificationCode`), not Laravel Breeze/Fortify and not OAuth. Two roles only, `App\Enums\UserRole` (`Admin`/`Agent`), enforced by `app/Http/Middleware/EnsureUserIsAdmin.php` (aliased as `admin` in `bootstrap/app.php`) plus per-resource Policies for ownership checks (an Agent only sees their own receptions/deliveries).

**Core domain flow** (reception → delivery → comparison):
1. `VehicleReceptionController` creates a `VehicleReception` with nested `VehiclePhoto`, `VehicleDocumentation`, `VehicleEquipmentCheck`, `VehicleConditionItem` records.
2. `VehicleDeliveryController` walks vehicle → open-reception selection → damage report → delivery form, closing the reception atomically (a `VehicleReception` can only be closed once).
3. `VehicleComparisonController` uses `App\Support\VehicleComparisonBuilder` to diff reception vs. delivery (mileage, fuel, documentation, checklist, per-position photos), rendered either as a Blade view or, via `App\Support\PdfImageEncoder` + `barryvdh/laravel-dompdf`, as a downloadable PDF (images embedded as data URIs so the PDF has no external dependencies).

File uploads (photos, signatures) all go through the `HandlesVehicleFormUploads` trait (`app/Http/Controllers/Concerns/`), which writes to whichever disk `config('vehicle.photos_disk')` points at (`public` or `cloudinary`) and builds human-readable paths (`flota/{placa-slug}/{recepcion|entrega}-{id}/{posicion}.{ext}`).

Two independent maintenance systems coexist on purpose: a free-text service log (`VehicleService`/`ServiceType`) and a fixed-interval schedule tracker (`VehicleMaintenanceSchedule`/`VehicleMaintenanceCompletion`/`MaintenanceCategory`) that computes due dates from the last completed service per category and fires email alerts synchronously on page visit (no queue/scheduler is wired up — `QUEUE_CONNECTION=database` is configured but unused).

Enums under `App\Enums` are string-backed and drive form options, validation, and report iteration dynamically (e.g. `DocumentType::forReception()`, `PhotoPosition::standardPositions()`) — adding a catalog option means editing the enum, not the views.

## Current collaboration state — 2026-09-22

Read `AGENTS.md` and `CAMBIOS_RAMA_LUIS.md` before changing shared files.

Current `luis/setup-local` baseline:

- Laravel 12.69.x / PHP 8.2.
- OTP email authentication.
- Helpdesk-aligned UI, complete dark mode and global Smart Select.
- PHPUnit isolated with SQLite `:memory:`.
- Known baseline: 60 tests / 194 assertions.

The `Claudio` and `luis/setup-local` branches are diverged. Do not resolve by taking one whole file over the other. Reconcile behavior deliberately, especially in:

- `phpunit.xml`
- `resources/js/app.js`
- reception/delivery create views
- `VehicleDeliveryController`
- upload/filesystem configuration
- seeders and flow tests

Cross-platform rule: application code and tests must work on both Windows and Ubuntu. `MAIN.bat` is optional Windows tooling only.

Before proposing a merge:

```bash
composer install
npm ci
php artisan optimize:clear
npm run build
php artisan test
composer validate --strict
git diff --check
```
