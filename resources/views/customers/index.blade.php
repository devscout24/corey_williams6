@extends('layouts.app')

@section('title', 'Customers')
@section('page-title', 'Contacts / Customers')

@push('styles')
<style>
  /* Customers Page Styles */
  .customers-toolbar {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    box-shadow: var(--shadow-xs);
  }
  .search-wrap {
    display: flex; gap: 12px; flex: 1; max-width: 400px;
  }
  .search-input {
    flex: 1; background: var(--gray-50); border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); padding: 8px 16px; font-size: 13.5px;
    outline: none; transition: var(--transition);
  }
  .search-input:focus { border-color: var(--primary); background: #fff; }
  .btn-search {
    background: var(--primary); color: #fff; border: none;
    border-radius: var(--radius-sm); padding: 8px 20px;
    font-size: 13.5px; font-weight: 600; display: flex;
    align-items: center; gap: 8px; transition: var(--transition);
  }
  .btn-search:hover { background: var(--primary-dark); }
  .toolbar-actions { display: flex; gap: 12px; }
  .btn-new-customer {
    background: var(--primary); color: #fff; border: none;
    border-radius: var(--radius-sm); padding: 8px 16px;
    font-size: 13.5px; font-weight: 600; display: flex;
    align-items: center; gap: 8px; transition: var(--transition);
  }
  .btn-new-customer:hover { background: var(--primary-dark); }
  .btn-icon-outline {
    background: #fff; border: 1px solid var(--primary); color: var(--primary);
    border-radius: var(--radius-sm); width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; transition: var(--transition);
  }
  .btn-icon-outline:hover { background: var(--primary-soft); }

  /* Table Card */
  .table-card {
    background: #fff; border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200); box-shadow: var(--shadow-xs);
    overflow: hidden;
  }
  .table-actions {
    padding: 16px 20px; border-bottom: 1px solid var(--gray-100);
    display: flex; gap: 12px;
  }
  .btn-action-sm {
    font-size: 12.5px; font-weight: 600; padding: 6px 14px;
    border-radius: var(--radius-sm); display: inline-flex;
    align-items: center; gap: 6px; border: none; color: #fff;
    transition: var(--transition); cursor: pointer;
  }
  .btn-delete { background: var(--danger); }
  .btn-delete:hover { background: #DC2626; }
  .btn-clear { background: var(--orange); }
  .btn-clear:hover { background: #EA580C; }

  /* Table */
  .custom-table { width: 100%; border-collapse: collapse; min-width: 800px; }
  .custom-table th {
    background: #fff; color: var(--gray-700);
    font-size: 12px; font-weight: 700; padding: 12px 20px;
    text-align: left; white-space: nowrap;
    border-bottom: 2px solid var(--gray-100);
  }
  .custom-table td {
    padding: 14px 20px; border-bottom: 1px solid var(--gray-100);
    font-size: 13.5px; color: var(--gray-700); vertical-align: middle;
    font-weight: 500;
  }
  .custom-table tr:last-child td { border-bottom: none; }
  .custom-table tr:hover { background: var(--gray-50); }

  .custom-checkbox {
    width: 18px; height: 18px; border-radius: 4px;
    border: 1.5px solid var(--gray-300); appearance: none;
    outline: none; cursor: pointer; position: relative;
    transition: var(--transition); background: #fff;
    display: flex; align-items: center; justify-content: center;
    margin: 0;
  }
  .custom-checkbox:checked { background: var(--primary); border-color: var(--primary); }
  .custom-checkbox:checked::after {
    content: '\F26A'; font-family: 'bootstrap-icons';
    color: #fff; font-size: 13px; position: absolute;
  }

  .user-info { display: flex; align-items: center; gap: 14px; }
  .user-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
  .user-name { font-size: 14px; font-weight: 700; color: var(--gray-900); }
  .user-id { font-size: 11.5px; color: var(--gray-400); margin-top: 2px; font-weight: 600; }

  .row-actions { display: flex; align-items: center; gap: 14px; color: var(--gray-400); }
  .action-icon { cursor: pointer; transition: color .2s; font-size: 16px; border: 1px solid var(--gray-200); padding: 4px 8px; border-radius: var(--radius-sm); }
  .action-icon:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }

  .table-responsive { overflow-x: auto; }

  @media (max-width: 768px) {
    .customers-toolbar { flex-direction: column; align-items: stretch; gap: 16px; }
    .search-wrap { max-width: 100%; }
    .toolbar-actions { justify-content: flex-end; }
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Toolbar -->
    <div class="customers-toolbar">
      <div class="search-wrap">
        <input type="text" class="search-input" placeholder="Search Customers" />
        <button class="btn-search"><i class="bi bi-search"></i> Search</button>
      </div>
      <div class="toolbar-actions">
        <a href="{{ route('customers.create') }}" class="btn-new-customer text-decoration-none"><i class="bi bi-plus-lg"></i> New Customer</a>
        <button class="btn-icon-outline"><i class="bi bi-three-dots"></i></button>
      </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-actions" id="bulkActions" style="display: none;">
        <button class="btn-action-sm btn-delete" id="bulkDeleteBtn"><i class="bi bi-trash3"></i> Delete</button>
        <button class="btn-action-sm btn-clear" onclick="clearSelection()"><i class="bi bi-x-circle"></i> Clear Selection</button>
      </div>

      <div class="table-responsive">
        <table class="custom-table">
          <thead>
            <tr>
              <th style="width: 50px;">
                <input type="checkbox" class="custom-checkbox" id="selectAll" onchange="toggleAll(this)" />
              </th>
              <th>Customer</th>
              <th>Company</th>
              <th>Email</th>
              <th>Phone</th>
              <th class="text-end">Balance</th>
              <th style="width: 80px;" class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($customers as $customer)
            <tr>
              <td><input type="checkbox" class="custom-checkbox row-checkbox" data-id="{{ $customer->person_id }}" onchange="checkSelection()" /></td>
              <td>
                <div class="user-info">
                  <div class="user-avatar d-flex align-items-center justify-content-center text-muted" style="background: var(--gray-100);">
                    <i class="bi bi-person"></i>
                  </div>
                  <div>
                    <div class="user-name">{{ $customer->last_name }}, {{ $customer->first_name }}</div>
                    <div class="user-id">ID:{{ $customer->person_id }}</div>
                  </div>
                </div>
              </td>
              <td>{{ $customer->company_name ?: '—' }}</td>
              <td>{{ $customer->email ?: '—' }}</td>
              <td>{{ $customer->phone_number ?: '—' }}</td>
              <td class="text-end fw-bold {{ $customer->balance > 0 ? 'text-danger' : 'text-success' }}">
                  ${{ number_format((float) $customer->balance, 2) }}
              </td>
              <td class="text-end pe-4">
                <div class="row-actions justify-content-end">
                  <a href="{{ route('customers.edit', $customer->person_id) }}" class="text-decoration-none"><i class="bi bi-pencil-square action-icon"></i></a>
                  <button type="button" class="p-0 border-0 bg-transparent text-danger delete-btn" data-id="{{ $customer->person_id }}">
                      <i class="bi bi-trash3 action-icon text-danger"></i>
                  </button>
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-people fs-1 d-block mb-2"></i>
                  No customers found.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    
    @if($customers->hasPages())
        <div class="mt-3">
            {{ $customers->links() }}
        </div>
    @endif

    <form id="delete-form" method="post" style="display:none">
        @csrf
        @method('delete')
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltips.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Delete handling
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to archive this customer?')) {
                const id = this.dataset.id;
                const form = document.getElementById('delete-form');
                form.action = `/customers/${id}`;
                form.submit();
            }
        });
    });
});

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
