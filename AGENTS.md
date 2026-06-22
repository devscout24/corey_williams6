# AGENTS.md — Laravel POS

## Quick start

```powershell
composer run setup
```

For fresh reseed: `composer install; npm install; copy .env.example .env; php artisan key:generate; php artisan migrate:fresh --seed; npm run build`

Set `POS_SEED_DEMO=true` in `.env` before seeding to load demo data.

## Dev commands

| Command              | Runs                                                                                                                                                               |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `composer run dev`   | `php artisan serve --host=0.0.0.0 --port=8000` + `app:register-self --port=8000` + `queue:listen --tries=3 --timeout=0` + `pail --timeout=0` + Vite (concurrently) |
| `npm run dev`        | Vite only                                                                                                                                                          |
| `composer run test`  | `config:clear` then `php artisan test`                                                                                                                             |
| `composer run setup` | `composer install`, copy `.env`, key:generate, `migrate --force`, `npm install && npm run build`                                                                   |

## Testing

- PHPUnit with SQLite `:memory:` — env overrides in `phpunit.xml`.
- Queue forced to `sync` in tests.
- Tests using `RefreshDatabase` must `@touch(storage_path('app/install.lock'))` in `setUp()` for `EnsureInstalled` middleware.
- Single: `php artisan test --filter TestName`

## Auth

- Custom **`employee` guard** (`auth:employee`) backed by `PhpposEmployee` (`config/auth.php`).
- Default: **admin** / **12345678** (MD5 in seeder; auto-upgrades to bcrypt on login).

## Database & migrations

- **SQLite** fallback default (`config/database.php`). `.env.example` has MySQL uncommented — set `DB_CONNECTION=sqlite` for SQLite.
- **Prefer `Schema::create()` only** for `phppos_*` tables — update the existing create migration and `migrate:fresh` rather than adding `Schema::table()` alters. Past alter migrations exist in `database/migrations/`; don't add new ones.
- Use `foreignId()->constrained()` with explicit `cascadeOnDelete`/`nullOnDelete`.
- Seed data in `database/seeders/` only. `PosCoreSeeder` runs always; `PosDemoSeeder` only if `POS_SEED_DEMO=true`.
- Validate: `php artisan migrate:fresh --seed`
- Legacy migrations in `database/migrations/legacy/` (reference only).
- Laravel stock migrations (`users`, `cache`, `jobs`) kept as-is.
- `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` defaults.

## Queue

- DB-driven queue (`QUEUE_CONNECTION=database`). Required for LAN sync.
- Dev server: `queue:listen --tries=3 --timeout=0` (embedded in `composer run dev`).
- Standalone: `php artisan queue:work --sleep=1 --tries=1 --timeout=60`
- Production NSSM services: `LaravelPosWeb` (built-in PHP server) + `LaravelPosQueueWorker` (worker). See `INSTALLER_BROWSER_MODE.md`.

## Architecture

- **Routes:** `routes/web.php` (POS routes), `routes/api.php` (LAN sync, `sync.auth` middleware), `routes/rony/*` (CRUD bundles for items, people, receiving, sales/inventory — required from `web.php`), `routes/console.php` (`app:bind-identity` command defined inline, `inspire`).
- **66 Eloquent models** in `app/Models/` — most prefixed `phppos_*`. Non-prefixed: `Location`, `TransferQueue`, `PriceRule`, `PriceRulePriceBreak`, `PriceTier`, `Attribute`, `AttributeValue`, `ItemVariation`, `User`, `Notification`.
- **Two location tables:** `phppos_locations` (POS stores), `locations` (LAN peer registry with `ip`, `port`, `is_self`, `last_seen_at`).
- **Location resolution** (`LocationContextService`): ULID header/cookie → requested ID → employee current location → LAN self-record → first location fallback.
- **Install guard:** `EnsureInstalled` middleware checks `storage/app/install.lock` — all routes redirect to `/setup` until lock exists.
- **Middleware aliases** (registered in `bootstrap/app.php`): `sync.auth`, `installed`, `check_register_open`.
- **Storage path** overridable via `LARAVEL_STORAGE_PATH` env.
- **Frontend:** Blade + jQuery, Tailwind CSS v4, custom assets in `public/assets/`.
- **Key deps:** `barryvdh/laravel-dompdf` (PDF), `picqer/php-barcode-generator` (SVG barcodes), `rats/zkteco` (biometric SDK), `nativephp-laravel` (desktop app).
- **LAN sync apps:** `announce:presence` (`app/Jobs/AnnouncePresence.php`), `send:item` (`app/Jobs/SendItem.php`). Only console command: `RegisterSelf` (`app:register-self`).

## Two-layer inventory mechanism (Purchases, Returns, Transfers)

Purchases, returns, sales, and transfers all use a **two-layer approach** for handling item kits:

### Layer 1 — Top layer (Display / Audit trail)

- Kits are shown as-is in the cart UI and in `PhpposReceivingItem` / `PhpposTransferItem` records.
- Kit header rows have `item_id = null, item_kit_id = <kitId>` so reporting shows which kits were involved.
- Individual items have `item_id` set and no `item_kit_id`.

### Layer 2 — Deeper layer (Inventory movement)

