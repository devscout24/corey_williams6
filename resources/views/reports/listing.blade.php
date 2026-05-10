@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@push('styles')
<style>
    .reports-container { display: grid; grid-template-columns: 300px 1fr; gap: 24px; }
    .report-menu { background: #fff; border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm); height: fit-content; max-height: calc(100vh - 150px); overflow-y: auto; }
    .report-menu h3 { font-size: 0.9rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-left: 8px; }
    .menu-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: var(--gray-700); font-weight: 500; transition: var(--transition); cursor: pointer; border: none; width: 100%; text-align: left; background: none; margin-bottom: 4px; }
    .menu-item:hover { background: var(--gray-50); color: var(--primary); }
    .menu-item.active { background: #000000; color: #ffffff; }
    .menu-item i { font-size: 1.1rem; }

    .report-selection { background: #fff; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm); min-height: 600px; }
    .report-group { display: none; }
    .report-group.active { display: block; }
    .report-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--gray-100); color: var(--gray-700); transition: var(--transition); text-decoration: none; }
    .report-link:last-child { border-bottom: none; }
    .report-link:hover { background: var(--gray-50); color: var(--primary); }
    .report-link i { font-size: 1.2rem; color: var(--gray-400); }
    
    .empty-selection { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; color: var(--gray-400); }
    .empty-selection i { font-size: 4rem; margin-bottom: 16px; }
</style>
@endpush

