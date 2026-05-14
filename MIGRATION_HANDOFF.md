# MIGRATION HANDOFF: CI3 → Laravel 13

## 🤖 Agent Control Logic
**IMPORTANT INSTRUCTIONS FOR ALL AGENTS:**
1. **DO NOT WASTE TIME.** Focus on the objective and execute efficiently.
2. **Context & Past Work:** If you need historical context or want to know what has already been done, **look into the `History` section below**.
3. **Next Tasks:** If you need to know what needs to be done next, **look into the `Upcoming` section below**.
4. **Always Update:** You MUST ALWAYS UPDATE this `MIGRATION_HANDOFF.md` file when you complete a task or change the project state. Ensure the `History` and `Upcoming` sections remain accurate and up to date.

### ✅ Migration / Seeder Rules (STRICT)
These rules are non-negotiable for this repo’s cleaned schema.

1. **One migration per table (create-only).**
  - For any `phppos_*` table: keep exactly one `Schema::create()` migration that defines ALL columns + indexes + foreign keys.
  - **Do not add `Schema::table()` “alter” migrations** to add columns, indexes, or foreign keys.
  - If the schema needs to change during this migration phase, update the table’s single create migration and rebuild using `migrate:fresh`.

2. **Use modern FK style (`foreignId` + `constrained`).**
  - Prefer `foreignId('...')->nullable()->constrained('...')` (or `->constrained()` when the table name can be inferred).
  - Use `->cascadeOnDelete()` / `->nullOnDelete()` intentionally where it matches legacy behavior.
  - If MySQL FK constraint names risk exceeding 64 chars, use explicit short FK names.

3. **Seed data must live in seeders (never migrations).**
  - Any baseline data (modules tree, permissions, defaults) belongs in `database/seeders/*` (ex: `PosCoreSeeder`).
  - Migrations should only define schema.

4. **Always validate using a clean rebuild.**
  - Required command: `php artisan migrate:fresh --seed`
  - On Git Bash (Windows), use: `winpty php.exe artisan migrate:fresh --seed`

5. **Legacy migrations are reference-only.**
  - Keep old files archived under `database/migrations/legacy/`.
  - Do not move legacy migrations back into the active `database/migrations/` folder.
  - Keep Laravel default migrations (`users`, `cache`, `jobs`) as-is.

### ✅ Environment Assumptions (Current Project)
1. **Single PC / Single Location POS.**
  - This install is one PC, one active store location.
  - Devices are connected over LAN, but all logic should treat the location table as the current location only.
  - Inventory, sales, and movements must always resolve to the current location (no multi-location logic).
  - Each install is a node in the POS network. Requests can arrive from other nodes; always resolve the current location from the node context (ULID header/cookie) and ignore user-selected locations.

---

## 📜 History (Completed Work)

### May 14, 2026 (Customers Module Modernization)
- **UI Modernization:** Rebuilt Customer list and form UIs to match the new dashboard template (consistent with Suppliers/Items).
- **Customer List (index):** Added modern toolbar with search/actions, customized table with user-info avatars, balance highlighting, and bulk action infrastructure.
- **Customer Form:** Modernized with tabs (Basic, Taxes, Files, Advanced, Custom Fields), dark mode compatibility, dynamic tax row management using Vanilla JS templates, and integrated file upload/download/delete workflows.
- **Controller Parity:** Ensured `CustomerController` correctly handles all modernized form fields including balance updates, tax overrides, price tiers, and internal notes.

### May 14, 2026 (Reports: Location Context TypeError Fix)
- **Type Safety Fix:** Hardened `LocationContextService::resolveLocationId()` to accept `int|string|null` and safely normalize request-provided IDs.
- **Single Location Enforcement:** Reports now always resolve and filter by the current node location (ULID header/cookie → employee context → DB fallback) and ignore any user-selected `location_id`.
- **Legacy Report Links:** `GET /reports/generate/*` now supports CI3-style query params like `report_date_range_simple=TODAY` (auto-runs the report instead of only showing the parameter form).

