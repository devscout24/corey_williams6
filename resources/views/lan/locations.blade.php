@extends('layouts.app')

@section('title', 'LAN Locations')
@section('page-title', 'LAN Locations')

@section('content')
<div class="container-fluid">
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

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Known LAN Nodes</h5>
                        <span class="text-muted small">{{ $locations->count() }} nodes</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>IP</th>
                                <th>Last Seen</th>
                                <th>Self</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $location)
                                <tr>
                                    <td>{{ $location->name }}</td>
                                    <td>{{ $location->ip }}</td>
                                    <td>{{ $location->last_seen_at ? \Carbon\Carbon::parse($location->last_seen_at)->format('m/d/Y H:i') : 'Never' }}</td>
                                    <td>
                                        @if($location->is_self)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($location->is_self)
                                            <form action="{{ route('lan.locations.resync-ip') }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Re-resolve LAN IP for this node">
                                                    <i class="bi bi-arrow-repeat me-1"></i>Resync IP
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-muted mb-3">No LAN nodes discovered yet.</div>
                                        <form action="{{ route('lan.locations.resync-ip') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-1"></i> Set Self Location
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Transfer Queue</h5>
                        <span class="text-muted small">Last {{ $transfers->count() }} records</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Destination</th>
                                <th>Item Type</th>
                                <th>Item ID</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                                <tr>
                                    <td>{{ $transfer->id }}</td>
                                    <td>
                                        {{ $transfer->destination?->name ?? 'Unknown' }}
                                        @if($transfer->destination?->ip)
                                            <span class="text-muted">({{ $transfer->destination->ip }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $transfer->item_type }}</td>
                                    <td>{{ $transfer->item_id }}</td>
                                    <td>
                                        @if($transfer->status === 'delivered')
                                            <span class="badge bg-success">Delivered</span>
                                        @elseif($transfer->status === 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $transfer->created_at ? \Carbon\Carbon::parse($transfer->created_at)->format('m/d/Y H:i') : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No transfers queued yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
