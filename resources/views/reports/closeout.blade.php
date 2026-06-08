@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
    .report-meta { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 12px; border: 1px solid var(--gray-200); }
    .report-meta h4 { margin: 0; color: var(--gray-900); font-size: 1.1rem; }
    .closeout-report { background: #fff; border-radius: 12px; box-shadow: var(--shadow-sm); padding: 24px; }
    .closeout-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .closeout-table tr.section-header td { padding: 14px 12px 6px; font-size: 0.95rem; color: var(--primary); border-bottom: 2px solid var(--primary); }
    .closeout-table tr.data-row td { padding: 6px 12px; border-bottom: 1px solid var(--gray-100); color: var(--gray-700); }
    .closeout-table tr.data-row:last-child td { border-bottom: none; }
    .closeout-table tr.data-row:nth-child(even) { background: var(--gray-50); }
    .closeout-table .description { width: 65%; }
    .closeout-table .value { width: 35%; text-align: right; font-weight: 600; color: var(--gray-900); }
    .closeout-table tr.data-row.subtotal td { border-top: 2px solid var(--gray-300); font-weight: 700; color: var(--gray-900); }
    .closeout-table tr.data-row.subtotal .value { font-weight: 800; }

    @media print {
        .page-header { display: none !important; }
        .report-meta { background: none !important; border: none !important; padding: 0 0 16px !important; }
        .report-meta .actions { display: none !important; }
        .closeout-report { box-shadow: none !important; border: none !important; padding: 0 !important; }
        .closeout-table tr.data-row:nth-child(even) { background: #f9f9f9 !important; }
        [data-theme='dark'] .closeout-report { background: #fff !important; color: #000 !important; }
        [data-theme='dark'] .closeout-table .value { color: #000 !important; }
        [data-theme='dark'] .closeout-table tr.data-row td { color: #000 !important; }
    }
</style>
@endpush

@section('content')
<div class="report-meta">
    <div>
        <h4>{{ $title }}</h4>
        <p class="mb-0 text-muted small">Period: {{ $startDate }} to {{ $endDate }}</p>
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

<div class="closeout-report">
    @if(empty($sections))
        <p class="text-muted text-center py-5 mb-0">No data found for the selected period.</p>
    @else
        <table class="closeout-table">
            @foreach($sections as $section)
                @if(!empty($section['rows']))
                    <tr class="section-header">
                        <td colspan="2"><strong>{{ $section['title'] }}</strong></td>
                    </tr>
                    @foreach($section['rows'] as $row)
                        @php $isSubtotal = ($row['subtotal'] ?? false); @endphp
                        <tr class="data-row{{ $isSubtotal ? ' subtotal' : '' }}">
                            <td class="description">{{ $row['label'] }}</td>
                            <td class="value">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                    @if(!$loop->last)
                    <tr style="height: 12px;"><td colspan="2"></td></tr>
                    @endif
                @endif
            @endforeach
        </table>
    @endif
</div>
@endsection
