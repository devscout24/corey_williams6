@extends('layouts.app')

@section('title', 'Item Kits')
@section('page-title', 'Item Kits')

@section('content')
<div class="container-fluid">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Manage Item Kits</h6>
            <div class="btn-group">
                <a class="btn btn-primary btn-sm px-3" href="{{ route('item-kits.create') }}">
                    <i class="bi bi-plus-lg me-1"></i> New Item Kit
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-white">
                        <tr>
                            <th class="ps-4 border-bottom">Item Kit</th>
                            <th class="border-bottom">Kit Number</th>
                            <th class="border-bottom">Category</th>
                            <th class="border-bottom">Supplier</th>
                            <th class="border-bottom">Cost</th>
                            <th class="text-end border-bottom">Price</th>
                            <th class="border-bottom">Quantity</th>
                            <th class="border-bottom">Threshold</th>
                            <th class="text-end pe-4 border-bottom">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kits as $kit)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $kit->name }}</div>
                                            <small class="text-muted">ID: #{{ $kit->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $kit->item_kit_number ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $kit->category->name ?? 'None' }}</span>
                                </td>
                                <td>{{ $kit->supplier->company_name ?? '—' }}</td>
                                <td>
                                    <input type="number" step="0.001" class="form-control form-control-sm inline-kit-input"
                                           data-kit-id="{{ $kit->id }}" data-field="cost_price"
                                           value="{{ $kit->cost_price }}" />
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    <input type="number" step="0.001" class="form-control form-control-sm inline-kit-input"
                                           data-kit-id="{{ $kit->id }}" data-field="unit_price"
                                           value="{{ $kit->unit_price }}" />
                                </td>
                                <td>
                                    <input type="number" step="0.001" class="form-control form-control-sm inline-kit-input"
                                           data-kit-id="{{ $kit->id }}" data-field="quantity"
                                           value="{{ $kit->default_quantity ?? 0 }}" />
                                </td>
                                <td>
                                    <input type="number" step="0.001" class="form-control form-control-sm inline-kit-input"
                                           data-kit-id="{{ $kit->id }}" data-field="reorder_level"
                                           value="{{ $kit->reorder_level ?? '' }}" />
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('item-kits.edit', $kit->id) }}" data-bs-toggle="tooltip" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="{{ $kit->id }}" data-bs-toggle="tooltip" title="Archive">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No item kits found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($kits->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $kits->links() }}
            </div>
        @endif
    </div>

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
            if (confirm('Are you sure you want to archive this item kit?')) {
                const id = this.dataset.id;
                const form = document.getElementById('delete-form');
                form.action = `/item-kits/${id}`;
                form.submit();
            }
        });
    });

    const quickUpdateBase = "{{ url('/item-kits') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function parseValue(value) {
        if (value === '' || value === null || typeof value === 'undefined') {
            return null;
        }
        const parsed = Number(value);
        return Number.isNaN(parsed) ? null : parsed;
    }

    async function saveInlineKit(input) {
        const kitId = input.dataset.kitId;
        const field = input.dataset.field;
        if (!kitId || !field) {
            return;
        }

        const newValue = input.value;
        const oldValue = input.defaultValue;

        if (newValue == oldValue) {
            return;
        }

        if (!confirm(`Are you sure you want to update this ${field.replace('_', ' ')}?`)) {
            input.value = oldValue;
            return;
        }

        const payload = { [field]: parseValue(newValue) };

        try {
            const response = await fetch(`${quickUpdateBase}/${kitId}/quick-update`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('Save failed');
            }

            input.defaultValue = newValue;
            input.classList.add('is-valid');
            setTimeout(() => input.classList.remove('is-valid'), 1000);
        } catch (error) {
            input.classList.add('is-invalid');
            input.value = oldValue;
            setTimeout(() => input.classList.remove('is-invalid'), 1200);
        }
    }

    document.querySelectorAll('.inline-kit-input').forEach((input) => {
        input.addEventListener('change', () => {
            saveInlineKit(input);
        });
    });
});
</script>
@endpush