### May 13, 2026 (Items & Item Kits Form Fixes + Enhancements)
- **JS Modernization:** Refactored item and item kit form JavaScript from jQuery to robust Vanilla JS. Implemented a template-based system (`<template>` tags) for dynamic rows (Secondary Categories, Suppliers, Serial Numbers, Additional SKUs), fixing "button not working" issues caused by missing jQuery or brittle script logic.
- **Secondary Categories for Item Kits:** Added full support for multiple secondary categories in Item Kits, including UI integration, `ItemKitController` save/load logic, and a new migration for `phppos_item_kits_secondary_categories`.
- **Model Relationships:** Added `secondaryCategories` and `secondarySuppliers` relationships to `PhpposItem` and `PhpposItemKit` models.
- **Robust UI:** Added optional chaining and event delegation to dynamic row management, ensuring forms remain functional even when certain elements are hidden due to permission logic.

### May 13, 2026 (Sales Currency + Flow Fixes)
- **Sales Register Currency Support:** Added base currency formatting and exchange rate selection on the Sales Register. Payments can now be entered in an exchange currency and auto-converted to base currency for totals and change. Payment rows show base amount on top and exchange amount below.
- **Receipt Currency Formatting:** Receipt amounts now use base currency formatting (symbol, decimals, separators) from Store Config.
- **Sales Flow Stability:** Fixed payment add crashes when no exchange rate row is selected. Made commission fields optional to prevent `commission_*` missing column errors. Inserted sales items after sale creation to avoid `sale_id` null constraint violations.
- **Single Location Sales:** Sales now resolve to the single current location for stock, sales, and inventory movements.
- **Single Location Node Context:** Added a shared location resolver and enforced it for sales, receivings, and transfers to ensure node requests always use the current location.
- **Reports + Sync API Location Lock:** Reports now always filter to the current node location, and transfer sync validates the destination ULID against the node location.

### May 10, 2026 (Orders & Receivings Sync)
- **Orders List & Filtering:** Updated `/orders` route to default to "open" orders. Added filter tabs to show all, open, and closed orders.
- **Order ID Display:** Replaced raw database primary keys with formatted `internal_code` identifiers (e.g. `PO-00000010`) in the Orders table UI.
- **Order Editing & Receiving Sync:** Added an edit modal to modify item/kit quantities within an order. Modifying quantities updates both the `quantity_purchased` and `quantity_received` values to keep them synchronized before closing.
- **Closing Orders:** Implemented a "Close Order" action featuring a SweetAlert confirmation dialog. Closing an order now programmatically duplicates it into a standard, completed receive record (`is_po = 0`, `source = order`), applies the received inventory quantities to the location's stock levels, and flags the original order as closed (`suspended = 1`).
- **Receiving Source Tracking:** Extended the `phppos_receivings` table migration by adding `source` (enum: manual, order, transfer) and `reference_id` columns. Rebuilt the database.
- **Purchases List Source Display:** Updated the `/purchases` (Receivings) list UI to include a "Source" column displaying the origin of the document (e.g., auto-generated from orders, imported from transfers, or manually input) along with its reference ID.
- **Transfers API:** `InventoryFlowService` now properly populates `source = transfer` and `reference_id` when importing transfers.
- **Order & Purchases Details View:** Replaced redirects with actual View Details (`orders.show` / `purchases.show`) and Print (`orders.print` / `purchases.print`) pages. Both show a comprehensive summary of items, totals, supplier, and origin data.
- **Barcodes on Print Views:** Installed `picqer/php-barcode-generator` and embedded high-quality SVG CODE-128 barcodes representing the document's `internal_code` onto the bottom of the printed receipts, mapping to the legacy CodeIgniter behavior.
- **Order Totals Calculation:** Fixed `OrderController` to correctly calculate and persist `subtotal` and `total` for both the main receiving record and individual items when creating or updating orders. Also ran a one-time script to fix past zeroed-out records.
- **Purchases Table Actions:** Cleaned up the Purchases history table (`purchases-page.js`) by replacing unimplemented Edit/Delete buttons with functional View Details and Print buttons.
- **Pagination & Search (Orders & Purchases):** Added proper Laravel pagination (`paginate()`) to both `/orders` (Blade rendered) and `/purchases` (AJAX rendered). Fully implemented server-side search by internal code, ID, or supplier name for both tables.
- **Item Kit Cart Bug Fix (All Registers):** Fixed a silent no-op when adding item kits to carts in `/purchases/create`, `/transfers/new`, and `/sales`. Root cause: kits with no component items in `phppos_item_kit_items` caused `addKitItemsToCart()` to iterate empty collections without adding anything. Fix: after expansion, if cart is unchanged, the kit itself is added as a single `[KIT] name` line item using its own `cost_price`/`unit_price`. Also optimised to use eager-loaded `$kitItem->item` instead of triggering extra N+1 queries. Applied consistently to `ReceivingController`, `SalesController`, and `TransferController`.
- **Structural: Kit Line Items in Receivings (schema change):** Added `item_kit_id` (nullable FK → `phppos_item_kits.id`) and made `item_id` nullable in `phppos_receivings_items`. Kit fallback lines (i.e. kits with no component items) are now properly stored with `item_kit_id` set and `item_id = null` instead of being silently skipped. Added `kit()` BelongsTo relation and `displayName()` helper to `PhpposReceivingItem`. Both `show.blade.php` and `print.blade.php` now display both regular items and kit line items, with a "Kit" badge to distinguish them. **Requires `migrate:fresh`** (already run).

