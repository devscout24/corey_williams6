@extends('layouts.app')

@section('title', 'LAN Locations')
@section('page-title', 'LAN Locations')

@section('content')
<div class="container-fluid">
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $location)
                                <tr>
                                    <td>{{ $location->name }}</td>
                                    <td>{{ $location->ip }}</td>
                                    <td>{{ $location->last_seen_at ? $location->last_seen_at->format('m/d/Y H:i') : 'Never' }}</td>
                                    <td>
                                        @if($location->is_self)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No LAN nodes discovered yet.</td>
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
                                    <td>{{ $transfer->created_at?->format('m/d/Y H:i') }}</td>
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
