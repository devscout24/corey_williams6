@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@push('styles')
    <style>
        .reports-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
        }

        .report-menu {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: var(--shadow-sm);
            height: fit-content;
            max-height: calc(100vh - 150px);
            overflow-y: auto;
        }

        .report-menu h3 {
            font-size: 0.9rem;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            padding-left: 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--gray-700);
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            width: 100%;
            text-align: left;
            background: none;
            margin-bottom: 4px;
        }

        .menu-item:hover {
            background: var(--gray-50);
            color: var(--primary);
        }

        .menu-item.active {
            background: #000000;
            color: #ffffff;
        }

        .menu-item i {
            font-size: 1.1rem;
        }

        .report-selection {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
            min-height: 600px;
        }

        .report-group {
            display: none;
        }

        .report-group.active {
            display: block;
        }

        .report-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--gray-100);
            color: var(--gray-700);
            transition: var(--transition);
            text-decoration: none;
        }

        .report-link:last-child {
            border-bottom: none;
        }

        .report-link:hover {
            background: var(--gray-50);
            color: var(--primary);
        }

        .report-link i {
            font-size: 1.2rem;
            color: var(--gray-400);
        }

        .empty-selection {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: var(--gray-400);
        }

        .empty-selection i {
            font-size: 4rem;
            margin-bottom: 16px;
        }
    </style>
@endpush