### May 10, 2026
- **Store Config (`/config`):** Fixed `Array to string conversion` on save by excluding `locations_color` / `locations_secondary_color` (and denomination POST arrays) from `AppConfigService::batchSave`, and by skipping non-scalar values in batch saves. Hardened `AppConfigService::save` for bool/null. Ported currency tab closer to legacy `config.php`: exchange-rate decimals as dropdown (incl. “Let system decide”), base decimals dropdown, thousands separator and decimal point on the Currency tab, register **currency denominations** (CRUD + soft-delete via `deleted_denmos[]`), receipt text size **Extra large** option, and `disable_price_rules_dialog` loaded from app config. Fixed exchange-rate persistence: only truncate/rebuild when `config_exchange_rates_sync` is posted (so empty exchange tables no longer wipe rates on unrelated saves).
- **Purchases (inventory receiving / returns):** Renamed user-facing module to **Purchases** (sidebar, register, dashboard links). Added canonical routes `GET /purchases`, `GET /purchases/create`, `GET /purchases/history-data` (JSON list). **List UI** ported from `corey-dashboard/pages/purchases.html` + `pages/js/purchases.js`: `public/assets/css/purchases-page.css`, `public/assets/js/purchases-page.js`, Blade `#viewPurchasesList` with `recv-mode-toggle` (Purchases vs Return) loading rows via API; **Add Purchases** / **Add Return** link to `purchases/create?mode=receive|return`. `ReceivingController@create` applies `mode` query into session cart. Cart POST redirects use `purchases.create`. Seeder submodule label `receiving` → `Purchases` for new seeds.
- **Receiving document identity:** `phppos_receivings` create migration extended with `type` (`receive` \| `return` \| `transfer`, mirrors `mode`) and unique `internal_code` (`RCV-` + zero-padded `receiving_id` for receive/transfer, `RTV-` for returns). `PhpposReceiving::syncDocumentIdentity()` sets both after insert; all `PhpposReceiving::create` call sites updated. History API JSON includes `internal_code` and `type`; ID search matches `receiving_id` or `internal_code`. **Requires** `migrate:fresh` (or manual DB sync) because schema is merged into the single create migration per handoff rules.

