@extends('layouts.app')

@section('title', 'Receivings')
@section('page-title', 'Inventory / Receivings')

@push('styles')
<style>
  .customers-toolbar {
    background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
    padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; box-shadow: var(--shadow-xs);
  }
  .search-wrap { display: flex; gap: 12px; flex: 1; max-width: 640px; }
  .search-input {
    flex: 1; background: var(--gray-50); border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13.5px;
    outline: none; transition: var(--transition);
  }
  .search-input:focus { border-color: var(--primary); background: #fff; }
  .btn-search {
    background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm);
    padding: 8px 20px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;
    transition: var(--transition);
  }
  .btn-search:hover { background: var(--primary-dark); }
  .btn-new-order {
    background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm);
    padding: 8px 16px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;
    transition: var(--transition);
  }
  .btn-new-order:hover { background: var(--primary-dark); }

  .order-tabs { display: flex; gap: 10px; margin-bottom: 16px; }
  .btn-tab {
    border: 1px solid var(--gray-200); background: #fff; padding: 6px 14px; border-radius: 999px;
    font-size: 13px; font-weight: 600; color: var(--gray-600); transition: var(--transition);
  }
  .btn-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }

  .table-card {
    background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs); overflow: hidden;
  }
  .custom-table { width: 100%; border-collapse: collapse; min-width: 900px; }
  .custom-table th {
    background: #fff; color: var(--gray-700); font-size: 12px; font-weight: 700;
    padding: 12px 20px; text-align: left; white-space: nowrap;
    border-bottom: 2px solid var(--gray-100);
  }
  .custom-table td {
    padding: 14px 20px; border-bottom: 1px solid var(--gray-100); font-size: 13.5px;
    color: var(--gray-700); vertical-align: middle; font-weight: 500;
  }
  .custom-table tr:last-child td { border-bottom: none; }
  .custom-table tr:hover { background: var(--gray-50); }
  .status-badge {
    padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; display: inline-block;
  }
  .status-open { background: #DCFCE7; color: #166534; }
  .status-close { background: #E2E8F0; color: #334155; }
  .row-actions { display: flex; align-items: center; gap: 10px; }
  .action-icon { cursor: pointer; transition: color .2s; font-size: 16px; }

  .modal-content-custom { border-radius: 16px; border: 1px solid var(--gray-200); }
  .modal-header-custom { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--gray-100); }
  .modal-title-custom { font-size: 16px; font-weight: 700; margin: 0; }
  .btn-close-custom { border: 0; background: transparent; font-size: 18px; color: var(--gray-500); }
  .supplier-search-input {
    width: 100%; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 10px 12px;
    font-size: 13.5px; outline: none; background: var(--gray-50); transition: var(--transition);
  }
  .supplier-search-input:focus { border-color: var(--primary); background: #fff; }
  .supplier-list { margin-top: 14px; display: flex; flex-direction: column; gap: 10px; }
  .supplier-item {
    border: 1px solid var(--gray-200); border-radius: 12px; padding: 12px 14px; display: flex;
    align-items: center; justify-content: space-between; cursor: pointer; transition: var(--transition);
  }
  .supplier-item:hover { border-color: var(--primary); background: var(--primary-soft); }
  .supplier-name { font-weight: 700; font-size: 13.5px; color: var(--gray-800); }
  .supplier-email { font-size: 12px; color: var(--gray-500); }

  .start-mode-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
  .start-mode-card {
    border: 1px solid var(--gray-200); border-radius: 14px; padding: 16px; text-align: left; cursor: pointer;
    transition: var(--transition); background: #fff;
  }
  .start-mode-card:hover { border-color: var(--primary); box-shadow: var(--shadow-xs); }
  .start-mode-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
  .icon-blue { background: #DBEAFE; color: #1D4ED8; }
  .icon-green { background: #DCFCE7; color: #166534; }
  .start-mode-title { font-weight: 700; font-size: 14px; color: var(--gray-800); }
  .start-mode-desc { font-size: 12.5px; color: var(--gray-500); }

  .pull-list-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .pull-list-actions { display: flex; gap: 10px; align-items: center; }
  .btn-cancel-custom {
    border: 1px solid var(--gray-200); background: #fff; color: var(--gray-600); border-radius: var(--radius-sm);
    padding: 8px 14px; font-weight: 600; font-size: 13px;
  }
  .btn-primary-custom {
    border: 0; background: var(--primary); color: #fff; border-radius: var(--radius-sm);
    padding: 8px 16px; font-weight: 700; font-size: 13px;
  }
  .pull-list-search { display: flex; gap: 10px; margin-top: 14px; }
  .pull-list-search input {
    flex: 1; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 13px;
  }
  .pull-results { margin-top: 10px; max-height: 200px; overflow: auto; border: 1px solid var(--gray-100); border-radius: 10px; }
  .pull-result-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid var(--gray-100); }
  .pull-result-row:last-child { border-bottom: 0; }
  .pull-result-title { font-weight: 600; font-size: 13px; color: var(--gray-800); }
  .pull-result-meta { font-size: 12px; color: var(--gray-500); }

  .pull-table { width: 100%; border-collapse: collapse; }
  .pull-table th {
    background: var(--gray-50); font-size: 12px; font-weight: 700; color: var(--gray-600);
    padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--gray-100);
  }
  .pull-table td { padding: 10px 12px; border-bottom: 1px solid var(--gray-100); font-size: 13px; }
  .pull-table input[type="number"] { width: 90px; }

  @media (max-width: 900px) {
    .customers-toolbar { flex-direction: column; align-items: stretch; gap: 16px; }
    .search-wrap { max-width: 100%; flex-wrap: wrap; }
    .start-mode-grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')

<div class="container-fluid">
    <div class="customers-toolbar">
        <div class="search-wrap">
            <input type="text" class="search-input" placeholder="Search receivings by supplier / receiving #" />
            <button class="btn-search"><i class="bi bi-search"></i> Search</button>
        </div>
        <a href="{{ route('receivings.create') }}" class="btn-new-order text-decoration-none">
            <i class="bi bi-plus-lg"></i> New Receiving
        </a>
    </div>

    <div class="order-tabs">
        <button class="btn-tab">Open</button>
        <button class="btn-tab">Closed</button>
        <button class="btn-tab active">All</button>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Receiving ID</th>
                        <th>Supplier</th>
                        <th>Created Date</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receivings as $receiving)
                        <tr>
                            <td>#{{ $receiving->receiving_id }}</td>
                            <td>{{ $receiving->supplier->company_name ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($receiving->receiving_time)->format('M d, Y h:i A') }}</td>
                            <td>{{ $receiving->items->count() }} items</td>
                            <td>
                                                                @if($receiving->is_po)
                                    <span class="status-badge status-open bg-warning text-dark">
                                        PO
                                    </span>
                                @elseif($receiving->suspended)
                                    <span class="status-badge status-open bg-secondary text-white">
                                        Suspended
                                    </span>
                                @else
                                    <span class="status-badge status-close bg-success text-white">
                                        Received
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <i class="bi bi-eye action-icon text-primary" title="View"></i>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No receivings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection