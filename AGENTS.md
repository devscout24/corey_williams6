# AGENTS.md — Laravel POS

## Quick start

```powershell
composer run setup
```

Fresh reseed: `composer install && npm install && copy .env.example .env && php artisan key:generate && php artisan migrate:fresh --seed && npm run build`

Set `POS_SEED_DEMO=true` in `.env` before seeding to load demo data.

## Dev commands

| Command | Runs |
|---|---|
| `composer run dev` | `php artisan serve --host=0.0.0.0 --port=8000` + `app:register-self --port=8000` + `queue:listen --tries=3 --timeout=0` + `pail --timeout=0` + Vite (concurrently) |
| `npm run dev` | Vite only |
| `composer run test` | `config:clear` then `php artisan test` |
| `composer run setup` | `composer install`, copy `.env`, key:generate, `migrate --force`, `npm install && npm run build` |
| `php artisan test --filter TestName` | Single test |
| `./vendor/bin/pint` | Laravel Pint (no config) |

## Requirements

- **PHP ^8.3**, Laravel 12, Node 26 (`.nvmrc`).
- Default `DB_CONNECTION=sqlite` (`config/database.php`). `.env.example` has MySQL uncommented.
- `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `BROADCAST_CONNECTION=log`.

## Testing

- PHPUnit with SQLite `:memory:`, QUEUE_CONNECTION=sync, CACHE_STORE=array, SESSION_DRIVER=array, BCRYPT_ROUNDS=4 (see `phpunit.xml`).
- Tests with `RefreshDatabase` must `touch(storage_path('app/install.lock'))` in `setUp()` for `EnsureInstalled` middleware.

## Auth

- Custom **`employee` guard** (`auth:employee`, `config/auth.php`) backed by `PhpposEmployee`.
- Employee password broker uses `phppos_employees_reset_password` table.
- Default: **admin** / **12345678** (MD5 in seeder; auto-upgrades to bcrypt on login).

## Routes

- `routes/web.php` (POS) requires `routes/rony/*` (4 route files: `items_module.php`, `sales_inventory_modules.php`, `receive_return_module.php`, `people_modules.php`).
- All module routes use `check_module_permission` middleware for role-based access.
- `routes/api.php` (LAN sync, `sync.auth` middleware).
- `routes/console.php` (`app:bind-identity` inline, `inspire`).
- **~25 controllers** in `app/Http/Controllers/`; views in `resources/views/` (25 subdirs). Frontend: Blade + jQuery, Tailwind CSS v4, Vite with `@tailwindcss/vite`.

## Database & migrations

- **~69 Eloquent models** in `app/Models/`, most prefixed `phppos_*`.
- **Two location tables:** `phppos_locations` (POS stores), `locations` (LAN peer registry).
- **Migration rule:** Prefer `Schema::create()` for `phppos_*` tables. Existing `Schema::table()` alter migrations exist in `database/migrations/` — do **not** add new ones. Update the single create migration and run `migrate:fresh` instead.
- **Seeders:** `PosCoreSeeder` runs always; `PosDemoSeeder` only if `POS_SEED_DEMO=true`.
- **Legacy migrations** are archived in `database/migrations/legacy/` — reference only.

## Architecture

- **Location resolution** (`LocationContextService`): ULID header/cookie → requested ID → employee location → LAN self-record → first location fallback.
- **Install guard:** `EnsureInstalled` checks `storage/app/install.lock` — all routes redirect to `/setup` until lock exists.
- **Middleware aliases** (`bootstrap/app.php`): `sync.auth` (SyncAuth), `installed` (EnsureInstalled), `check_register_open` (CheckRegisterOpen — required for sales routes), `check_module_permission` (CheckModulePermission — gates routes by employee module permission).
- **Module permission system:** `CheckModulePermission` middleware (`app/Http/Middleware/CheckModulePermission.php`) gates routes by employee module access. Supports comma-separated module names — access granted if employee has any of them. Sidebar in `resources/views/layouts/sidebar.blade.php` uses `hasModulePermission()` to conditionally render nav items.
- **Dashboard:** `ModuleController::index()` displays a grid of permitted modules with sales charts (weekly/monthly/yearly) and stats.
- **Storage path** overridable via `LARAVEL_STORAGE_PATH` env.
- **Register flow:** sales routes require an open register session (`check_register_open` middleware). Open/close routes bypass this middleware. Reconciliation routes use `check_module_permission:reconciliation` (register already closed).
- **Key deps:** `barryvdh/laravel-dompdf` (PDF), `picqer/php-barcode-generator` (SVG barcodes), `rats/zkteco` (biometric SDK).
- **Console commands:** `app:register-self` (`app/Console/Commands/RegisterSelf.php`), `app:bind-identity` (inline in `routes/console.php`).
- **LAN sync jobs:** `AnnouncePresence`, `SendItem` in `app/Jobs/`. Sync controller at `app/Http/Controllers/Sync/TransferSyncController.php`.

## Quantity sync pattern

When updating an item's quantity, **always update both** `phppos_items.default_quantity` **and** `phppos_location_items.quantity` for the employee's current location. See `ItemController::quickUpdate()` and `ItemController::saveItem()` for reference.

## Two-layer inventory (Purchases, Returns, Transfers)

- **Top layer (display/audit):** Kit rows in `phppos_receiving_items` / `phppos_transfer_items` have `item_id = null, item_kit_id = <kitId>`.
- **Bottom layer (inventory movement):** Kits are **exploded** into component items before adjusting inventory on `phppos_location_items`. See `ReceivingController::explodeKitComponents()` / `TransferController::explodeKitForTransfer()`.
- Kit `default_quantity` on `phppos_item_kits` is incremented/decremented to track kit stock.

### Transfer-specific rules

- Transfer out: decrement kit `default_quantity`; transfer in/receiving: increment.
- Lifecycle in `InventoryFlowService` (`createTransferOut`, `completeTransferOut`, `importTransferIn`, etc.).
- `TransferController::edit()` restores cart from saved `phppos_transfer_items`.

### LAN transfer — item/kit auto-creation

Receiver resolution order (`LanController::receive`, `TransferSyncController::receiveTransferOut`):
1. `item_id` → 2. `item_number` → 3. `product_id` → 4. `name` → 5. **auto-create** `PhpposItem` with provided metadata
6. For kit headers, auto-create `PhpposItemKit` with its `components` array if component items exist locally

## Orders

- **Orders module** (`OrderController`) is a submodule of `items` permission, routes in `routes/web.php:84-97`. Supports CRUD, pull list, item search, CSV/XLS export, close, print, and destroy.

## VAT Report

- Available at `/reports/vat` under `reports` permission. Handled by `ReportController::vatIndex()`.
- Output tax view (`resources/views/reports/output_tax.blade.php`) has three manual input fields in the Input Tax section header: **Claimable**, **VAT Non-Inventory**, **VAT on Electricity**. Only **VAT on Electricity** is wired with JS to update the electricity rows and recalculate totals. **Claimable** and **VAT Non-Inventory** are placeholders — yet to be implemented.

## LAN sync

- `php artisan app:bind-identity` writes `APP_NODE_IP`/`APP_NODE_NAME` to `.env` (run once per device).
- `php artisan app:register-self --port=8000` registers node in `locations` table (auto-runs in `composer run dev`).
- Discovery + transfers via DB queue + `/api/lan/*` (`sync.auth` middleware, shared token in `config/sync.php`).
- No polling — manual Sync button in top bar.
- After editing `.env`, run `php artisan config:clear`.

## Knowledge base

After changing routes, controllers, models, migrations, views, or docs:

```powershell
python tools\build_knowledge_base.py
```

Outputs `graphify-out/{graph.json, graph.html, GRAPH_REPORT.md}`. Read `GRAPH_REPORT.md` before structural changes.

## Other docs

- **`MIGRATION_HANDOFF.md`** — migration rules and pending tasks (history section is reference only).
- **`INSTALLER_BROWSER_MODE.md`** — NSSM service setup.
- **`README.md`** — LAN sync debug guide only.
- **`tools/`** — helper scripts for debugging/testing.

## CI

- Only branch `night` deploys via FTP (`.github/workflows/night.yml`). No test runner.

## Agent plans

Save multi-step task plans to `.opencode/plans/` for continuity across sessions.