### May 6, 2026
- **Transfers Sync (Backend):** Added peer-to-peer sync API endpoints with shared-token auth, transfer export endpoint, and import logic that creates a local Transfer In, inventory movements, and a Receiving record with source reference. Added external source/id fields to `phppos_transfers` for idempotent imports.
- **Receivings UI:** Added manual “Sync Transfer” modal on the Receiving register page.

### May 7, 2026
- **Dashboard Modernization:** Rebuilt the `modules.index` view to match the `corey-dashboard` template. Added sections for Stat Cards, Getting Started progress, Command Center, Recent Activity, and advanced Sales Analytics charts.
- **Migration Fix:** Added missing `customer_id` column and foreign key to `phppos_sales` table migration to support recent activity tracking. Rebuilt database using `migrate:fresh --seed`.
- **UI Refinements:** Improved sidebar hover and active states for both light and dark modes. Ensured the theme's primary blue color is used for active states and added better hover contrast in dark mode.
- **ModuleController:** Updated to fetch extended stats and recent sales data.
- **Orders UI (WIP):** Built initial Orders page UI and endpoints.
- **Migration Fix (Previous):** Removed two schema-alter migrations and merged changes into create migrations. Added `supplier_id` + `reorder_level` to `phppos_item_kits`, added `reorder_level` to `phppos_items`, and added a new create migration for `phppos_item_kits_secondary_suppliers`.
- **Threshold Labels:** Renamed reorder level labels to “Threshold” on item and item kit forms, and added Threshold column to item kits list.
- **Threshold Column (Items):** Added Threshold column to items list.
- **Quick Edit (Lists):** Added quick-edit modals with auto-save on blur for items and item kits list (cost, price, quantity, threshold).

### May 1, 2026
- **Inventory Flow (Receivings):** Enhanced `ReceivingController` to support searching and adding Item Kits (including nested kits) to the receiving cart. Implemented logic to parse item kits and correctly process their individual items during inventory updates, matching legacy POS system behavior.
- **Migrations:** Archived legacy migrations to `database/migrations/legacy`, created one-migration-per-table schema (excluding users/cache/jobs), and centralized seed data in `PosCoreSeeder`.

### 1. Auth & Security
- **Database:** Migrated `phppos_employees`, `phppos_people`, and related tables (`phppos_permissions`, `phppos_modules_actions`). Fixed MySQL FK issues.
- **Models:** Created `PhpposPerson` and `PhpposEmployee`.
- **Logic:** Implemented `EmployeeAuthController`, `auth:employee` guard, and session login. Fully implemented "Remember me" feature (UI, controller logic, and added `remember_token` to `phppos_employees` table).
- **Legacy Support:** Added legacy CI3 MD5 password validation that auto-upgrades to Laravel `Hash::make()` upon successful login. Time window checks and inactive/deleted block logic ported.

### 2. Dashboard & Modules
- **Database:** Created `phppos_modules` and `phppos_module_submodules`. Seeded locations, contacts, items, sales, messages, and config module tree.
- **Logic:** Added `ModuleController` to render the dynamic module dashboard UI based on user permissions.
- **UI:** Implemented modern, responsive sidebar layout (`layouts.app.blade.php`) and module dashboard layout.

### 3. Inventory & Items
- **Items:** 
  - Backend: Added `PhpposItem`, `ItemController` with minimal CRUD, added item dependencies (taxes, suppliers).
  - UI: Replaced standard table with styled toolbar, search input, and bulk action interface (Modernized to Pos HTML Dashboard).
- **Categories:**
  - Backend: Added `PhpposCategory`, `CategoryController`.
  - UI: Transformed into a hierarchical tree view to display parent-child relationships, integrated add/edit modals (Modernized to Pos HTML Dashboard).
- **Tags:**
  - UI: Created new UI view with the modern dashboard structure, including add/edit modals.
  - Backend: Created missing `PhpposTag` model to resolve fatal errors during Item and Item Kit creation.