@section('content')
    <div class="reports-container">
        <aside class="report-menu">
            <h3>Report Categories</h3>
            <button class="menu-item active" data-target="sales">
                <i class="bi bi-cart"></i> Sales
            </button>
            <button class="menu-item" data-target="sales_generator">
                <i class="bi bi-search"></i> Sales Generator
            </button>
            <button class="menu-item" data-target="appointments">
                <i class="bi bi-calendar-event"></i> Appointments
            </button>
            <button class="menu-item" data-target="categories">
                <i class="bi bi-grid"></i> Categories
            </button>
            <button class="menu-item" data-target="closeout">
                <i class="bi bi-x-circle"></i> Closeout
            </button>
            <button class="menu-item" data-target="commissions">
                <i class="bi bi-percent"></i> Commissions
            </button>
            <button class="menu-item" data-target="customers">
                <i class="bi bi-people"></i> Customers
            </button>
            <button class="menu-item" data-target="deliveries">
                <i class="bi bi-truck-flatbed"></i> Deliveries
            </button>
            <button class="menu-item" data-target="discounts">
                <i class="bi bi-tag"></i> Discounts
            </button>
            <button class="menu-item" data-target="employees">
                <i class="bi bi-person-badge"></i> Employees
            </button>
            <button class="menu-item" data-target="expenses">
                <i class="bi bi-wallet2"></i> Expenses
            </button>
            <button class="menu-item" data-target="giftcards">
                <i class="bi bi-card-checklist"></i> Giftcards
            </button>
            <button class="menu-item" data-target="inventory">
                <i class="bi bi-bar-chart"></i> Inventory
            </button>
            <button class="menu-item" data-target="invoices">
                <i class="bi bi-receipt-cutoff"></i> Invoices
            </button>
            <button class="menu-item" data-target="items">
                <i class="bi bi-box"></i> Items
            </button>
            <button class="menu-item" data-target="item_kits">
                <i class="bi bi-boxes"></i> Item Kits
            </button>
            <button class="menu-item" data-target="manufacturers">
                <i class="bi bi-factory"></i> Manufacturers
            </button>
            <button class="menu-item" data-target="payments">
                <i class="bi bi-cash-stack"></i> Payments
            </button>
            <button class="menu-item" data-target="price_rules">
                <i class="bi bi-rulers"></i> Price Rules
            </button>
            <button class="menu-item" data-target="profit_loss">
                <i class="bi bi-graph-up-arrow"></i> Profit & Loss
            </button>
            <button class="menu-item" data-target="receivings">
                <i class="bi bi-download"></i> Receivings
            </button>
            <button class="menu-item" data-target="register_log">
                <i class="bi bi-book"></i> Register Log
            </button>
            <button class="menu-item" data-target="registers">
                <i class="bi bi-display"></i> Registers
            </button>
            <button class="menu-item" data-target="store_accounts">
                <i class="bi bi-credit-card"></i> Store Accounts
            </button>
            <button class="menu-item" data-target="suppliers">
                <i class="bi bi-truck"></i> Suppliers
            </button>
            <button class="menu-item" data-target="suspended_sales">
                <i class="bi bi-pause-circle"></i> Suspended Sales
            </button>
            <button class="menu-item" data-target="tags">
                <i class="bi bi-tags"></i> Tags
            </button>
            <button class="menu-item" data-target="taxes">
                <i class="bi bi-calculator"></i> Taxes
            </button>
            <button class="menu-item" data-target="tiers">
                <i class="bi bi-layers"></i> Tiers
            </button>
            <button class="menu-item" data-target="timeclock">
                <i class="bi bi-clock"></i> Timeclock
            </button>
        </aside>

        <main class="report-selection" id="reportSelection">
            <!-- Sales Reports -->
            <div class="report-group active" id="sales">
                <h2 class="mb-4">Sales Reports</h2>
                <a href="{{ route('reports.generate', 'summary_journal') }}" class="report-link">
                    <i class="bi bi-journal-text"></i>
                    <div>
                        <strong>Summary Journal</strong>
                        <p class="mb-0 text-muted small">Daily journal of all sales and payments</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'graphical_summary_sales') }}" class="report-link">
                    <i class="bi bi-bar-chart-line"></i>
                    <div>
                        <strong>Graphical Summary Sales</strong>
                        <p class="mb-0 text-muted small">Visual representation of sales performance</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_sales') }}" class="report-link">
                    <i class="bi bi-file-earmark-text"></i>
                    <div>
                        <strong>Summary Sales</strong>
                        <p class="mb-0 text-muted small">View overall sales totals grouped by day</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_sales') }}" class="report-link">
                    <i class="bi bi-calendar-check"></i>
                    <div>
                        <strong>Detailed Sales</strong>
                        <p class="mb-0 text-muted small">View individual sale details and items sold</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_sales_day_of_week') }}" class="report-link">
                    <i class="bi bi-calendar-range"></i>
                    <div>
                        <strong>Sales by Day of Week</strong>
                        <p class="mb-0 text-muted small">Analyze sales performance by day of the week</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_sales_time') }}" class="report-link">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong>Sales by Time</strong>
                        <p class="mb-0 text-muted small">Analyze sales performance by hour</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'graphical_summary_sales_time') }}" class="report-link">
                    <i class="bi bi-graph-up"></i>
                    <div>
                        <strong>Graphical Sales by Time</strong>
                        <p class="mb-0 text-muted small">Visual analysis of sales performance by hour</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_ecommerce_sales') }}" class="report-link">
                    <i class="bi bi-cart-check"></i>
                    <div>
                        <strong>Detailed Ecommerce Sales</strong>
                        <p class="mb-0 text-muted small">View sales originating from ecommerce platform</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_sales_locations') }}" class="report-link">
                    <i class="bi bi-geo-alt"></i>
                    <div>
                        <strong>Sales by Location</strong>
                        <p class="mb-0 text-muted small">Compare sales performance across different locations</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_tips') }}" class="report-link">
                    <i class="bi bi-cash"></i>
                    <div>
                        <strong>Summary Tips</strong>
                        <p class="mb-0 text-muted small">View tips collected by employees</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_last_4_cc') }}" class="report-link">
                    <i class="bi bi-credit-card-2-front"></i>
                    <div>
                        <strong>Search Last 4 Credit Card</strong>
                        <p class="mb-0 text-muted small">Find sales by the last 4 digits of credit card</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'deleted_sales') }}" class="report-link">
                    <i class="bi bi-trash"></i>
                    <div>
                        <strong>Deleted Sales</strong>
                        <p class="mb-0 text-muted small">Review sales that have been deleted</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'voided_transactions') }}" class="report-link">
                    <i class="bi bi-slash-circle"></i>
                    <div>
                        <strong>Voided Transactions</strong>
                        <p class="mb-0 text-muted small">Review credit card transactions that were voided</p>
                    </div>
                </a>
            </div>

            <!-- Sales Generator -->
            <div class="report-group" id="sales_generator">
                <h2 class="mb-4">Sales Generator</h2>
                <a href="{{ route('reports.generate', 'sales_generator') }}" class="report-link">
                    <i class="bi bi-search"></i>
                    <div>
                        <strong>Sales Search / Generator</strong>
                        <p class="mb-0 text-muted small">Custom sales report with advanced filtering</p>
                    </div>
                </a>
            </div>

            <!-- Appointments Reports -->
            <div class="report-group" id="appointments">
                <h2 class="mb-4">Appointment Reports</h2>
                <a href="{{ route('reports.generate', 'summary_appointments') }}" class="report-link">
                    <i class="bi bi-calendar-event"></i>
                    <div>
                        <strong>Summary Appointments</strong>
                        <p class="mb-0 text-muted small">Overview of appointments scheduled</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_appointments') }}" class="report-link">
                    <i class="bi bi-calendar-check"></i>
                    <div>
                        <strong>Detailed Appointments</strong>
                        <p class="mb-0 text-muted small">Detailed list of all appointments</p>
                    </div>
                </a>
            </div>

            <!-- Categories Reports -->
            <div class="report-group" id="categories">
                <h2 class="mb-4">Category Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_categories') }}" class="report-link">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <strong>Graphical Summary Categories</strong>
                        <p class="mb-0 text-muted small">Visual breakdown of sales by category</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_categories') }}" class="report-link">
                    <i class="bi bi-grid"></i>
                    <div>
                        <strong>Summary Categories</strong>
                        <p class="mb-0 text-muted small">Sales totals grouped by category</p>
                    </div>
                </a>
            </div>

            <!-- Closeout Reports -->
            <div class="report-group" id="closeout">
                <h2 class="mb-4">Closeout Reports</h2>
                <a href="{{ route('reports.generate', 'closeout') }}" class="report-link">
                    <i class="bi bi-door-closed"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Overview of daily register closeouts</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'closeout_condensed') }}" class="report-link">
                    <i class="bi bi-file-zip"></i>
                    <div>
                        <strong>Condensed Summary</strong>
                        <p class="mb-0 text-muted small">A shorter version of the closeout report</p>
                    </div>
                </a>
            </div>

            <!-- Commissions Reports -->
            <div class="report-group" id="commissions">
                <h2 class="mb-4">Commission Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_commissions') }}" class="report-link">
                    <i class="bi bi-bar-chart-steps"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of employee commissions</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_commissions') }}" class="report-link">
                    <i class="bi bi-percent"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Total commissions earned by employees</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_commissions') }}" class="report-link">
                    <i class="bi bi-list-check"></i>
                    <div>
                        <strong>Detailed Reports</strong>
                        <p class="mb-0 text-muted small">Detailed breakdown of each commission earned</p>
                    </div>
                </a>
            </div>

            <!-- Customer Reports -->
            <div class="report-group" id="customers">
                <h2 class="mb-4">Customer Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_customers') }}" class="report-link">
                    <i class="bi bi-person-video"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of customer sales</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_customers') }}" class="report-link">
                    <i class="bi bi-people"></i>
                    <div>
                        <strong>Summary Customers</strong>
                        <p class="mb-0 text-muted small">Sales performance grouped by customer</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'specific_customer') }}" class="report-link">
                    <i class="bi bi-person-lines-fill"></i>
                    <div>
                        <strong>Detailed Customer Report</strong>
                        <p class="mb-0 text-muted small">Detailed sales for a specific customer</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'customers_series') }}" class="report-link">
                    <i class="bi bi-graph-up"></i>
                    <div>
                        <strong>Customer Series</strong>
                        <p class="mb-0 text-muted small">Trend analysis for customer purchasing</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'new_customers') }}" class="report-link">
                    <i class="bi bi-person-plus"></i>
                    <div>
                        <strong>New Customers</strong>
                        <p class="mb-0 text-muted small">Report on customers added within a date range</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_customers_zip') }}" class="report-link">
                    <i class="bi bi-geo"></i>
                    <div>
                        <strong>Zip Code Report</strong>
                        <p class="mb-0 text-muted small">Sales totals grouped by customer zip code</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'graphical_customers_zip') }}" class="report-link">
                    <i class="bi bi-map"></i>
                    <div>
                        <strong>Graphical Zip Code Report</strong>
                        <p class="mb-0 text-muted small">Visual distribution of sales by zip code</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_non_taxable_customers') }}" class="report-link">
                    <i class="bi bi-person-dash"></i>
                    <div>
                        <strong>Non Taxable Customers</strong>
                        <p class="mb-0 text-muted small">Report on customers exempt from tax</p>
                    </div>
                </a>
            </div>

            <!-- Deliveries Reports -->
            <div class="report-group" id="deliveries">
                <h2 class="mb-4">Delivery Reports</h2>
                <a href="{{ route('reports.generate', 'detailed_deliveries') }}" class="report-link">
                    <i class="bi bi-truck"></i>
                    <div>
                        <strong>Detailed Reports</strong>
                        <p class="mb-0 text-muted small">Detailed log of all scheduled deliveries</p>
                    </div>
                </a>
            </div>

            <!-- Discounts Reports -->
            <div class="report-group" id="discounts">
                <h2 class="mb-4">Discount Reports</h2>
                <a href="{{ route('reports.generate', 'summary_discounts') }}" class="report-link">
                    <i class="bi bi-tag"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Total discounts given grouped by discount type</p>
                    </div>
                </a>
            </div>

            <!-- Employees Reports -->
            <div class="report-group" id="employees">
                <h2 class="mb-4">Employee Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_employees') }}" class="report-link">
                    <i class="bi bi-person-badge"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of employee sales</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_employees') }}" class="report-link">
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <strong>Summary Employees</strong>
                        <p class="mb-0 text-muted small">Sales performance grouped by employee</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'specific_employee') }}" class="report-link">
                    <i class="bi bi-person-bounding-box"></i>
                    <div>
                        <strong>Detailed Employee Report</strong>
                        <p class="mb-0 text-muted small">Detailed sales for a specific employee</p>
                    </div>
                </a>
            </div>

            <!-- Expenses Reports -->
            <div class="report-group" id="expenses">
                <h2 class="mb-4">Expense Reports</h2>
                <a href="{{ route('reports.generate', 'summary_expenses') }}" class="report-link">
                    <i class="bi bi-wallet2"></i>
                    <div>
                        <strong>Summary Expenses</strong>
                        <p class="mb-0 text-muted small">Expense totals grouped by category</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_expenses') }}" class="report-link">
                    <i class="bi bi-receipt"></i>
                    <div>
                        <strong>Detailed Expenses</strong>
                        <p class="mb-0 text-muted small">Detailed log of all business expenses</p>
                    </div>
                </a>
            </div>

            <!-- Giftcards Reports -->
            <div class="report-group" id="giftcards">
                <h2 class="mb-4">Giftcard Reports</h2>
                <a href="{{ route('reports.generate', 'summary_giftcards') }}" class="report-link">
                    <i class="bi bi-card-checklist"></i>
                    <div>
                        <strong>Summary Giftcards</strong>
                        <p class="mb-0 text-muted small">Overall giftcard balances and counts</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_giftcards') }}" class="report-link">
                    <i class="bi bi-card-text"></i>
                    <div>
                        <strong>Detailed Giftcards</strong>
                        <p class="mb-0 text-muted small">History of giftcard transactions</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'giftcard_audit') }}" class="report-link">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <strong>Audit Report</strong>
                        <p class="mb-0 text-muted small">Detailed log of giftcard value adjustments</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_giftcard_sales') }}" class="report-link">
                    <i class="bi bi-cart-check"></i>
                    <div>
                        <strong>Gift Card Sales Reports</strong>
                        <p class="mb-0 text-muted small">Summary of gift card sales</p>
                    </div>
                </a>
            </div>

            <!-- Inventory Reports -->
            <div class="report-group" id="inventory">
                <h2 class="mb-4">Inventory Reports</h2>
                <a href="{{ route('reports.generate', 'inventory_low') }}" class="report-link">
                    <i class="bi bi-arrow-down-circle"></i>
                    <div>
                        <strong>Low Inventory</strong>
                        <p class="mb-0 text-muted small">Items that are below their reorder level</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'inventory_summary') }}" class="report-link">
                    <i class="bi bi-bar-chart-steps"></i>
                    <div>
                        <strong>Inventory Summary</strong>
                        <p class="mb-0 text-muted small">Current inventory levels and value</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'inventory_at_past_date') }}" class="report-link">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong>Inventory at Past Date</strong>
                        <p class="mb-0 text-muted small">View inventory levels as they were on a specific date</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_inventory') }}" class="report-link">
                    <i class="bi bi-list-check"></i>
                    <div>
                        <strong>Detailed Inventory</strong>
                        <p class="mb-0 text-muted small">Individual inventory adjustments log</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_count_report') }}" class="report-link">
                    <i class="bi bi-calculator"></i>
                    <div>
                        <strong>Summary Count Report</strong>
                        <p class="mb-0 text-muted small">Summary of inventory counts performed</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_count_report') }}" class="report-link">
                    <i class="bi bi-list-ol"></i>
                    <div>
                        <strong>Detailed Count Report</strong>
                        <p class="mb-0 text-muted small">Detailed log of inventory counts</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'expiring_inventory') }}" class="report-link">
                    <i class="bi bi-hourglass-split"></i>
                    <div>
                        <strong>Expiring Items Report</strong>
                        <p class="mb-0 text-muted small">List of items near their expiration date</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_damaged_items') }}" class="report-link">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>
                        <strong>Damaged Items Report</strong>
                        <p class="mb-0 text-muted small">Log of items marked as damaged</p>
                    </div>
                </a>
            </div>

            <!-- Invoices Reports -->
            <div class="report-group" id="invoices">
                <h2 class="mb-4">Invoice Reports</h2>
                <a href="{{ route('reports.generate', 'customer_invoices') }}" class="report-link">
                    <i class="bi bi-person-badge"></i>
                    <div>
                        <strong>Customer Invoices</strong>
                        <p class="mb-0 text-muted small">Report on invoices generated for customers</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'supplier_invoices') }}" class="report-link">
                    <i class="bi bi-truck"></i>
                    <div>
                        <strong>Supplier Invoices</strong>
                        <p class="mb-0 text-muted small">Report on invoices received from suppliers</p>
                    </div>
                </a>
            </div>

            <!-- Item Kits Reports -->
            <div class="report-group" id="item_kits">
                <h2 class="mb-4">Item Kits Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_item_kits') }}" class="report-link">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of item kit sales</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_item_kits') }}" class="report-link">
                    <i class="bi bi-boxes"></i>
                    <div>
                        <strong>Summary Item Kits</strong>
                        <p class="mb-0 text-muted small">Sales performance grouped by item kit</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_item_kits_variance') }}" class="report-link">
                    <i class="bi bi-plus-slash-minus"></i>
                    <div>
                        <strong>Price Variance Report</strong>
                        <p class="mb-0 text-muted small">Difference between actual and expected sales price</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'item_kit_price_history') }}" class="report-link">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong>Pricing History</strong>
                        <p class="mb-0 text-muted small">Log of price changes for item kits</p>
                    </div>
                </a>
            </div>

            <!-- Items Reports -->
            <div class="report-group" id="items">
                <h2 class="mb-4">Items Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_items') }}" class="report-link">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of item sales</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_items') }}" class="report-link">
                    <i class="bi bi-box-seam"></i>
                    <div>
                        <strong>Summary Items</strong>
                        <p class="mb-0 text-muted small">Sales performance grouped by item</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'enhanced_summary_items') }}" class="report-link">
                    <i class="bi bi-file-spreadsheet"></i>
                    <div>
                        <strong>Enhanced Summary Reports</strong>
                        <p class="mb-0 text-muted small">Item sales with additional detail</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'top_sellers') }}" class="report-link">
                    <i class="bi bi-award"></i>
                    <div>
                        <strong>Top Sellers</strong>
                        <p class="mb-0 text-muted small">View your best performing items</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'worse_sellers') }}" class="report-link">
                    <i class="bi bi-graph-down"></i>
                    <div>
                        <strong>Worse Sellers</strong>
                        <p class="mb-0 text-muted small">View your least performing items</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_items_variance') }}" class="report-link">
                    <i class="bi bi-plus-slash-minus"></i>
                    <div>
                        <strong>Price Variance Report</strong>
                        <p class="mb-0 text-muted small">Difference between actual and expected sales price</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'item_price_history') }}" class="report-link">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong>Pricing History</strong>
                        <p class="mb-0 text-muted small">Log of price changes for items</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'serial_numbers_sold') }}" class="report-link">
                    <i class="bi bi-123"></i>
                    <div>
                        <strong>Serial Numbers Sold</strong>
                        <p class="mb-0 text-muted small">Report on items sold with serial numbers</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'serial_number_history') }}" class="report-link">
                    <i class="bi bi-journal-list"></i>
                    <div>
                        <strong>Serial Number History</strong>
                        <p class="mb-0 text-muted small">History of a specific serial number</p>
                    </div>
                </a>
            </div>

            <!-- Manufacturers Reports -->
            <div class="report-group" id="manufacturers">
                <h2 class="mb-4">Manufacturer Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_manufacturers') }}" class="report-link">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of sales by manufacturer</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_manufacturers') }}" class="report-link">
                    <i class="bi bi-factory"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Sales performance grouped by manufacturer</p>
                    </div>
                </a>
            </div>

            <!-- Payments Reports -->
            <div class="report-group" id="payments">
                <h2 class="mb-4">Payment Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_payments') }}" class="report-link">
                    <i class="bi bi-pie-chart"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual breakdown of payments by type</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_payments') }}" class="report-link">
                    <i class="bi bi-cash-stack"></i>
                    <div>
                        <strong>Summary Payments</strong>
                        <p class="mb-0 text-muted small">Totals for each payment type</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_payments_registers') }}" class="report-link">
                    <i class="bi bi-display"></i>
                    <div>
                        <strong>Summary Payments Registers</strong>
                        <p class="mb-0 text-muted small">Payment totals grouped by register</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_payments') }}" class="report-link">
                    <i class="bi bi-list-ul"></i>
                    <div>
                        <strong>Detailed Payments</strong>
                        <p class="mb-0 text-muted small">List of all payments received</p>
                    </div>
                </a>
            </div>

            <!-- Price Rules Reports -->
            <div class="report-group" id="price_rules">
                <h2 class="mb-4">Price Rule Reports</h2>
                <a href="{{ route('reports.generate', 'summary_price_rules') }}" class="report-link">
                    <i class="bi bi-rulers"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Sales performance for each price rule</p>
                    </div>
                </a>
            </div>

            <!-- Profit & Loss -->
            <div class="report-group" id="profit_loss">
                <h2 class="mb-4">Profit & Loss Reports</h2>
                <a href="{{ route('reports.generate', 'summary_profit_and_loss') }}" class="report-link">
                    <i class="bi bi-graph-up-arrow"></i>
                    <div>
                        <strong>Summary Profit & Loss</strong>
                        <p class="mb-0 text-muted small">View high-level revenue vs expenses</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_profit_and_loss') }}" class="report-link">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <div>
                        <strong>Detailed Profit & Loss</strong>
                        <p class="mb-0 text-muted small">Detailed breakdown of profit and loss</p>
                    </div>
                </a>
            </div>

            <!-- Receivings Reports -->
            <div class="report-group" id="receivings">
                <h2 class="mb-4">Receivings Reports</h2>
                <a href="{{ route('reports.generate', 'summary_categories_receivings') }}" class="report-link">
                    <i class="bi bi-grid-3x3-gap"></i>
                    <div>
                        <strong>Summary Categories</strong>
                        <p class="mb-0 text-muted small">Receivings totals grouped by category</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'transfers') }}" class="report-link">
                    <i class="bi bi-arrow-left-right"></i>
                    <div>
                        <strong>Transfers</strong>
                        <p class="mb-0 text-muted small">Log of inventory transfers between locations</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_receivings') }}" class="report-link">
                    <i class="bi bi-download"></i>
                    <div>
                        <strong>Detailed Reports</strong>
                        <p class="mb-0 text-muted small">Detailed report of all inventory received</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_suspended_receivings') }}" class="report-link">
                    <i class="bi bi-pause"></i>
                    <div>
                        <strong>Suspended Receivings</strong>
                        <p class="mb-0 text-muted small">View receivings that are currently suspended</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'deleted_receivings') }}" class="report-link">
                    <i class="bi bi-trash"></i>
                    <div>
                        <strong>Deleted Recv Reports</strong>
                        <p class="mb-0 text-muted small">Log of receivings that have been deleted</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_taxes_receivings') }}" class="report-link">
                    <i class="bi bi-calculator"></i>
                    <div>
                        <strong>Summary Taxes Reports</strong>
                        <p class="mb-0 text-muted small">Total taxes paid on receivings</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'graphical_summary_taxes_receivings') }}" class="report-link">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <strong>Graphical Summary Taxes Reports</strong>
                        <p class="mb-0 text-muted small">Visual breakdown of taxes paid on receivings</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'cheapest_supplier') }}" class="report-link">
                    <i class="bi bi-tag"></i>
                    <div>
                        <strong>Cheapest Supplier</strong>
                        <p class="mb-0 text-muted small">Find the supplier with the lowest price for items</p>
                    </div>
                </a>
                <div class="mt-4 pt-4 border-top">
                    <h4 class="mb-3 text-primary">Items</h4>
                    <a href="{{ route('reports.generate', 'graphical_summary_items_receivings') }}" class="report-link">
                        <i class="bi bi-bar-chart"></i>
                        <div>
                            <strong>Graphical Reports</strong>
                            <p class="mb-0 text-muted small">Visual representation of items received</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'summary_items_receivings') }}" class="report-link">
                        <i class="bi bi-receipt"></i>
                        <div>
                            <strong>Summary Reports</strong>
                            <p class="mb-0 text-muted small">Summary of items received</p>
                        </div>
                    </a>
                </div>
                <div class="mt-4 pt-4 border-top">
                    <h4 class="mb-3 text-primary">Payments</h4>
                    <a href="{{ route('reports.generate', 'receivings_graphical_summary_payments') }}"
                        class="report-link">
                        <i class="bi bi-bar-chart"></i>
                        <div>
                            <strong>Graphical Reports</strong>
                            <p class="mb-0 text-muted small">Visual breakdown of payments for receivings</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'receivings_summary_payments') }}" class="report-link">
                        <i class="bi bi-receipt"></i>
                        <div>
                            <strong>Summary Reports</strong>
                            <p class="mb-0 text-muted small">Summary of payments for receivings</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'receivings_detailed_payments') }}" class="report-link">
                        <i class="bi bi-calendar"></i>
                        <div>
                            <strong>Detailed Reports</strong>
                            <p class="mb-0 text-muted small">Detailed log of payments for receivings</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Register Log Reports -->
            <div class="report-group" id="register_log">
                <h2 class="mb-4">Register Log Reports</h2>
                <a href="{{ route('reports.generate', 'detailed_register_log') }}" class="report-link">
                    <i class="bi bi-book"></i>
                    <div>
                        <strong>Detailed Reports</strong>
                        <p class="mb-0 text-muted small">Detailed log of all register opening/closing</p>
                    </div>
                </a>
            </div>

            <!-- Registers Reports -->
            <div class="report-group" id="registers">
                <h2 class="mb-4">Register Reports</h2>
                <a href="{{ route('reports.generate', 'summary_registers') }}" class="report-link">
                    <i class="bi bi-calendar"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Sales totals for each register</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'graphical_summary_registers') }}" class="report-link">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of register sales</p>
                    </div>
                </a>
            </div>

            <!-- Store Account Reports -->
            <div class="report-group" id="store_accounts">
                <h2 class="mb-4">Store Account Reports</h2>
                <div class="mt-4">
                    <h4 class="mb-3 text-primary">Customers</h4>
                    <a href="{{ route('reports.generate', 'store_account_statements') }}" class="report-link">
                        <i class="bi bi-file-earmark-person"></i>
                        <div>
                            <strong>Store Account Statements</strong>
                            <p class="mb-0 text-muted small">Generate statements for customer accounts</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'summary_store_accounts') }}" class="report-link">
                        <i class="bi bi-credit-card-2-front"></i>
                        <div>
                            <strong>Summary Reports</strong>
                            <p class="mb-0 text-muted small">Overall store account balances</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'specific_customer_store_account') }}" class="report-link">
                        <i class="bi bi-person-lines-fill"></i>
                        <div>
                            <strong>Detailed Reports</strong>
                            <p class="mb-0 text-muted small">Detailed transaction log for a specific customer</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'store_account_activity') }}" class="report-link">
                        <i class="bi bi-activity"></i>
                        <div>
                            <strong>Activity</strong>
                            <p class="mb-0 text-muted small">Log of all store account activity</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'store_account_activity_summary') }}" class="report-link">
                        <i class="bi bi-file-spreadsheet"></i>
                        <div>
                            <strong>Activity Summary Report</strong>
                            <p class="mb-0 text-muted small">Summary of store account activity</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'store_account_outstanding') }}" class="report-link">
                        <i class="bi bi-graph-down"></i>
                        <div>
                            <strong>Outstanding Sales</strong>
                            <p class="mb-0 text-muted small">Report on outstanding store account balances</p>
                        </div>
                    </a>
                </div>
                <div class="mt-4 pt-4 border-top">
                    <h4 class="mb-3 text-primary">Suppliers</h4>
                    <a href="{{ route('reports.generate', 'supplier_store_account_statements') }}" class="report-link">
                        <i class="bi bi-file-earmark-person"></i>
                        <div>
                            <strong>Store Account Statements</strong>
                            <p class="mb-0 text-muted small">Generate statements for supplier accounts</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'supplier_summary_store_accounts') }}" class="report-link">
                        <i class="bi bi-credit-card-2-front"></i>
                        <div>
                            <strong>Summary Reports</strong>
                            <p class="mb-0 text-muted small">Overall supplier account balances</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'supplier_specific_store_account') }}" class="report-link">
                        <i class="bi bi-person-lines-fill"></i>
                        <div>
                            <strong>Detailed Reports</strong>
                            <p class="mb-0 text-muted small">Detailed transaction log for a specific supplier</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'supplier_store_account_activity') }}" class="report-link">
                        <i class="bi bi-activity"></i>
                        <div>
                            <strong>Activity</strong>
                            <p class="mb-0 text-muted small">Log of all supplier account activity</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'supplier_store_account_activity_summary') }}"
                        class="report-link">
                        <i class="bi bi-file-spreadsheet"></i>
                        <div>
                            <strong>Activity Summary Report</strong>
                            <p class="mb-0 text-muted small">Summary of supplier account activity</p>
                        </div>
                    </a>
                    <a href="{{ route('reports.generate', 'supplier_store_account_outstanding') }}" class="report-link">
                        <i class="bi bi-graph-down"></i>
                        <div>
                            <strong>Outstanding Recv</strong>
                            <p class="mb-0 text-muted small">Report on outstanding supplier account balances</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Suppliers Reports -->
            <div class="report-group" id="suppliers">
                <h2 class="mb-4">Supplier Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_suppliers') }}" class="report-link">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual representation of supplier performance</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_suppliers') }}" class="report-link">
                    <i class="bi bi-truck"></i>
                    <div>
                        <strong>Summary Suppliers</strong>
                        <p class="mb-0 text-muted small">Sales performance grouped by supplier</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'specific_supplier') }}" class="report-link">
                    <i class="bi bi-clipboard-data"></i>
                    <div>
                        <strong>Detailed Supplier Report</strong>
                        <p class="mb-0 text-muted small">Detailed sales for a specific supplier</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'specific_supplier_summary') }}" class="report-link">
                    <i class="bi bi-list-task"></i>
                    <div>
                        <strong>Summary Items</strong>
                        <p class="mb-0 text-muted small">Item performance for a specific supplier</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'graphical_summary_suppliers_receivings') }}" class="report-link">
                    <i class="bi bi-bar-chart-steps"></i>
                    <div>
                        <strong>Graphical Receiving Reports</strong>
                        <p class="mb-0 text-muted small">Visual breakdown of inventory received from suppliers</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_suppliers_receivings') }}" class="report-link">
                    <i class="bi bi-receipt"></i>
                    <div>
                        <strong>Summary Receiving Reports</strong>
                        <p class="mb-0 text-muted small">Receiving totals grouped by supplier</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'specific_supplier_receivings') }}" class="report-link">
                    <i class="bi bi-calendar"></i>
                    <div>
                        <strong>Detailed Receiving Reports</strong>
                        <p class="mb-0 text-muted small">Detailed log of inventory received from a supplier</p>
                    </div>
                </a>
            </div>

            <!-- Suspended Sales Reports -->
            <div class="report-group" id="suspended_sales">
                <h2 class="mb-4">Suspended Sales Reports</h2>
                <a href="{{ route('reports.generate', 'detailed_suspended_sales') }}" class="report-link">
                    <i class="bi bi-pause-circle"></i>
                    <div>
                        <strong>Detailed Reports</strong>
                        <p class="mb-0 text-muted small">Detailed log of all sales currently suspended</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'layaway_statements') }}" class="report-link">
                    <i class="bi bi-receipt"></i>
                    <div>
                        <strong>Layaway Statements</strong>
                        <p class="mb-0 text-muted small">Generate statements for sales on layaway</p>
                    </div>
                </a>
            </div>

            <!-- Tags Reports -->
            <div class="report-group" id="tags">
                <h2 class="mb-4">Tag Reports</h2>
                <a href="{{ route('reports.generate', 'graphical_summary_tags') }}" class="report-link">
                    <i class="bi bi-bar-chart"></i>
                    <div>
                        <strong>Graphical Reports</strong>
                        <p class="mb-0 text-muted small">Visual breakdown of sales by tag</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_tags') }}" class="report-link">
                    <i class="bi bi-tags"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Sales performance grouped by tag</p>
                    </div>
                </a>
            </div>

            <!-- Tax Reports -->
            <div class="report-group" id="taxes">
                <h2 class="mb-4">Tax Reports</h2>
                <a href="{{ route('reports.generate', 'summary_taxes') }}" class="report-link">
                    <i class="bi bi-calculator"></i>
                    <div>
                        <strong>Summary Taxes</strong>
                        <p class="mb-0 text-muted small">Total taxes collected grouped by tax type</p>
                    </div>
                </a>
            </div>

            <!-- Tiers Reports -->
            <div class="report-group" id="tiers">
                <h2 class="mb-4">Tier Reports</h2>
                <a href="{{ route('reports.generate', 'summary_tiers') }}" class="report-link">
                    <i class="bi bi-layers"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Sales performance for each pricing tier</p>
                    </div>
                </a>
            </div>

            <!-- Timeclock Reports -->
            <div class="report-group" id="timeclock">
                <h2 class="mb-4">Timeclock Reports</h2>
                <a href="{{ route('reports.generate', 'time_off') }}" class="report-link">
                    <i class="bi bi-calendar-x"></i>
                    <div>
                        <strong>Time Off Reports</strong>
                        <p class="mb-0 text-muted small">Log of employee time off requests</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'summary_timeclock') }}" class="report-link">
                    <i class="bi bi-clock"></i>
                    <div>
                        <strong>Summary Reports</strong>
                        <p class="mb-0 text-muted small">Total hours worked by employees</p>
                    </div>
                </a>
                <a href="{{ route('reports.generate', 'detailed_timeclock') }}" class="report-link">
                    <i class="bi bi-list-task"></i>
                    <div>
                        <strong>Detailed Reports</strong>
                        <p class="mb-0 text-muted small">Detailed log of employee punch in/out times</p>
                    </div>
                </a>
            </div>

            <div id="emptyMessage" class="empty-selection d-none">
                <i class="bi bi-arrow-left-circle"></i>
                <p>Select a category to view available reports</p>
            </div>
        </main>
    </div>

@endsection

    @push('scripts')
        <script>
            document.querySelectorAll('.menu-item').forEach(button => {
                button.addEventListener('click', () => {
                    // Toggle active class on buttons
                    document.querySelectorAll('.menu-item').forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');

                    // Toggle visibility of report groups
                    const target = button.getAttribute('data-target');
                    document.querySelectorAll('.report-group').forEach(group => group.classList.remove(
                        'active'));

                    const activeGroup = document.getElementById(target);
                    if (activeGroup) {
                        activeGroup.classList.add('active');
                        document.getElementById('emptyMessage').classList.add('d-none');
                    } else {
                        document.getElementById('emptyMessage').classList.remove('d-none');
                    }
                });
            });
        </script>
    @endpush