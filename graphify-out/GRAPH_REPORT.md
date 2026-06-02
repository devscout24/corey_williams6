# Laravel POS Knowledge Graph Report

Generated from local static analysis of routes, controllers, models, migrations, Blade views, tests, and project Markdown docs.

## Snapshot

- Nodes: 2,679
- Edges: 3,388
- Routes: 159
- Controllers: 31
- Models: 65
- Tables/table refs: 125
- Views: 49

## Key Domains

- Sales/register: SalesController, phppos_sales, register
- Purchasing/receiving: ReceivingController, PhpposReceiving, phppos_receivings
- Inventory/items: ItemController, ItemVariation, phppos_items, phppos_location_items
- LAN transfer sync: LanController, TransferController, TransferQueue, locations
- VAT/reporting: ReportController, phppos_vat_rates, phppos_sales_items_taxes
- People/access: EmployeeController, CustomerController, SupplierController, phppos_people

## Most Connected Concepts

- Model [class_ref], degree 63
- PhpposItem [model_ref], degree 62
- PhpposLocation [model_ref], degree 60
- phppos_items [table], degree 56 (database/migrations/2026_05_10_232648_add_reorder_level_to_items_tables.php:35)
- layouts.app [view], degree 53
- routes/web.php [route_file], degree 50
- phppos_item_kits [table], degree 49 (database/migrations/2026_05_01_000037_create_phppos_item_kits_table.php:11)
- PhpposSupplier [model_ref], degree 48
- PhpposItemKit [model_ref], degree 48
- PhpposCategory [model_ref], degree 46
- MIGRATION_HANDOFF.md [doc_file], degree 46
- phppos_receivings [table], degree 43 (database/migrations/2026_05_01_000070_create_phppos_receivings_table.php:11)
- phppos_employees [table], degree 38 (database/migrations/2026_05_10_224324_add_commission_fields_to_tables.php:39)
- PhpposEmployee [model_ref], degree 37
- phppos_customers [table], degree 35 (database/migrations/2026_05_01_000031_create_phppos_customers_table.php:11)

## Relationship Types

- has_column: 875
- defines: 657
- uses_model: 568
- contains: 199
- links_to_route: 174
- declares: 159
- handled_by: 159
- calls: 159
- extends: 145
- creates: 125
- renders: 75
- belongsTo: 35
- hasMany: 20
- belongsToMany: 16
- alters: 14
- foreign_key: 4
- hasOne: 2
- include: 2

## Suggested Questions

- Which routes touch sales, payments, register logs, and VAT tables?
- What model relationships exist around item variations, suppliers, and location inventory?
- Which controllers render each Blade view, and which views link back to route names?
- Where does LAN transfer sync flow from web/API routes into jobs and queue tables?
- Which active migrations still alter tables during the migration-cleanup phase?

## Files

- `graph.html`: interactive local browser graph.
- `graph.json`: machine-readable graph with nodes, edges, files, lines, and confidence tags.
- `GRAPH_REPORT.md`: this summary.
- `KNOWLEDGE_BASE_SETUP.md`: setup steps, regeneration commands, missing pieces, and recommended next steps.
