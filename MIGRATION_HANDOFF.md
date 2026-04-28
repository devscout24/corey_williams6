# MIGRATION HANDOFF: CI3 → Laravel 13

## 🤖 Agent Control Logic
**IMPORTANT INSTRUCTIONS FOR ALL AGENTS:**
1. **DO NOT WASTE TIME.** Focus on the objective and execute efficiently.
2. **Context & Past Work:** If you need historical context or want to know what has already been done, **look into the `History` section below**.
3. **Next Tasks:** If you need to know what needs to be done next, **look into the `Upcoming` section below**.
4. **Always Update:** You MUST ALWAYS UPDATE this `MIGRATION_HANDOFF.md` file when you complete a task or change the project state. Ensure the `History` and `Upcoming` sections remain accurate and up to date.

---

## 📜 History (Completed Work)

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
- **Config:** Altered `phppos_app_files.file_data` to `LONGBLOB` to support larger uploads.
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
- [ ] **Item Kits:** Modernize UI to match the new Pos HTML Dashboard designs.
- [ ] **Attributes:** Implement backend logic and modernize UI.
- [ ] **Price Rules:** Implement backend logic and modernize UI.

### 4. Inventory Flow
- [ ] Modernize the UI for Receivings, Returns, and Transfers to match the new Pos HTML Dashboard template.

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
- [ ] **Config:** Add additional config tables (ecommerce locations, exchange rates, API keys) and modernize the config UI.
