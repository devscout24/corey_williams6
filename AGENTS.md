# AGENTS.md — Laravel POS

## Quick start

```powershell
composer run setup
```

For fresh reseed: `composer install; npm install; copy .env.example .env; php artisan key:generate; php artisan migrate:fresh --seed; npm run build`

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
