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
                                <td class="text-center"><span class="fw-medium">{{ $kit->cost_price ?? '—' }}</span></td>
                                <td class="text-center fw-bold text-dark"><span class="fw-medium">{{ $kit->unit_price ?? '—' }}</span></td>
                                <td class="text-center"><span class="fw-medium">{{ $kit->default_quantity ?? '—' }}</span></td>
                                <td class="text-center"><span class="fw-medium">{{ $kit->reorder_level ?? '—' }}</span></td>
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
        btn.addEventListener('click', async function() {
            let confirmResult = { isConfirmed: false };
            if (window.Swal) {
                confirmResult = await Swal.fire({
                    title: 'Archive item kit?',
                    text: 'This will archive the item kit.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Archive',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                });
            } else {
                confirmResult.isConfirmed = confirm('Are you sure you want to archive this item kit?');
            }

            if (confirmResult.isConfirmed) {
                const id = this.dataset.id;
                const form = document.getElementById('delete-form');
                form.action = `/item-kits/${id}`;
                form.submit();
            }
        });
    });


});
</script>
@endpush
