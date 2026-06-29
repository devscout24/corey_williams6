@extends('layouts.app')

@section('title', 'Suppliers')
@section('page-title', 'Contacts / Suppliers')

@push('styles')
<style>
  .customers-toolbar {
    background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--gray-200);
    padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; box-shadow: var(--shadow-xs);
  }
  .search-wrap { display: flex; gap: 12px; flex: 1; max-width: 400px; }
  .search-input {
    flex: 1; background: var(--gray-50); border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13.5px;
    outline: none; transition: var(--transition);
  }
  .search-input:focus { border-color: var(--primary); background: #fff; }
  .btn-search {
    background: var(--primary); color: #fff; border: none;
    border-radius: var(--radius-sm); padding: 8px 20px;
    font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: var(--transition);
  }
  .btn-search:hover { background: var(--primary-dark); }
  .toolbar-actions { display: flex; gap: 12px; }
  .btn-new-customer {
    background: var(--primary); color: #fff; border: none;
    border-radius: var(--radius-sm); padding: 8px 16px;
    font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: var(--transition);
  }
  .btn-new-customer:hover { background: var(--primary-dark); }
  .btn-icon-outline {
    background: #fff; border: 1px solid var(--primary); color: var(--primary);
    border-radius: var(--radius-sm); width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center; font-size: 18px; transition: var(--transition);
  }
  .btn-icon-outline:hover { background: var(--primary-soft); }

  .table-card {
    background: #fff; border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200); box-shadow: var(--shadow-xs); overflow: hidden;
  }
  .table-actions {
    padding: 16px 20px; border-bottom: 1px solid var(--gray-100); display: flex; gap: 12px;
  }
  .btn-action-sm {
    font-size: 12.5px; font-weight: 600; padding: 6px 14px; border-radius: var(--radius-sm);
    display: inline-flex; align-items: center; gap: 6px; border: none; color: #fff;
    transition: var(--transition); cursor: pointer;
  }
  .btn-delete { background: var(--danger); }
  .btn-delete:hover { background: #DC2626; }
  .btn-clear { background: var(--orange); }
  .btn-clear:hover { background: #EA580C; }

  .custom-table { width: 100%; border-collapse: collapse; min-width: 800px; }
  .custom-table th {
    background: #fff; color: var(--gray-700); font-size: 12px; font-weight: 700;
    padding: 12px 20px; text-align: left; white-space: nowrap;
    border-bottom: 2px solid var(--gray-100);
  }
  .custom-table td {
    padding: 14px 20px; border-bottom: 1px solid var(--gray-100);
    font-size: 13.5px; color: var(--gray-700); vertical-align: middle; font-weight: 500;
  }
  .custom-table tr:last-child td { border-bottom: none; }
  .custom-table tr:hover { background: var(--gray-50); }

  .custom-checkbox {
    width: 18px; height: 18px; border-radius: 4px; border: 1.5px solid var(--gray-300);
    appearance: none; outline: none; cursor: pointer; position: relative;
    transition: var(--transition); background: #fff;
    display: inline-flex; align-items: center; justify-content: center; margin: 0;
  }
  .custom-checkbox:checked { background: var(--primary); border-color: var(--primary); }
  .custom-checkbox:checked::after {
    content: '\F26A'; font-family: 'bootstrap-icons'; color: #fff; font-size: 13px; position: absolute;
  }

  .user-info { display: flex; align-items: center; gap: 14px; }
  .user-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
  .user-name { font-size: 14px; font-weight: 700; color: var(--gray-900); }
  .user-id { font-size: 11.5px; color: var(--gray-400); margin-top: 2px; font-weight: 600; }

  .row-actions { display: flex; align-items: center; gap: 14px; color: var(--gray-400); }
  .action-icon { cursor: pointer; transition: color .2s; font-size: 16px; border: 1px solid var(--gray-200); padding: 4px 8px; border-radius: var(--radius-sm); }
  .action-icon:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }

  .table-responsive { overflow-x: auto; }

  .col-config-dropdown {
    width: 250px; padding: 0; border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg); border: 1px solid var(--gray-200);
  }
  .dropdown-header-config {
    background: var(--gray-50); padding: 10px 15px; font-weight: 700; font-size: 13px;
    border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;
  }
  .dropdown-header-config #reset_to_default {
    font-size: 12px; color: var(--primary); cursor: pointer; text-decoration: none;
  }
  .sort-list { list-style: none; padding: 0; margin: 0; max-height: 400px; overflow-y: auto; }
  .sort-item {
    display: flex; align-items: center; padding: 8px 15px;
    border-bottom: 1px solid var(--gray-50); transition: background 0.2s;
  }
  .sort-item:hover { background: var(--gray-50); }
  .sort-item label { flex: 1; margin-left: 10px; margin-bottom: 0; font-size: 13px; cursor: pointer; }
  .handle { cursor: move; color: var(--gray-400); font-size: 16px; }

  @media (max-width: 768px) {
    .customers-toolbar { flex-direction: column; align-items: stretch; gap: 16px; }
    .search-wrap { max-width: 100%; }
    .toolbar-actions { justify-content: flex-end; }
  }
</style>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
@endpush