- Kits are **exploded** into their component items before inventory quantities are adjusted.
- `ReceivingController::explodeKitComponents()` / `TransferController::explodeKitForTransfer()` recursively flatten a kit (including nested sub-kits) into individual component items.
- Inventory is adjusted on `phppos_location_items` for each component item.
- Kit `default_quantity` on `phppos_item_kits` is incremented/decremented to track kit stock levels.

### Transfer-specific rules

- `phppos_transfer_items` stores both individual items (`item_id` set) and kit header references (`item_kit_id` set, `item_id` null) — same pattern as `phppos_receivings_items`.
- When a kit is added to a transfer cart, it creates two types of `phppos_transfer_item` rows on save/complete:
    1. **Kit header row** — `item_kit_id` = kit ID, `item_id` = null, `quantity` = kit qty (for display/audit).
    2. **Component rows** — `item_id` = component item ID, `item_kit_id` = parent kit ID, `quantity` = exploded qty (for inventory movement).
- `default_quantity` on `phppos_item_kits` is **decremented on transfer out** and **incremented on transfer in / receiving** (mirrors return/purchase behaviour).
- The `InventoryFlowService` methods (`createTransferOut`, `completeTransferOut`, `importTransferIn`, etc.) carry through `item_kit_id` and `item_kit_name` so kit metadata is preserved across the full transfer lifecycle (out → sync → in → receive).
- `TransferController::edit()` restores the cart from saved `phppos_transfer_items`, reconstructing both individual items and kit entries.

### LAN transfer exchange — item/kit metadata & auto-creation

When a transfer is sent over LAN (`SendItem` / `TransferSyncController::exportTransferOut`), each line carries full metadata so the receiver can work with items that don't exist locally:

| Field | Source | Purpose |
|---|---|---|
| `item_id` | `phppos_transfer_items` | Primary lookup |
| `item_number` | `PhpposItem::item_number` | Fallback lookup |
| `product_id` | `PhpposItem::product_id` | Fallback lookup |
| `name` | `PhpposItem::name` / `item_kit_name` | Display + auto-creation |
| `cost_price` | `PhpposItem::cost_price` | Auto-creation |
| `unit_price` | `PhpposItem::unit_price` | Auto-creation |
| `markup` | `PhpposItem::markup` | Auto-creation |
| `markup_type` | `PhpposItem::markup_type` | Auto-creation |
| `item_kit_id` | `phppos_transfer_items` | Kit reference |
| `item_kit_name` | `phppos_transfer_items` | Kit display + auto-creation |
| `item_kit_cost_price` | `PhpposItemKit::cost_price` | Kit auto-creation |
| `item_kit_unit_price` | `PhpposItemKit::unit_price` | Kit auto-creation |
| `item_kit_default_quantity` | `PhpposItemKit::default_quantity` | Kit auto-creation |
| `components` | `phppos_item_kit_items` / `phppos_item_kit_item_kits` | Kit structure recreation |

**On the receiver** (`LanController::receive`, `TransferSyncController::receiveTransferOut`), item resolution order:
1. Look up by `item_id`
2. Look up by `item_number`
3. Look up by `product_id`
4. Look up by `name` (fallback — catches items created independently with same name but different identifiers)
5. If still not found, **auto-create** a `PhpposItem` with the provided metadata (name, item_number, product_id, cost_price, unit_price, markup, markup_type)
5. For kit header rows, auto-create a minimal `PhpposItemKit` with the provided kit metadata and its `components` array to recreate the kit structure (`phppos_item_kit_items` records) if the component items exist locally

This ensures transfers always complete successfully even when items/kits exist only on the sender. The `quantity > 0` filter prevents zero-quantity component rows from blocking delivery.

## Code style

- Laravel Pint (`./vendor/bin/pint`) — no local config, uses defaults. Run before committing.
- EditorConfig: 4-space indent, LF endings, UTF-8.
- Node 26 (`.nvmrc`).

## LAN sync

- `php artisan app:bind-identity` writes `APP_NODE_IP`/`APP_NODE_NAME` to `.env`.
- `php artisan app:register-self --port=8000` registers this node in `locations` table (auto-runs in `composer run dev`).
- Discovery + transfers via DB queue + `/api/lan/*` endpoints (`sync.auth` middleware, shared token in `config/sync.php`).
- No polling — manual Sync button in top bar.
- After editing `.env`, run `php artisan config:clear`.

## Knowledge base

After changing routes, controllers, models, migrations, views, or docs:

```powershell
python tools\build_knowledge_base.py
```

Outputs `graphify-out/{graph.json, graph.html, GRAPH_REPORT.md}`. Read `GRAPH_REPORT.md` before structural changes.

## Other docs

- **`MIGRATION_HANDOFF.md`** — migration rules, completed work history, pending tasks.
- **`INSTALLER_BROWSER_MODE.md`** — NSSM service setup for web server + worker.
- **`README.md`** — LAN sync debug guide (not a real project README; has leftover NativePHP build config at the bottom).
- **`tools/`** — helper scripts for debugging and testing.

## CI

- Only branch `night` deploys via FTP (`.github/workflows/night.yml`). No test runner.
