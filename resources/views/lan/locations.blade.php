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

    <div class="alert alert-info">
        This instance: <code>{{ $appUrl }}</code>
    </div>

    <div id="notificationsPanel" class="mb-3" style="display:none;">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0"><i class="bi bi-bell me-1"></i>Notifications <span id="unreadBadge" class="badge bg-danger ms-1" style="display:none;">0</span></h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="markAllRead()">Mark all read</button>
            </div>
            <div id="notificationsList" class="list-group list-group-flush"></div>
        </div>
    </div>

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
                                <th>Address</th>
                                <th>Last Seen</th>
                                <th>Poke Status</th>
                                <th>Self</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $location)
                                <tr>
                                    <td>{{ $location->name }}</td>
                                    <td><code>{{ $location->ip }}:{{ $location->port ?? 8000 }}</code></td>
                                    <td>{{ $location->last_seen_at ? \Carbon\Carbon::parse($location->last_seen_at)->format('m/d/Y H:i') : 'Never' }}</td>
                                    <td>
                                        @if($location->is_self)
                                            <span class="text-muted small">—</span>
                                        @elseif($location->last_poke_sent_at && $location->last_poke_ack_at && $location->last_poke_sent_at->eq($location->last_poke_ack_at))
                                            <span class="badge bg-success" title="Sent: {{ $location->last_poke_sent_at->format('m/d/Y H:i') }}, Response: {{ $location->last_poke_ack_at->format('m/d/Y H:i') }}">
                                                <i class="bi bi-check-circle me-1"></i>Complete
                                            </span>
                                        @elseif($location->last_poke_sent_at && $location->last_poke_ack_at)
                                            <span class="badge bg-success" title="Sent: {{ $location->last_poke_sent_at->format('m/d/Y H:i') }}, Response: {{ $location->last_poke_ack_at->format('m/d/Y H:i') }}">
                                                <i class="bi bi-check-circle me-1"></i>Complete
                                            </span>
                                        @elseif($location->last_poke_sent_at)
                                            <span class="badge bg-warning text-dark" title="Sent: {{ $location->last_poke_sent_at->format('m/d/Y H:i') }}, awaiting response">
                                                <i class="bi bi-hourglass-split me-1"></i>Waiting
                                            </span>
                                        @else
                                            <span class="text-muted small">Not poked</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($location->is_self)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($location->is_self)
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="resyncIpBtn" title="Re-resolve LAN IP for this node">
                                                <i class="bi bi-arrow-repeat me-1"></i>Resync IP
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSelfNameModal" title="Edit self label">
                                                <i class="bi bi-pencil me-1"></i>Edit Label
                                            </button>
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
                                        <button type="button" class="btn btn-primary me-2" id="resyncIpBtnEmpty">
                                            <i class="bi bi-plus-circle me-1"></i> Set Self Location
                                        </button>
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
                                            value="{{ $location->port ?? 8000 }}" min="1" max="65535">
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
                                <th>Error</th>
                                <th>Created</th>
                                <th>Actions</th>
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
                                    <td class="text-break" style="max-width: 200px;">
                                        @if($transfer->status === 'failed' && $transfer->error)
                                            <code class="small text-danger">{{ $transfer->error }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $transfer->created_at ? \Carbon\Carbon::parse($transfer->created_at)->format('m/d/Y H:i') : '' }}</td>
                                    <td>
                                        @if($transfer->status === 'failed')
                                            <form action="{{ route('lan.locations.retry', $transfer->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Retry sending this transfer">
                                                    <i class="bi bi-arrow-repeat me-1"></i>Retry
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No transfers queued yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editSelfNameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('lan.locations.self-name') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Self Location Label</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label for="self-name" class="form-label">Name</label>
                        <input type="text" name="name" id="self-name" class="form-control" value="{{ $locations->firstWhere('is_self', true)?->name ?? config('app.node_name') }}" required>
                        <div class="form-text">This label is used when other nodes discover this device.</div>
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

