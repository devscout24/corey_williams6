@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@push('styles')
<style>
    .notif-table {
        width: 100%;
        border-collapse: collapse;
    }
    .notif-table th {
        background: var(--gray-50);
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-500);
        border-bottom: 1.5px solid var(--gray-200);
        text-align: left;
        white-space: nowrap;
    }
    .notif-table td {
        padding: 14px 16px;
        font-size: 13.5px;
        color: var(--gray-800);
        border-bottom: 1px solid var(--gray-100);
        vertical-align: middle;
    }
    .notif-table tr:hover {
        background: var(--gray-50);
    }
    .notif-table tr.unread {
        font-weight: 600;
    }
    [data-theme='dark'] .notif-table th {
        background: var(--gray-200) !important;
        border-bottom-color: var(--gray-300) !important;
        color: var(--gray-800) !important;
    }
    [data-theme='dark'] .notif-table td {
        border-bottom-color: var(--gray-200) !important;
        color: var(--gray-900) !important;
    }
    [data-theme='dark'] .notif-table tr:hover {
        background: var(--gray-50) !important;
    }
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
    .btn-delete-notif {
        background: none;
        border: none;
        color: #dc2626;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 6px;
        transition: var(--transition);
    }
    .btn-delete-notif:hover {
        background: #fef2f2;
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="table-container-card">
        <table class="notif-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Title</th>
                    <th>Body</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $n)
                <tr class="{{ $n->read_at === null ? 'unread' : '' }}" data-id="{{ $n->id }}">
                    <td><span class="badge bg-secondary">{{ $n->type }}</span></td>
                    <td>
                        @if($n->reference_type && $n->reference_id)
                            <span class="small text-muted">{{ $n->reference_type }} #{{ $n->reference_id }}</span>
                        @else
                            <span class="small text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($n->action_url)
                            <a href="{{ $n->action_url }}">{{ $n->title }}</a>
                        @else
                            {{ $n->title }}
                        @endif
                    </td>
                    <td class="text-muted">{{ $n->body ?? '-' }}</td>
                    <td class="text-muted" style="white-space:nowrap;">{{ $n->created_at?->diffForHumans() ?? '-' }}</td>
                    <td class="notif-actions" style="white-space:nowrap;">
                        @if($n->read_at === null)
                        <button type="button" class="btn btn-sm btn-outline-secondary mark-read-btn" data-id="{{ $n->id }}">Mark read</button>
                        @endif
                        @if($canDelete)
                        <button type="button" class="btn-delete-notif delete-notif-btn" data-id="{{ $n->id }}" title="Delete"><i class="bi bi-trash"></i></button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No notifications.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $notifications->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const row = this.closest('tr');
            try {
                const res = await fetch('/app/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                row.classList.remove('unread');
                this.remove();
            } catch (e) {
                // ignore
            }
        });
    });

    document.querySelectorAll('.delete-notif-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            if (!confirm('Delete this notification?')) return;
            const id = this.dataset.id;
            const row = this.closest('tr');
            try {
                const res = await fetch('/app/notifications/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) return;
                row.remove();
            } catch (e) {
                // ignore
            }
        });
    });
});
</script>
@endpush