- **Item Kits:**
  - Backend: Added `PhpposItemKit`, `ItemKitController` with minimal CRUD.
  - DB: Added missing `override_default_tax` column to `phppos_item_kits`.
- **Labels:** 
  - Backend: Added `ItemLabelController`, `phppos_item_label_jobs`.
  - Print logic: Implemented barcode mode (JsBarcode) and sheet mode (logo box mapping).
  - UI: Rebuilt Labels UI to match Pos HTML Dashboard style, added sheet background upload stored in app config.
  - Config: Wired `show_barcode_company_name` and `hide_barcode_on_barcode_labels` to Store Config and print view.
  - Search: Added label search across items and item kits.

### 4. Inventory Flow
- **Database:** Created `phppos_location_items`, `phppos_transfers`, `phppos_inventory_movements`.
- **Logic:** Added `InventoryFlowService` using DB transactions and row locks (`lockForUpdate`) for stock consistency.
- **Features:** Implemented Receivings (add stock), Returns (subtract stock), Transfer Out (auto-creates and completes Transfer In at destination).
- **UI:** Added `InventoryOperationController` and basic operations UI.

### 5. Sales & Receipts
- **Database:** Created `phppos_sales`, `phppos_sales_items`, `phppos_sales_payments`, `phppos_sales_item_returns`, `phppos_receipt_settings`.
- **Logic:** Added `SalesService` for transactional sales, stock validation, deductions, and processing returns against sale receipts (line-level qty).
- **UI:** Added `SalesController`, sales register page, and receipt endpoint. Included basic receipt settings (title, footer, paper size).

### 6. Contacts
- **Suppliers:**
  - Backend: Added `PhpposSupplier`, `SupplierController`, `PhpposSupplierTax`, `PhpposTaxClass`, `PhpposInvoiceTerm`.
  - Logic: Implemented complete save logic including taxes, custom fields, image uploads, and multiple file attachments.
  - UI: Replaced list view with modern card-based table. Redesigned form view to use a modern Bootstrap 5 Tab system (Basic Info, Taxes, Advanced, Custom Fields).
- **Customers:**
  - UI: Built new modern card-based UI module using identical styling to Suppliers, mapping to `$customers` data.

### 7. Messages
- **Logic:** Refactored `MessageController` to use Eloquent models (`PhpposMessage`, `PhpposMessageReceiver`) and implemented advanced sending logic (All Employees, All Locations, or specific selections). Added Soft Delete support.
- **UI:** Fully modernized the Messages UI to match the Pos HTML Dashboard design, including Inbox, Compose, and Detail views with responsive behavior and view switching.

### 8. Store Config & Employees
- **Locations:** Added `ulid` field to `phppos_locations` table and implemented ULID-based location loading via `EmployeeService::getLoggedInEmployeeCurrentLocationId()`. This supports connecting multiple PCs via LAN to a single location identifier, falling back to legacy session `employee_current_location_id`.
- **Config:** Added `AppConfigService` (duplicate tax guard, payment types helper), `ConfigController`, and settings UI.
- **Config:** Added Store Config shortcut for label sheet background uploads.
- **Config:** Set `phppos_app_files.file_data` to `LONGBLOB` to support larger uploads.
- **Config:** Added migrations and models for `phppos_currency_exchange_rates`. Modernized the Store Config UI to manage Currency (Exchange Rates), Payment Types, and Price Rules dynamically via Bootstrap Tabs. Explicitly skipped API integrations (ecommerce locations, api keys) per user requirement.
- **Employees:** Added dependencies (`phppos_employees_time_clock`, permissions locations, templates), `EmployeeService`, `EmployeeController`, and basic views for syncing permissions.


