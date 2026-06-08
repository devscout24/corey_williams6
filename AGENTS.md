# AGENTS.md — Laravel POS

## Quick start

```powershell
composer install
npm install
copy .env.example .env         # then edit DB_* for MySQL
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
```

## Dev server

| Command | What it runs |
|---|---|
| `composer run dev` | `php artisan serve` + `queue:listen` + `pail` + Vite, concurrently |
| `npm run dev` | Vite only |
| `composer run test` | `config:clear` then `php artisan test` |
| `composer run setup` | Full fresh install (composer, .env, key, migrate, npm) |
| `composer run native:dev` | NativePHP desktop dev: `native:serve` + Vite |

## Testing

- PHPUnit with SQLite `:memory:` in tests (`phpunit.xml`).
- Suites: `tests/Unit`, `tests/Feature`.
- Queue forced to `sync` in tests (no worker needed).

## Database & migrations

- **MySQL** in production, **SQLite** in tests.
- **One create-only migration per `phppos_*` table** — never add `Schema::table()` alters. To change schema, update the single create migration and run `migrate:fresh`.
- Use `foreignId()->constrained()` for FKs. Prefer `cascadeOnDelete`/`nullOnDelete` explicitly.
- Seed all baseline data in `database/seeders/` (e.g. `PosCoreSeeder`), never in migrations.
- Validate schema: `php artisan migrate:fresh --seed`
- Legacy migrations live in `database/migrations/legacy/` (reference only; never move back).
- Laravel default migrations (`users`, `cache`, `jobs`, etc.) kept as-is.

## Queue

- `QUEUE_CONNECTION=database` by default (DB-driven queue).
- Required for LAN sync. Start worker: `php artisan queue:work --sleep=1 --tries=1 --timeout=60`
- NSSM service `LaravelPosQueueWorker` auto-starts the worker in production.

## Architecture

- Routes split across `routes/web.php`, `routes/api.php` (LAN sync), `routes/rony/*` (feature bundles), `routes/console.php`.
- 65+ Eloquent models (all `phppos_*` prefixed) in `app/Models/`.
- Custom middleware aliases: `sync.auth`, `installed`, `check_register_open` (registered in `bootstrap/app.php`).
- Storage path overridable via `LARAVEL_STORAGE_PATH` env (`bootstrap/app.php:25`).
- Key services: `InventoryFlowService`, `SalesService`, `AppConfigService`, `ZktecoService`, `LocationContextService`, `EmployeeService`.

## Code style

- Laravel Pint (`laravel/pint`) — run `./vendor/bin/pint` before committing.
- EditorConfig: 4-space indent, LF line endings, UTF-8.
- Node 26 (`.nvmrc`).

## Install modes

- **Browser mode**: NSSM runs PHP built-in server (`php -S 0.0.0.0:8020 -t public server.php`) + queue worker.
- **NativePHP desktop**: `php artisan native:build win` — uses SQLite, Electron shell.

## LAN sync

- Each node binds identity: `php artisan app:bind-identity` (writes `APP_NODE_IP`/`APP_NODE_NAME` to `.env`).
- Discovery + transfers flow through DB queue + `/api/lan/*` endpoints. No background polling — manual Sync button.
- `config/sync.php` holds `shared_token` for peer auth. `config/nativephp.php` has desktop app config.

## Knowledge base

Regenerate after changing routes, controllers, models, migrations, views, or docs:

```powershell
python tools\build_knowledge_base.py
```

Outputs `graphify-out/{graph.json, graph.html, GRAPH_REPORT.md}`. Read `graphify-out/GRAPH_REPORT.md` before making structural changes.

## Other docs

- `MIGRATION_HANDOFF.md` — migration rules + completed/pending work log.
- `INSTALLER_BROWSER_MODE.md` — NSSM service setup details.
- `nativePhp.md` — NativePHP desktop build guide.

## CI

- Only branch `night` deploys via FTP (`.github/workflows/night.yml`).
- No CI test runner configured.
