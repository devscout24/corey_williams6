@extends('layouts.app')

@section('title', 'Register Reconciliation')
@section('page-title', 'Register Reconciliation')

@push('styles')
<style>
    .table-container-card {
        background: #fff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xs);
        border: 1px solid var(--gray-200);
        padding: 24px;
        overflow-x: auto;
    }
    [data-theme='dark'] .table-container-card {
        background: var(--gray-100) !important;
        border-color: var(--gray-200) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold m-0">Pending Reconciliation</h5>
        <span class="badge bg-warning text-dark fs-6">{{ count($logs) }} pending</span>
    </div>

    <div class="table-container-card">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Register</th>
                    <th>Opened By</th>
                    <th>Opened At</th>
                    <th>Closed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="fw-bold">{{ $log->register_log_id }}</td>
                        <td>{{ $log->register?->name ?? '—' }}</td>
                        <td>{{ $log->employeeOpen?->person?->first_name }} {{ $log->employeeOpen?->person?->last_name }}</td>
                        <td>{{ $log->shift_start }}</td>
                        <td>{{ $log->shift_end }}</td>
                        <td>
                            <a href="{{ route('registers.reconciliation.edit', $log->register_log_id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-search me-1"></i> Reconcile
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
                            All registers are reconciled.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
