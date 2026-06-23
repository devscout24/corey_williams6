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

## Testing

- PHPUnit with SQLite `:memory:` — env overrides in `phpunit.xml`.
- Queue forced to `sync` in tests.
- Tests with `RefreshDatabase` must `touch(storage_path('app/install.lock'))` in `setUp()` for `EnsureInstalled` middleware.
- Single: `php artisan test --filter TestName`

## Auth

- Custom **`employee` guard** (`auth:employee`, `config/auth.php`) backed by `PhpposEmployee`.
- Default: **admin** / **12345678** (MD5 in seeder; auto-upgrades to bcrypt on login).

## Database & migrations

- Default `DB_CONNECTION=sqlite` (`config/database.php`). `.env.example` has MySQL uncommented.
- **`Schema::create()` only** for `phppos_*` tables — update the existing create migration and `migrate:fresh` rather than adding `Schema::table()` alters. Past alter migrations exist in `database/migrations/` (don't add new ones).
- Seed data in `database/seeders/`. `PosCoreSeeder` runs always; `PosDemoSeeder` only if `POS_SEED_DEMO=true`.
- `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` defaults.

## Quantity sync pattern

When updating an item's quantity, **always update both** `phppos_items.default_quantity` **and** `phppos_location_items.quantity` for the employee's current location. See `ItemController::quickUpdate()` and `ItemController::saveItem()` for reference.

## Queue

- DB-driven queue (`QUEUE_CONNECTION=database`). Required for LAN sync.
- Dev: `queue:listen --tries=3 --timeout=0` (embedded in `composer run dev`).
- Standalone: `php artisan queue:work --sleep=1 --tries=1 --timeout=60`

## Architecture

- **Routes:** `routes/web.php` (POS), `routes/api.php` (LAN sync, `sync.auth` middleware), `routes/rony/*` (CRUD bundles required from `web.php`), `routes/console.php` (`app:bind-identity` inline, `inspire`).
- **~66 Eloquent models** in `app/Models/` — most prefixed `phppos_*`.
- **Two location tables:** `phppos_locations` (POS stores), `locations` (LAN peer registry).
- **Location resolution** (`LocationContextService`): ULID header/cookie → requested ID → employee location → LAN self-record → first location fallback.
- **Install guard:** `EnsureInstalled` checks `storage/app/install.lock` — all routes redirect to `/setup` until lock exists.
- **Middleware aliases** (`bootstrap/app.php`): `sync.auth`, `installed`, `check_register_open`.
- **Storage path** overridable via `LARAVEL_STORAGE_PATH` env.
- **Frontend:** Blade + jQuery, Tailwind CSS v4, custom assets in `public/assets/`.
- **Key deps:** `barryvdh/laravel-dompdf` (PDF), `picqer/php-barcode-generator` (SVG barcodes), `rats/zkteco` (biometric SDK).
- **Console commands:** `app:register-self` (`RegisterSelf.php`), `app:bind-identity` (inline in `routes/console.php`).
- **LAN sync jobs:** `AnnouncePresence`, `SendItem`.

## Two-layer inventory (Purchases, Returns, Transfers)

Kits use a two-layer approach:

- **Top layer (display/audit):** Kit rows in `phppos_receiving_items` / `phppos_transfer_items` have `item_id = null, item_kit_id = <kitId>`. Individual items have `item_id` set.
- **Bottom layer (inventory movement):** Kits are **exploded** into component items before adjusting inventory on `phppos_location_items`. `ReceivingController::explodeKitComponents()` / `TransferController::explodeKitForTransfer()` recursively flatten kits.
- Kit `default_quantity` on `phppos_item_kits` is incremented/decremented to track kit stock.

### Transfer-specific rules

- Transfer out: `default_quantity` on kit is **decremented** on transfer out, **incremented** on transfer in/receiving.
- Complete lifecycle handled by `InventoryFlowService` methods (`createTransferOut`, `completeTransferOut`, `importTransferIn`, etc.).
- `TransferController::edit()` restores cart from saved `phppos_transfer_items`.

### LAN transfer — item/kit auto-creation

When a transfer is sent over LAN, each line carries full metadata (`item_id`, `item_number`, `product_id`, `name`, `cost_price`, `unit_price`, `markup`, `markup_type`, `item_kit_id`, `item_kit_name`, `item_kit_cost_price`, `item_kit_unit_price`, `item_kit_default_quantity`, `components`).

Receiver resolution order (`LanController::receive`, `TransferSyncController::receiveTransferOut`):
1. `item_id` → 2. `item_number` → 3. `product_id` → 4. `name` → 5. **auto-create** `PhpposItem` with provided metadata
6. For kit headers, auto-create `PhpposItemKit` with its `components` array if component items exist locally

## Code style

- Laravel Pint (`./vendor/bin/pint`) — no config, run before committing.
- EditorConfig: 4-space indent, LF endings, UTF-8.
- Node 26 (`.nvmrc`).

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

- **`MIGRATION_HANDOFF.md`** — migration rules and pending migration tasks.
- **`INSTALLER_BROWSER_MODE.md`** — NSSM service setup (web server + worker).
- **`README.md`** — LAN sync debug guide only (ignore NativePHP build config at bottom).
- **`tools/`** — helper scripts for debugging/testing.

## CI

- Only branch `night` deploys via FTP (`.github/workflows/night.yml`). No test runner.
