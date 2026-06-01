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
                        <div>
                            <span class="text-muted small me-3">{{ $locations->count() }} nodes</span>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                <i class="bi bi-plus-lg me-1"></i>Add Location
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>IP</th>
                                <th>Port</th>
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
                                    <td>{{ $location->port ?? 80 }}</td>
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
                                        @else
                                            <form action="{{ route('lan.locations.poke', $location) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-info" title="Send a poke to this node">
                                                    <i class="bi bi-send me-1"></i>Poke
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#editLocationModal-{{ $location->id }}" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('lan.locations.destroy', $location) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete {{ $location->name }}?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted mb-3">No LAN nodes discovered yet.</div>
                                        <form action="{{ route('lan.locations.resync-ip') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bi bi-plus-circle me-1"></i> Set Self Location
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                            <i class="bi bi-plus-lg me-1"></i> Add Remote Location
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @foreach($locations as $location)
                @if(!$location->is_self)
                <div class="modal fade" id="editLocationModal-{{ $location->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('lan.locations.update', $location) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Location</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <div class="mb-3">
                                        <label for="edit-name-{{ $location->id }}" class="form-label">Name</label>
                                        <input type="text" name="name" id="edit-name-{{ $location->id }}" class="form-control"
                                            value="{{ $location->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-ip-{{ $location->id }}" class="form-label">IP Address</label>
                                        <input type="text" name="ip" id="edit-ip-{{ $location->id }}" class="form-control"
                                            value="{{ $location->ip }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-port-{{ $location->id }}" class="form-label">Port</label>
                                        <input type="number" name="port" id="edit-port-{{ $location->id }}" class="form-control"
                                            value="{{ $location->port ?? 80 }}" min="1" max="65535">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
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

<div class="modal fade" id="addLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('lan.locations.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Remote Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label for="add-name" class="form-label">Name</label>
                        <input type="text" name="name" id="add-name" class="form-control" placeholder="e.g. Back-Office POS" required>
                    </div>
                    <div class="mb-3">
                        <label for="add-ip" class="form-label">IP Address</label>
                        <input type="text" name="ip" id="add-ip" class="form-control" placeholder="e.g. 192.168.1.100" required>
                    </div>
                    <div class="mb-3">
                        <label for="add-port" class="form-label">Port</label>
                        <input type="number" name="port" id="add-port" class="form-control" placeholder="e.g. 80" value="80" min="1" max="65535">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Location</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