@section('content')
<div class="reports-container">
    <aside class="report-menu">
        <h3>Report Categories</h3>
        <button class="menu-item active" data-target="sales">
            <i class="bi bi-cart"></i> Sales
        </button>
        <button class="menu-item" data-target="customers">
            <i class="bi bi-people"></i> Customers
        </button>
        <button class="menu-item" data-target="items">
            <i class="bi bi-box"></i> Items
        </button>
        <button class="menu-item" data-target="item_kits">
            <i class="bi bi-boxes"></i> Item Kits
        </button>
        <button class="menu-item" data-target="employees">
            <i class="bi bi-person-badge"></i> Employees
        </button>
        <button class="menu-item" data-target="suppliers">
            <i class="bi bi-truck"></i> Suppliers
        </button>
        <button class="menu-item" data-target="inventory">
            <i class="bi bi-bar-chart"></i> Inventory
        </button>
        <button class="menu-item" data-target="receivings">
            <i class="bi bi-download"></i> Receivings
        </button>
        <button class="menu-item" data-target="payments">
            <i class="bi bi-cash-stack"></i> Payments
        </button>
        <button class="menu-item" data-target="expenses">
            <i class="bi bi-wallet2"></i> Expenses
        </button>
        <button class="menu-item" data-target="taxes">
            <i class="bi bi-calculator"></i> Taxes
        </button>
        <button class="menu-item" data-target="store_accounts">
            <i class="bi bi-credit-card"></i> Store Accounts
        </button>
        <button class="menu-item" data-target="giftcards">
            <i class="bi bi-card-checklist"></i> Giftcards
        </button>
        <button class="menu-item" data-target="profit_loss">
            <i class="bi bi-graph-up-arrow"></i> Profit & Loss
        </button>
    </aside>

    <main class="report-selection" id="reportSelection">
        <!-- Sales Reports -->
        <div class="report-group active" id="sales">
            <h2 class="mb-4">Sales Reports</h2>
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
            <a href="{{ route('reports.generate', 'summary_sales_time') }}" class="report-link">
                <i class="bi bi-clock-history"></i>
                <div>
                    <strong>Sales by Time</strong>
                    <p class="mb-0 text-muted small">Analyze sales performance by hour or day of week</p>
                </div>
            </a>
            <a href="{{ route('reports.generate', 'summary_journal') }}" class="report-link">
                <i class="bi bi-journal-text"></i>
                <div>
                    <strong>Summary Journal</strong>
                    <p class="mb-0 text-muted small">Daily journal of all sales and payments</p>
                </div>
            </a>
            <a href="{{ route('reports.generate', 'deleted_sales') }}" class="report-link">
                <i class="bi bi-trash"></i>
                <div>
                    <strong>Deleted Sales</strong>
                    <p class="mb-0 text-muted small">Review sales that have been deleted</p>
                </div>
            </a>
        </div>

        <!-- Customer Reports -->
        <div class="report-group" id="customers">
            <h2 class="mb-4">Customer Reports</h2>
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
            <a href="{{ route('reports.generate', 'new_customers') }}" class="report-link">
                <i class="bi bi-person-plus"></i>
                <div>
                    <strong>New Customers</strong>
                    <p class="mb-0 text-muted small">Report on customers added within a date range</p>
                </div>
            </a>
        </div>

        <!-- Items Reports -->
        <div class="report-group" id="items">
            <h2 class="mb-4">Items Reports</h2>
            <a href="{{ route('reports.generate', 'summary_items') }}" class="report-link">
                <i class="bi bi-box-seam"></i>
                <div>
                    <strong>Summary Items</strong>
                    <p class="mb-0 text-muted small">Sales performance grouped by item</p>
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
        </div>

        <!-- Item Kits Reports -->
        <div class="report-group" id="item_kits">
            <h2 class="mb-4">Item Kits Reports</h2>
            <a href="{{ route('reports.generate', 'summary_item_kits') }}" class="report-link">
                <i class="bi bi-boxes"></i>
                <div>
                    <strong>Summary Item Kits</strong>
                    <p class="mb-0 text-muted small">Sales performance grouped by item kit</p>
                </div>
            </a>
        </div>

        <!-- Employees Reports -->
        <div class="report-group" id="employees">
            <h2 class="mb-4">Employee Reports</h2>
            <a href="{{ route('reports.generate', 'summary_employees') }}" class="report-link">
                <i class="bi bi-person-badge"></i>
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

        <!-- Suppliers Reports -->
        <div class="report-group" id="suppliers">
            <h2 class="mb-4">Supplier Reports</h2>
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
            <a href="{{ route('reports.generate', 'detailed_inventory') }}" class="report-link">
                <i class="bi bi-list-check"></i>
                <div>
                    <strong>Detailed Inventory</strong>
                    <p class="mb-0 text-muted small">Individual inventory adjustments log</p>
                </div>
            </a>
        </div>

        <!-- Receivings Reports -->
        <div class="report-group" id="receivings">
            <h2 class="mb-4">Receivings Reports</h2>
            <a href="{{ route('reports.generate', 'detailed_receivings') }}" class="report-link">
                <i class="bi bi-download"></i>
                <div>
                    <strong>Detailed Receivings</strong>
                    <p class="mb-0 text-muted small">Detailed report of all inventory received</p>
                </div>
            </a>
            <a href="{{ route('reports.generate', 'summary_suppliers_receivings') }}" class="report-link">
                <i class="bi bi-truck"></i>
                <div>
                    <strong>Summary Suppliers Receivings</strong>
                    <p class="mb-0 text-muted small">Receiving totals grouped by supplier</p>
                </div>
            </a>
        </div>

        <!-- Payments Reports -->
        <div class="report-group" id="payments">
            <h2 class="mb-4">Payment Reports</h2>
            <a href="{{ route('reports.generate', 'summary_payments') }}" class="report-link">
                <i class="bi bi-cash-stack"></i>
                <div>
                    <strong>Summary Payments</strong>
                    <p class="mb-0 text-muted small">Totals for each payment type</p>
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

        <!-- Taxes Reports -->
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

        <!-- Store Accounts Reports -->
        <div class="report-group" id="store_accounts">
            <h2 class="mb-4">Store Account Reports</h2>
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
                    <strong>Summary Store Accounts</strong>
                    <p class="mb-0 text-muted small">Overall store account balances</p>
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

        <div id="emptyMessage" class="empty-selection d-none">
            <i class="bi bi-arrow-left-circle"></i>
            <p>Select a category to view available reports</p>
        </div>
    </main>
</div>

@push('scripts')
<script>
document.querySelectorAll('.menu-item').forEach(button => {
    button.addEventListener('click', () => {
        // Toggle active class on buttons
        document.querySelectorAll('.menu-item').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Toggle visibility of report groups
        const target = button.getAttribute('data-target');
        document.querySelectorAll('.report-group').forEach(group => group.classList.remove('active'));
        
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
@endsection
