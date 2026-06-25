@extends('layouts.app')

@section('title', 'Items')
@section('page-title', 'Inventory / Items')

@push('styles')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

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

  /* Column Gear Dropdown Styles */
  .col-config-dropdown {
    width: 250px;
    padding: 0;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
  }
  .dropdown-header-config {
    background: var(--gray-50);
    padding: 10px 15px;
    font-weight: 700;
    font-size: 13px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .dropdown-header-config #reset_to_default {
    font-size: 12px;
    color: var(--primary);
    cursor: pointer;
    text-decoration: none;
  }
  .sort-list {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 400px;
    overflow-y: auto;
  }
  .sort-item {
    display: flex;
    align-items: center;
    padding: 8px 15px;
    border-bottom: 1px solid var(--gray-50);
    transition: background 0.2s;
  }
  .sort-item:hover {
    background: var(--gray-50);
  }
  .sort-item label {
    flex: 1;
    margin-left: 10px;
    margin-bottom: 0;
    font-size: 13px;
    cursor: pointer;
  }
  .handle {
    cursor: move;
    color: var(--gray-400);
    font-size: 16px;
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
        <form method="get" action="{{ route('items.index') }}" class="d-flex gap-2 flex-grow-1">
          <input type="text" name="search" class="search-input" placeholder="Search Items" value="{{ request('search') }}" />
          <select name="category" class="search-input" style="max-width:180px;flex:none;" onchange="this.form.submit()">
            <option value="all">All Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
          </select>
          <button type="submit" class="btn-search"><i class="bi bi-search"></i> Search</button>
          @if(request('search') || request('category'))
            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center text-decoration-none" style="border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13.5px;">Clear</a>
          @endif
        </form>
      </div>
      <div class="toolbar-actions">
        <a class="btn-new-customer text-decoration-none" href="{{ route('items.create') }}"><i class="bi bi-plus-lg"></i> New Item</a>

        <div class="dropdown d-inline-block">
          <button class="btn-icon-outline dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="bi bi-gear"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end col-config-dropdown p-0">
            <div class="dropdown-header-config">
              <span>Column Configuration</span>
              <a id="reset_to_default"><i class="bi bi-arrow-clockwise"></i> Reset</a>
            </div>
            <form id="config_columns">
              <ul id="sortable_columns" class="sort-list">
                @foreach($all_columns as $col_key => $col_info)
                  @php $checked = in_array($col_key, $selected_columns) ? 'checked' : ''; @endphp
                  <li class="sort-item" data-id="{{ $col_key }}">
                    <input type="checkbox" class="custom-checkbox column-checkbox" id="col_{{ $col_key }}" value="{{ $col_key }}" {{ $checked }}>
                    <label for="col_{{ $col_key }}">{{ $col_info['label'] }}</label>
                    <span class="handle"><i class="bi bi-list"></i></span>
                  </li>
                @endforeach
              </ul>
            </form>
          </div>
        </div>

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
              @foreach($selected_columns as $col_key)
                <th>{{ $all_columns[$col_key]['label'] }}</th>
              @endforeach
              <th style="width: 100px;">Action</th>
            </tr>
          </thead>
          <tbody id="itemsTableBody">
            @forelse($items as $item)
            <tr>
              <td><input type="checkbox" class="custom-checkbox row-checkbox" onchange="checkSelection()" /></td>

              @foreach($selected_columns as $col_key)
                @if($col_key == 'item_id')
                  <td>{{ $item->item_id }}</td>
                @elseif($col_key == 'name')
                  <td>
                    <div class="item-info">
                        {{-- @if($item->image_file_id)
                            <img src="{{ route('app_files.view', $item->image_file_id) }}" alt="{{ $item->name }}" class="item-img" />
                        @else
                            <div class="item-img d-flex align-items-center justify-content-center text-muted" style="font-size: 10px; background: var(--gray-100);">IMG</div>
                        @endif --}}
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-flower2"></i>
                            {{-- <i class="bi bi-leaf-fill"></i> --}}
                        </div>
                        <div>
                            <div class="item-name"><a href="{{ route('items.edit', $item->item_id) }}" class="text-primary text-decoration-underline">{{ $item->name }}</a></div>
                        </div>
                    </div>
                  </td>
                @elseif($col_key == 'category')
                  <td>{{ $item->category_name ?? '—' }}</td>
                @elseif($col_key == 'cost_price')
                  <td class=""><span class="fw-medium">{{ $item->cost_price !== null ? $baseCurrencySymbol . number_format((float) $item->cost_price, $baseDecimals) : '—' }}</span></td>
                @elseif($col_key == 'unit_price')
                  <td class=""><span class="fw-medium">{{ $item->unit_price !== null ? $baseCurrencySymbol . number_format((float) $item->unit_price, $baseDecimals) : '—' }}</span></td>
                @elseif($col_key == 'quantity')
                  <td class=""><span class="fw-medium">{{ $item->location_quantity ?? $item->default_quantity ?? '—' }}</span></td>
                @elseif($col_key == 'reorder_level')
                  <td class=""><span class="fw-medium">{{ $item->reorder_level ?? '—' }}</span></td>
                @elseif($col_key == 'item_number')
                  <td>{{ $item->item_number ?: ($item->product_id ?: '—') }}</td>
                @endif
              @endforeach

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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
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

  $(document).ready(function() {
    console.log('[ColumnConfig] Document ready');

    var sortableInitialized = false;
    var $gearBtn = $('.btn-icon-outline.dropdown-toggle.no-caret');

    // Init sortable when dropdown is shown (needs visible elements for jQuery UI)
    $gearBtn.closest('.dropdown').on('shown.bs.dropdown', function() {
      console.log('[ColumnConfig] Dropdown shown');
      if (!sortableInitialized) {
        try {
          $("#sortable_columns").sortable({
            handle: ".handle",
            update: function(event, ui) {
              console.log('[ColumnConfig] Sortable drag update');
              saveColumnPrefs();
            }
          });
          sortableInitialized = true;
          console.log('[ColumnConfig] Sortable initialized');
        } catch (e) {
          console.error('[ColumnConfig] Sortable init error:', e);
        }
      }
    });

    // Handle column checkbox changes
    $(".column-checkbox").on('change', function(e) {
      console.log('[ColumnConfig] Checkbox changed:', $(this).val(), 'checked:', $(this).is(':checked'));
      saveColumnPrefs();
    });

    // Reset columns
    $("#reset_to_default").on('click', function(e) {
      e.preventDefault();
      console.log('[ColumnConfig] Reset clicked');
      $.ajax({
        url: "{{ route('items.reset_column_prefs') }}",
        type: 'GET',
        success: function() {
          console.log('[ColumnConfig] Reset OK, reloading');
          location.reload();
        },
        error: function(xhr) {
          console.error('[ColumnConfig] Reset failed:', xhr.responseJSON || xhr.statusText);
          Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to reset column preferences.', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
        }
      });
    });

    function saveColumnPrefs() {
      let columns = [];
      let visibleColumns = [];

      $("#sortable_columns li").each(function() {
        let checkbox = $(this).find('input[type="checkbox"]');
        let colValue = checkbox.val();
        columns.push(colValue);
        if (checkbox.is(':checked')) {
          visibleColumns.push(colValue);
        }
      });

      console.log('[ColumnConfig] Saving prefs - order:', columns, 'visible:', visibleColumns);

      $.ajax({
        url: "{{ route('items.save_column_prefs') }}",
        type: 'POST',
        data: {
          _token: "{{ csrf_token() }}",
          columns: columns,
          visible_columns: visibleColumns
        },
        success: function(response) {
          console.log('[ColumnConfig] Save OK, reloading');
          location.reload();
        },
        error: function(xhr) {
          console.error('[ColumnConfig] Save failed:', xhr.responseJSON || xhr.statusText);
          Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save column preferences. Please try again.', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
        }
      });
    }
  });


</script>
@endpush
