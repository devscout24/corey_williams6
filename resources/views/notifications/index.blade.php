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
                <tr class="{{ $n->read_at === null ? 'unread' : '' }}">
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
                    <td style="white-space:nowrap;">
                        @if($n->read_at === null)
                        <form method="POST" action="{{ route('app.notifications.read', $n->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                        </form>
                        @endif
                        @if($canDelete)
                        <form method="POST" action="{{ route('app.notifications.delete', $n->id) }}" style="display:inline;" onsubmit="return confirm('Delete this notification?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete-notif" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
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
