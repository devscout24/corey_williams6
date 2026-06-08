@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
    .report-meta { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 12px; border: 1px solid var(--gray-200); }
    .report-meta h4 { margin: 0; color: var(--gray-900); font-size: 1.1rem; }
    .table-container { background: #fff; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .table thead th { background: var(--gray-50); border-top: none; color: var(--gray-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 16px; }
    .table tbody td { padding: 16px; vertical-align: middle; color: var(--gray-700); border-bottom: 1px solid var(--gray-100); }
    .table tbody tr:last-child td { border-bottom: none; }

    @media print {
        .page-header { display: none !important; }
        .table-container { border: none !important; }
    }
</style>
@endpush

@section('content')
<div class="report-meta">
    <div>
        <h4>{{ $title }}</h4>
        <p class="mb-0 text-muted small">Range: {{ $startDate }} to {{ $endDate }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light me-2">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('reports.generate', $report) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerate
        </a>
    </div>
</div>

<div class="table-container">
    <table class="table mb-0">
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    @foreach((array)$row as $value)
                        <td>
                            @if(is_numeric($value) && str_contains($value, '.'))
                                {{ number_format($value, 2) }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="text-center py-5 text-muted">
                        No data found for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
