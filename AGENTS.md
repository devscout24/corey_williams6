# AGENTS.md — Laravel POS

## Quick start (SQLite, no MySQL needed)

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
| `composer run dev` | `php artisan serve --host=0.0.0.0 --port=8000` + `app:register-self --port=8000` + `queue:listen` + `pail` + Vite, concurrently |
| `npm run dev` | Vite only (Tailwind v4 + laravel-vite-plugin) |
| `composer run test` | `config:clear` then `php artisan test` |
| `composer run setup` | Full fresh install (composer, .env, key, migrate, npm) |
| `composer run native:dev` | NativePHP desktop dev via `native:serve` + Vite |

## Testing

- **PHPUnit** with SQLite `:memory:` (see `phpunit.xml` for exact env overrides).
- Suites: `tests/Unit`, `tests/Feature`.
- Queue forced to `sync` in tests (no worker needed).
- Run a single test: `php artisan test --filter TestName`

## Auth

- Uses a **custom `employee` guard** (`auth:employee` middleware) backed by `PhpposEmployee` model.
- Default credentials: **admin** / **12345678** (legacy MD5 stored in seeder; auto-upgrades to bcrypt on first login).
- Login route: `GET/POST /login`, logout: `POST /logout`.

## Database & migrations

- **SQLite** by default in `.env.example` (MySQL optional, just uncomment).
- **One create-only migration per `phppos_*` table** — never add `Schema::table()` alters. To change schema, update the single create migration and run `migrate:fresh`.
- Use `foreignId()->constrained()` for FKs. Prefer `cascadeOnDelete`/`nullOnDelete` explicitly.
- Seed all baseline data in `database/seeders/`, never in migrations.
- Validate: `php artisan migrate:fresh --seed`
- Legacy migrations in `database/migrations/legacy/` (reference only).
- Laravel default migrations (`users`, `cache`, `jobs`) kept as-is.

## Queue

- `QUEUE_CONNECTION=database` by default (DB-driven queue).
- Required for LAN sync. Start worker: `php artisan queue:work --sleep=1 --tries=1 --timeout=60`
- NSSM service `LaravelPosQueueWorker` auto-starts the worker in production.

## Architecture

- Routes split across `routes/web.php`, `routes/api.php` (LAN sync), `routes/rony/*` (feature bundles), `routes/console.php`.
- 65+ Eloquent models in `app/Models/` — most prefixed `phppos_*`, except `Location`, `TransferQueue`, `PriceRule`, `PriceRulePriceBreak`, `PriceTier`, `Attribute`, `AttributeValue`, `ItemVariation`, `User`.
- Two location tables: `phppos_locations` (POS stores) and `locations` (LAN peer registry with `ip`, `port`, `is_self`, `last_seen_at`).
- Custom middleware: `sync.auth`, `installed`, `check_register_open` (registered in `bootstrap/app.php`).
- Storage path overridable via `LARAVEL_STORAGE_PATH` env (`bootstrap/app.php:28`).
- Frontend: **Blade + jQuery** (not Vue/React). Tailwind CSS v4. Custom assets in `public/assets/`.
- Single-location enforcement: location resolved from node context (ULID header/cookie), never user-selected.

## Code style

- Laravel Pint (`./vendor/bin/pint`) — run before committing.
- EditorConfig: 4-space indent, LF endings, UTF-8.
- Node 26 (`.nvmrc`).

## Install modes

- **Browser mode**: PHP built-in server (`php -S 0.0.0.0:8020 -t public server.php`) + queue worker, managed via NSSM.
- **NativePHP desktop**: `php artisan native:build win` — uses SQLite, Electron shell.

## LAN sync

- `php artisan app:bind-identity` writes `APP_NODE_IP`/`APP_NODE_NAME` to `.env`.
- `php artisan app:register-self --port=8000` registers this node in the `locations` table (runs automatically in `composer run dev`).
- Discovery + transfers use DB queue + `/api/lan/*` endpoints (shared token auth in `config/sync.php`).
- No polling — manual Sync button in top bar.
- After editing `.env` (IP/name changes), run `php artisan config:clear` to flush stale cache.

## Knowledge base

Regenerate after changing routes, controllers, models, migrations, views, or docs:

```powershell
python tools\build_knowledge_base.py
```

Outputs `graphify-out/{graph.json, graph.html, GRAPH_REPORT.md}`. Read `graphify-out/GRAPH_REPORT.md` before structural changes.

## Other docs

- `MIGRATION_HANDOFF.md` — migration rules + completed/pending work log.
- `INSTALLER_BROWSER_MODE.md` — NSSM setup details.
- `nativePhp.md` — NativePHP desktop build guide.

## CI

- Only branch `night` deploys via FTP (`.github/workflows/night.yml`).
- No CI test runner.
