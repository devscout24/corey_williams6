# AGENTS.md — Laravel POS

## Quick start (SQLite supported, MySQL `.env.example` default)

```powershell
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
```

Set `POS_SEED_DEMO=true` in `.env` before seeding to load demo data.

## Dev server

| Command | What it runs |
|---|---|
| `composer run dev` | `php artisan serve --host=0.0.0.0 --port=8000` + `app:register-self --port=8000` + `queue:listen --tries=3` + `pail` + Vite, via concurrently |
| `npm run dev` | Vite only (Tailwind v4 + laravel-vite-plugin) |
| `composer run test` | `config:clear` then `php artisan test` |
| `composer run setup` | Full fresh install (composer, .env, key, migrate, npm) |

## Testing

- **PHPUnit** with SQLite `:memory:` — env overrides in `phpunit.xml`.
- Suites: `tests/Unit`, `tests/Feature`.
- Queue forced to `sync` in tests (no worker needed).
- Tests using `RefreshDatabase` must manually `@touch(storage_path('app/install.lock'))` in `setUp()` to pass `EnsureInstalled` middleware (see `tests/Feature/*` examples).
- Run a single test: `php artisan test --filter TestName`

## Auth

- Custom **`employee` guard** (`auth:employee` middleware) backed by `PhpposEmployee` model.
- Default credentials: **admin** / **12345678** (legacy MD5 stored in seeder; auto-upgrades to bcrypt on first login).
- Login: `GET/POST /login`. Logout: `POST /logout` (inside `auth:employee` group).

## Database & migrations

- **SQLite** fallback default (`config/database.php`); `.env.example` has MySQL uncommented — uncomment `DB_CONNECTION=sqlite` to use SQLite explicitly.
- **One create-only migration per `phppos_*` table** — prefer updating the existing create migration and running `migrate:fresh` over adding `Schema::table()` alters. Existing alter migrations exist but don't add new ones.
- Use `foreignId()->constrained()` for FKs. Explicit `cascadeOnDelete`/`nullOnDelete`.
- Seed all baseline data in `database/seeders/` (never in migrations). `PosCoreSeeder` runs always; `PosDemoSeeder` runs only if `POS_SEED_DEMO=true`.
- Validates with: `php artisan migrate:fresh --seed`
- Legacy migrations in `database/migrations/legacy/` (reference only).
- Laravel default migrations (`users`, `cache`, `jobs`) kept as-is.

## Queue

- `QUEUE_CONNECTION=database` default (DB-driven queue). Required for LAN sync.
- Start worker: `php artisan queue:work --sleep=1 --tries=1 --timeout=60`
- NSSM service `LaravelPosQueueWorker` auto-starts worker in production.

## Architecture

- Routes: `routes/web.php` (main POS routes), `routes/api.php` (LAN sync, `sync.auth` middleware), `routes/rony/*` (CRUD bundles for items, people, receiving, sales/inventory — required from `web.php`), `routes/console.php` (custom Artisan commands).
- 65+ Eloquent models in `app/Models/` — most prefixed `phppos_*`, except `Location`, `TransferQueue`, `PriceRule`, `PriceRulePriceBreak`, `PriceTier`, `Attribute`, `AttributeValue`, `ItemVariation`, `User`.
- Two location tables: `phppos_locations` (POS stores) and `locations` (LAN peer registry with `ip`, `port`, `is_self`, `last_seen_at`).
- **Location resolution chain** (`LocationContextService`): ULID header/cookie → requested ID → employee current location → LAN self-record → first location fallback.
- **Setup guard**: `installed` middleware checks for `storage/app/install.lock` — all routes redirect to `/setup` until lock exists.
- Custom middleware aliases registered in `bootstrap/app.php`: `sync.auth`, `installed`, `check_register_open`.
- Storage path overridable via `LARAVEL_STORAGE_PATH` env.
- Frontend: **Blade + jQuery** (not Vue/React). Tailwind CSS v4. Custom assets in `public/assets/`.
- Key composer dependencies: `barryvdh/laravel-dompdf` (PDF), `picqer/php-barcode-generator` (SVG barcodes), `rats/zkteco` (biometric device SDK).

## Code style

- Laravel Pint (`./vendor/bin/pint`) — run before committing.
- EditorConfig: 4-space indent, LF endings, UTF-8.
- Node 26 (`.nvmrc`).

## LAN sync

- `php artisan app:bind-identity` writes `APP_NODE_IP`/`APP_NODE_NAME` to `.env` (defined inline in `routes/console.php`).
- `php artisan app:register-self --port=8000` registers this node in `locations` table (runs automatically in `composer run dev`).
- Discovery + transfers use DB queue + `/api/lan/*` endpoints (`sync.auth` middleware, shared token in `config/sync.php`).
- No polling — manual Sync button in top bar.
- After editing `.env` (IP/name changes), run `php artisan config:clear`.

## Knowledge base

Regenerate after changing routes, controllers, models, migrations, views, or docs:

```powershell
python tools\build_knowledge_base.py
```

Outputs `graphify-out/{graph.json, graph.html, GRAPH_REPORT.md}`. Read `graphify-out/GRAPH_REPORT.md` before structural changes.

## Other docs

- `MIGRATION_HANDOFF.md` — migration handoff rules, history, and pending work log.
- `INSTALLER_BROWSER_MODE.md` — NSSM setup and browser-mode installer details.
- `tools/` — helper scripts for debugging and testing (check state, transfers, announce, etc.).

## CI

- Only branch `night` deploys via FTP (`.github/workflows/night.yml`). No test runner in CI.