@section('content')
<div class="container-fluid p-0">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="customers-toolbar">
      <div class="search-wrap">
        <form method="get" action="{{ route('suppliers.index') }}" class="d-flex gap-2 flex-grow-1">
          <input type="text" name="search" class="search-input" placeholder="Search Suppliers" value="{{ request('search') }}" />
          <button type="submit" class="btn-search"><i class="bi bi-search"></i> Search</button>
          @if(request('search'))
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center text-decoration-none" style="border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13.5px;">Clear</a>
          @endif
        </form>
      </div>
      <div class="toolbar-actions">
        <a href="{{ route('suppliers.create') }}" class="btn-new-customer text-decoration-none"><i class="bi bi-plus-lg"></i> New Supplier</a>
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
        <div class="dropdown" style="margin-left: 10px;">
          <button class="btn-icon-outline dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots"></i></button>
          <ul class="dropdown-menu dropdown-menu-end" style="min-width: 180px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid var(--gray-200); padding: 6px 0;">
            <li><a class="dropdown-item" href="{{ route('suppliers.export', ['format' => 'csv']) }}" style="font-size: 13.5px; padding: 8px 16px; font-weight: 500;"><i class="bi bi-filetype-csv" style="margin-right: 8px; color: #10b981;"></i>Export CSV</a></li>
            <li><a class="dropdown-item" href="{{ route('suppliers.export', ['format' => 'xls']) }}" style="font-size: 13.5px; padding: 8px 16px; font-weight: 500;"><i class="bi bi-file-earmark-excel" style="margin-right: 8px; color: #059669;"></i>Export XLS</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-actions" id="bulkActions" style="display: none;">
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
              <th style="width: 80px;">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($suppliers as $supplier)
            <tr>
              <td><input type="checkbox" class="custom-checkbox row-checkbox" onchange="checkSelection()" /></td>
              @foreach($selected_columns as $col_key)
                @if($col_key == 'person_id')
                  <td>{{ $supplier->person_id }}</td>
                @elseif($col_key == 'company_name')
                  <td>{{ $supplier->company_name ?? '—' }}</td>
                @elseif($col_key == 'contact_name')
                  <td>
                    <div class="user-info">
                      <div class="user-avatar d-flex align-items-center justify-content-center text-muted" style="background: var(--gray-100);">
                        <i class="bi bi-person"></i>
                      </div>
                      <div>
                        <div class="user-name">{{ $supplier->first_name }} {{ $supplier->last_name }}</div>
                        <div class="user-id">ID:{{ $supplier->person_id }}</div>
                      </div>
                    </div>
                  </td>
                @elseif($col_key == 'email')
                  <td>{{ $supplier->email ?? '—' }}</td>
                @elseif($col_key == 'phone_number')
                  <td>{{ $supplier->phone_number ?? '—' }}</td>
                @elseif($col_key == 'fax_number')
                  <td>{{ $supplier->fax_number ?? '—' }}</td>
                @elseif($col_key == 'balance')
                  <td class="fw-bold {{ $supplier->balance > 0 ? 'text-danger' : 'text-success' }}">
                    ${{ number_format((float) $supplier->balance, 2) }}
                  </td>
                @elseif($col_key == 'account_number')
                  <td>{{ $supplier->account_number ?? '—' }}</td>
                @endif
              @endforeach
              <td>
                <div class="row-actions">
                  <a href="{{ route('suppliers.edit', $supplier->person_id) }}" class="text-decoration-none"><i class="bi bi-pencil-square action-icon"></i></a>
                  <form method="post" action="{{ route('suppliers.destroy', $supplier->person_id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Archive this supplier?')">
                      @csrf
                      @method('delete')
                      <button type="submit" class="p-0 border-0 bg-transparent text-danger"><i class="bi bi-trash3 action-icon text-danger border-0"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="{{ count($selected_columns) + 2 }}" class="text-center py-4 text-muted">No suppliers found.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    
    <div class="mt-3">
        {{ $suppliers->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
  $(document).ready(function() {
    var sortableInitialized = false;
    var $gearBtn = $('.btn-icon-outline.dropdown-toggle.no-caret');

    $gearBtn.closest('.dropdown').on('shown.bs.dropdown', function() {
      console.log('[ColumnConfig] Dropdown shown');
      if (!sortableInitialized) {
        try {
          $("#sortable_columns").sortable({
            handle: ".handle",
            update: function() { saveColumnPrefs(); }
          });
          sortableInitialized = true;
          console.log('[ColumnConfig] Sortable initialized');
        } catch (e) {
          console.error('[ColumnConfig] Sortable init error:', e);
        }
      }
    });

    $(".column-checkbox").on('change', function() {
      console.log('[ColumnConfig] Checkbox changed:', $(this).val(), 'checked:', $(this).is(':checked'));
      saveColumnPrefs();
    });

    $("#reset_to_default").on('click', function(e) {
      e.preventDefault();
      $.ajax({
        url: "{{ route('suppliers.reset_column_prefs') }}",
        type: 'GET',
        success: function() {
          location.reload();
        },
        error: function(xhr) {
          console.error('Failed to reset column prefs:', xhr.responseJSON || xhr.statusText);
          Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to reset column preferences.', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
        }
      });
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
      url: "{{ route('suppliers.save_column_prefs') }}",
      type: 'POST',
      data: {
        _token: "{{ csrf_token() }}",
        columns: columns,
        visible_columns: visibleColumns
      },
      success: function() { location.reload(); },
      error: function(xhr) {
        console.error('Failed to save column prefs:', xhr.responseJSON || xhr.statusText);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save column preferences. Please try again.', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
      }
    });
  }

  function checkSelection() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectAll = document.getElementById('selectAll');
    
    let checkedCount = 0;
    checkboxes.forEach(cb => { if (cb.checked) checkedCount++; });

    bulkActions.style.display = checkedCount > 0 ? 'flex' : 'none';
    selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
  }

  function toggleAll(source) {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = source.checked);
    checkSelection();
  }

  function clearSelection() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    checkSelection();
  }
</script>
@endpush
