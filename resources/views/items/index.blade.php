@extends('layouts.app')

@section('title', 'Items')
@section('page-title', 'Inventory / Items')

@push('styles')
<style>
  /* Items Page Styles */
  .customers-toolbar {
    background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
    padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; box-shadow: var(--shadow-xs);
  }
  .search-wrap { display: flex; gap: 12px; flex: 1; max-width: 600px; }
  .search-input {
    flex: 1; max-width: 250px; background: var(--gray-50); border: 1px solid var(--gray-200);
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
  
  .toolbar-select {
    background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    padding: 8px 30px 8px 16px; font-size: 13.5px; outline: none; transition: var(--transition); appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%2364748B' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 6l5 5 5-5'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center; background-size: 10px 10px;
    color: var(--gray-700); min-width: 120px;
  }
  .toolbar-select:focus { border-color: var(--primary); background-color: #fff; }

  .toolbar-actions { display: flex; gap: 12px; }
  .btn-new-customer {
    background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm);
    padding: 8px 16px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;
    transition: var(--transition);
  }
  .btn-new-customer:hover { background: var(--primary-dark); }
  .btn-icon-outline {
    background: #fff; border: 1px solid var(--primary); color: var(--primary);
    border-radius: var(--radius-sm); width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center; font-size: 18px; transition: var(--transition);
  }
  .btn-icon-outline:hover { background: var(--primary-soft); }

  /* Table Card */
  .table-card {
    background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs); overflow: hidden;
  }
  .table-actions {
    padding: 16px 20px; border-bottom: 1px solid var(--gray-100); display: flex; gap: 12px; flex-wrap: wrap;
  }
  .btn-action-sm {
    font-size: 12.5px; font-weight: 600; padding: 6px 14px; border-radius: var(--radius-sm);
    display: inline-flex; align-items: center; gap: 6px; border: none; color: #fff;
    transition: var(--transition); cursor: pointer;
  }
  .btn-action-blue { background: var(--primary); }
  .btn-action-blue:hover { background: var(--primary-dark); }
  .btn-delete { background: var(--danger); }
  .btn-delete:hover { background: #DC2626; }
  .btn-clear { background: var(--orange); }
  .btn-clear:hover { background: #EA580C; }

  /* Table */
  .custom-table { width: 100%; border-collapse: collapse; min-width: 900px; }
  .custom-table th {
    background: var(--primary-light); color: var(--gray-700); font-size: 12px; font-weight: 700;
    padding: 12px 20px; text-align: left; white-space: nowrap;
  }
  .custom-table td {
    padding: 14px 20px; border-bottom: 1px solid var(--gray-100); font-size: 13.5px;
    color: var(--gray-700); vertical-align: middle; font-weight: 500;
  }
  .custom-table tr:last-child td { border-bottom: none; }
  .custom-table tr:hover { background: var(--gray-50); }

  .custom-checkbox {
    width: 18px; height: 18px; border-radius: 4px; border: 1.5px solid var(--gray-300);
    appearance: none; outline: none; cursor: pointer; position: relative; transition: var(--transition);
    background: #fff; display: flex; align-items: center; justify-content: center; margin: 0;
  }
  .custom-checkbox:checked { background: var(--primary); border-color: var(--primary); }
  .custom-checkbox:checked::after {
    content: '\F26A'; font-family: 'bootstrap-icons'; color: #fff; font-size: 13px; position: absolute;
  }

  .item-info { display: flex; align-items: center; gap: 14px; }
  .item-img { width: 42px; height: 42px; border-radius: 8px; object-fit: cover; background: var(--gray-100); }
  .item-name { font-size: 14px; font-weight: 700; color: var(--gray-900); }
  .item-id { font-size: 11.5px; color: var(--gray-400); margin-top: 2px; font-weight: 600; }

  .row-actions { display: flex; align-items: center; gap: 14px; color: var(--gray-400); }
  .action-icon { cursor: pointer; transition: color .2s; font-size: 16px; border: 1px solid transparent; padding: 4px 8px; border-radius: var(--radius-sm); }
  .action-icon:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }

  .table-responsive { overflow-x: auto; }

  @media (max-width: 900px) {
    .customers-toolbar { flex-direction: column; align-items: stretch; gap: 16px; }
    .search-wrap { max-width: 100%; flex-wrap: wrap; }
    .search-input { max-width: 100%; }
    .toolbar-actions { justify-content: flex-end; }
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <!-- Toolbar -->
    <div class="customers-toolbar">
      <div class="search-wrap">
        <input type="text" class="search-input" placeholder="Search Items" />
        <button class="btn-search"><i class="bi bi-search"></i> Search</button>
        <select class="toolbar-select">
          <option>Fields:</option>
        </select>
        <select class="toolbar-select">
          <option>Category:</option>
        </select>
      </div>
      <div class="toolbar-actions">
        <a class="btn-new-customer text-decoration-none" href="{{ route('items.create') }}"><i class="bi bi-plus-lg"></i> New Item</a>
        <button class="btn-icon-outline"><i class="bi bi-three-dots"></i></button>
      </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-actions" id="bulkActions" style="display: none;">
        <button class="btn-action-sm btn-action-blue"><i class="bi bi-pencil-square"></i> Edit</button>
        <button class="btn-action-sm btn-action-blue"><i class="bi bi-check2-square"></i> Select All</button>
        <button class="btn-action-sm btn-action-blue"><i class="bi bi-tags"></i> Labels <i class="bi bi-chevron-down ms-1" style="font-size: 10px;"></i></button>
        <button class="btn-action-sm btn-delete"><i class="bi bi-trash3"></i> Delete</button>
        <button class="btn-action-sm btn-clear" onclick="clearSelection()"><i class="bi bi-x-circle"></i> Clear Selection</button>
      </div>

      <div class="table-responsive">
        <table class="custom-table">
          <thead>
            <tr>
              <th style="width: 50px;">
                <input type="checkbox" class="custom-checkbox" id="selectAll" onchange="toggleAll(this)" />
              </th>
              <th>Name</th>
              <th>Category</th>
              <th>Cost Price</th>
              <th>Selling Price</th>
              <th>Quantity</th>
              <th>Threshold</th>
              <th>UPC/EAN/ISBN</th>
              <th style="width: 100px;">Action</th>
            </tr>
          </thead>
          <tbody id="itemsTableBody">
            @forelse($items as $item)
            <tr>
              <td><input type="checkbox" class="custom-checkbox row-checkbox" onchange="checkSelection()" /></td>
              <td>
                <div class="item-info">
                  @if($item->image_file_id)
                    <img src="{{ route('app_files.view', $item->image_file_id) }}" alt="{{ $item->name }}" class="item-img" />
                  @else
                    <div class="item-img d-flex align-items-center justify-content-center text-muted" style="font-size: 10px; background: var(--gray-100);">IMG</div>
                  @endif
                  <div>
                    <div class="item-name">{{ $item->name }}</div>
                  </div>
                </div>
              </td>
              <td>{{ $item->category_name ?? '—' }}</td>
              <td>${{ number_format($item->cost_price ?? 0, 2) }}</td>
              <td>${{ number_format($item->unit_price ?? 0, 2) }}</td>
              <td>{{ number_format($item->location_quantity ?? $item->default_quantity ?? 0, 3) }}</td>
              <td>{{ number_format($item->reorder_level ?? 0, 3) }}</td>
              <td>{{ $item->item_number ?: ($item->product_id ?: '—') }}</td>
              <td>
                <div class="row-actions">
                  <a href="{{ route('items.edit', $item->item_id) }}" class="text-decoration-none"><i class="bi bi-pencil-square action-icon"></i></a>
                  <form method="post" action="{{ route('items.destroy', $item->item_id) }}" class="d-inline m-0 p-0 delete-form">
                    @csrf
                    @method('delete')
                    <button type="submit" class="p-0 border-0 bg-transparent text-danger"><i class="bi bi-trash3 action-icon text-danger"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9" class="text-center py-4 text-muted">No items found.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
        {{ $items->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
  function checkSelection() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectAll = document.getElementById('selectAll');
    
    let checkedCount = 0;
    checkboxes.forEach(cb => {
      if (cb.checked) checkedCount++;
    });

    if (checkedCount > 0) {
      bulkActions.style.display = 'flex';
    } else {
      bulkActions.style.display = 'none';
    }

    selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
  }

  function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    checkSelection();
  }

  function clearSelection() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAll = document.getElementById('selectAll');
    checkboxes.forEach(cb => cb.checked = false);
    selectAll.checked = false;
    checkSelection();
  }
</script>
@endpush