{{-- Resync IP confirmation modal --}}
<div class="modal fade" id="resyncIpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('lan.locations.resync-ip') }}" method="POST" id="resyncIpForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Confirm IP Resync</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start" id="resyncIpModalBody">
                    <p class="text-muted small mb-3">Review the detected values below. You can edit any field before saving.</p>
                    <div class="mb-3">
                        <label for="resync-name" class="form-label fw-semibold">Node Name</label>
                        <input type="text" name="name" id="resync-name" class="form-control" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-8">
                            <label for="resync-ip" class="form-label fw-semibold">IP Address</label>
                            <input type="text" name="ip" id="resync-ip" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label for="resync-port" class="form-label fw-semibold">Port</label>
                            <input type="number" name="port" id="resync-port" class="form-control" min="1" max="65535" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
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
                        <input type="number" name="port" id="add-port" class="form-control" placeholder="e.g. 8000" value="8000" min="1" max="65535">
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

@push('scripts')
<script>
(function () {
    const previewUrl = "{{ route('lan.locations.resync-ip.preview') }}";

    function triggerResync() {
        const btn = document.getElementById('resyncIpBtn') || document.getElementById('resyncIpBtnEmpty');
        const modal = new bootstrap.Modal(document.getElementById('resyncIpModal'));
        const body  = document.getElementById('resyncIpModalBody');

        // Show spinner state
        const originalBtnHtml = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Detecting…';

        const clickedBtn = this;

        fetch(previewUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data };
            });
        })
        .then(function ({ ok, data }) {
            clickedBtn.disabled = false;
            clickedBtn.innerHTML = originalBtnHtml;

            if (!ok) {
                alert('Error: ' + (data.error || 'Could not resolve IP'));
                return;
            }

            document.getElementById('resync-ip').value   = data.ip;
            document.getElementById('resync-port').value = data.port;
            document.getElementById('resync-name').value = data.name;

            modal.show();
        })
        .catch(function (err) {
            clickedBtn.disabled = false;
            clickedBtn.innerHTML = originalBtnHtml;
            alert('Network error: ' + err.message);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['resyncIpBtn', 'resyncIpBtnEmpty'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', triggerResync);
        });
    });
})();

(function () {
    const panel = document.getElementById('notificationsPanel');
    const list = document.getElementById('notificationsList');
    const badge = document.getElementById('unreadBadge');

    function fetchNotifications() {
        fetch('/app/notifications', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.notifications && data.notifications.length) {
                panel.style.display = '';
                list.innerHTML = data.notifications.map(function (n) {
                    var cls = n.is_unread ? 'list-group-item list-group-item-action' : 'list-group-item list-group-item-action text-muted';
                    var html = '<div class="' + cls + '" data-id="' + n.id + '">';
                    html += '<div class="d-flex w-100 justify-content-between"><small class="text-' + (n.is_unread ? 'primary' : 'secondary') + ' fw-semibold">' + escHtml(n.type) + '</small>';
                    if (n.is_unread) {
                        html += '<button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="markRead(' + n.id + ')" title="Dismiss">&times;</button>';
                    }
                    html += '</div>';
                    html += '<a href="' + (n.action_url || '#') + '" class="text-decoration-none stretched-link text-reset"><strong>' + escHtml(n.title) + '</strong></a>';
                    if (n.body) html += '<br><small>' + escHtml(n.body) + '</small>';
                    html += '<small class="d-block text-muted mt-1">' + timeAgo(n.created_at) + '</small>';
                    html += '</div>';
                    return html;
                }).join('');
            } else {
                panel.style.display = 'none';
            }

            if (data.unread_count > 0) {
                badge.textContent = data.unread_count;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(function () {});
    }

    function escHtml(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function timeAgo(ts) {
        if (!ts) return '';
        var diff = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    window.markRead = function (id) {
        fetch('/app/notifications/' + id + '/read', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function () { fetchNotifications(); })
            .catch(function () {});
    };

    window.markAllRead = function () {
        fetch('/app/notifications', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.notifications) {
                var promises = data.notifications.filter(function (n) { return n.is_unread; }).map(function (n) {
                    return fetch('/app/notifications/' + n.id + '/read', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                });
                Promise.all(promises).then(function () { fetchNotifications(); });
            }
        })
        .catch(function () {});
    };

    fetchNotifications();
    setInterval(fetchNotifications, 10000);
})();
</script>
@endpush