### 9. Items
- **Advanced Editing:** Ported all advanced item editing capabilities from the legacy CI3 setup to Laravel. Added fields for weight, size, ecommerce settings, EBT, serialized flags, and age verification to the `phppos_items` table.
- **Relational Data:** Created pivot tables to manage tags, additional item numbers, and serial numbers.
- **UI Rewrite:** Redesigned `items/form.blade.php` to use a modern, responsive Bootstrap 5 Tab system to organize the massive number of fields neatly (Basic Info, Dimensions, Settings, Advanced, Custom Fields).
- **Manufacturers:** Created `PhpposManufacturer` model and migration. Added manufacturer dropdown to item form with manage link.
- **Secondary Categories/Suppliers:** Added support for multiple secondary categories and suppliers with dynamic add/remove functionality via `phppos_items_secondary_categories` and `phppos_items_secondary_suppliers` tables.
- **Custom Fields:** Added 10 custom fields (`custom_field_1_value` through `custom_field_10_value`) to `phppos_items` table. Form supports checkbox, date, dropdown, image, and file input types.
- **Serial Numbers:** Enhanced serial numbers table in form to include "Add to Inventory" checkbox, cost price, unit price, and variation dropdown (matching CI3 functionality).

---

## 🚀 Upcoming (Pending Tasks)

### 1. Auth & Security
- [ ] Implement 2FA flow (`allow_employees_to_use_2fa` + `secret_key_2fa`).
- [ ] Implement employee reset-password endpoints and email templates (parity with CI3).

### 2. Dashboard & Modules
- [ ] No immediate tasks pending. Ensure new modules are correctly injected into the seeders.

### 3. Inventory & Items
- [x] **Items:** Expand item attributes UI, implement bulk edit, import/export, and pricing/tax tiers. (Completed: manufacturers, secondary categories/suppliers, custom fields, enhanced serial numbers)
- [x] **Tags:** Implement backend `TagController` and wire up routes to match the modernized UI.
  - Created `TagController` with full CRUD operations
  - Created `tags.index` and `tags.form` views
  - Added tags routes to `web.php`
  - Updated `phppos_tags` migration to match CI3 structure (added `ecommerce_tag_id`, `last_modified`, fixed indexes)
- [x] **Item Kits:** Modernized UI to match the new Pos HTML Dashboard designs (tabs, dynamic rows, secondary categories/suppliers).
- [ ] **Attributes:** Implement backend logic and modernize UI.
- [ ] **Price Rules:** Implement backend logic and modernize UI.

### 4. Inventory Flow
- [ ] Modernize the UI for Receivings, Returns, and Transfers to match the new Pos HTML Dashboard template.
- [x] **Transfers Sync (UI):** Added target device registry (`sync_url` in locations table) and enabled peer-to-peer transfer sync for open, edit, and completed transfer events.
- [x] **Transfers:** Added capability to create/save a transfer without completing, and edit open transfers (adjust quantities, close/complete).

### 5. Sales & Receipts
- [ ] Modernize Sales Register UI.
- [ ] Implement VAT Reporting UI and backend integration.

### 6. Contacts
- [x] **Suppliers:** Fully modernized backend logic and UI (parity with CI3).
- [ ] **Customers:** Implement backend `CustomerController`, models, and wire up routes for the modernized UI.

### 7. Messages
- [x] Modernized Messages UI to match the new Pos HTML Dashboard templates.

### 8. Store Config & Employees
- [x] **Employees:** Expand UI for permissions templates, time clock, and time off management. Modernize the employee directory/form UI.
  - Created `PhpposPermissionTemplate` model (matches CI3 `phppos_permissions_templates` table)
  - Fixed table name discrepancy (`phppos_permission_templates` → `phppos_permissions_templates`)
  - Employee form now has: Permission Templates dropdown, Module/Action checkboxes, Location overrides for modules/actions
  - `EmployeeController` properly handles `template_id`, permissions, action permissions, and location assignments
  - `EmployeeService` handles all permission syncing correctly
- [x] **Config:** Re-add sub modules for Currency, Payment Types, and Price Rules.
  - Added new tabs to `config.index` UI for Currency (with Exchange Rates table), Payment Types, and Price Rules.
  - Removed explicit API integration dependencies and tables as requested.
