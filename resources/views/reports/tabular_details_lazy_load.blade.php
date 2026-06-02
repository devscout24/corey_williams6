@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
<style>
    .report-meta { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 12px; border: 1px solid var(--gray-200); }
    .report-meta h4 { margin: 0; color: var(--gray-900); font-size: 1.1rem; }
    .table-container { background: #fff; border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .table thead th { background: var(--gray-50); border-top: none; color: var(--gray-500); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; }
    .table tbody td { padding: 12px 16px; vertical-align: middle; color: var(--gray-700); border-bottom: 1px solid var(--gray-100); }
    .table tbody tr:last-child td { border-bottom: none; }
    .summary-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .summary-card { background: #fff; border-radius: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); padding: 20px; text-align: center; }
    .summary-card .value { font-size: 1.5rem; font-weight: 700; color: var(--gray-900); }
    .summary-card .label { font-size: 0.8rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    .expand-btn { cursor: pointer; user-select: none; font-weight: bold; font-size: 1.1rem; }
    .innertable { padding: 0 !important; }
    .innertable table { margin: 0; }
    .innertable table th { background: #f8f9fa; font-size: 0.75rem; padding: 8px 12px; }
    .innertable table td { padding: 8px 12px; font-size: 0.85rem; }
    .pagination-info { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: var(--gray-50); border-top: 1px solid var(--gray-200); }
    .pagination-info nav { margin: 0; }
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
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Download
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                    <a class="dropdown-item" href="{{ route('reports.generate', $report) }}?{{ http_build_query(array_merge(request()->query(), ['export_excel' => '1', 'format' => 'xls', 'show_summary_only' => '0'])) }}">
                        <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xls)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('reports.generate', $report) }}?{{ http_build_query(array_merge(request()->query(), ['export_excel' => '1', 'format' => 'csv', 'show_summary_only' => '0'])) }}">
                        <i class="bi bi-filetype-csv me-2 text-primary"></i> CSV (.csv)
                    </a>
                </li>
            </ul>
        </div>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ route('reports.generate', $report) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i> Regenerate
        </a>
    </div>
</div>

@if(isset($summaryTotalsArray))
<div class="summary-cards">
    @foreach($summaryTotalsArray as $name => $value)
    <div class="summary-card">
        <div class="value">{{ number_format($value, 2) }}</div>
        <div class="label">{{ ucfirst($name) }}</div>
    </div>
    @endforeach
</div>
@endif

<div class="table-container">
    <table class="table mb-0" id="detailed-sales-table">
        <thead>
            <tr>
                @if(!$showSummaryOnly)
                <th style="width: 40px;">
                    <a href="#" class="expand-all expand-btn" style="text-decoration:none;color:inherit;">+</a>
                </th>
                @endif
                @foreach($headers as $header)
                    <th class="text-{{ $header['align'] === 'right' ? 'end' : 'start' }}">{{ $header['data'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                @if(!$showSummaryOnly)
                <td>
                    <a href="#" class="expand expand-btn" data-id="{{ $row->sale_id }}" style="text-decoration:none;color:inherit;">+</a>
                </td>
                @endif
                <td>{{ $row->sale_id }}</td>
                @if($locationCount > 1)
                <td>{{ $row->location_name ?? '' }}</td>
                @endif
                <td>{{ $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : '' }}</td>
                <td>{{ $row->register_name ?? '' }}</td>
                <td>{{ $row->items_purchased ?? 0 }}</td>
                <td>
                    @if($row->sold_by_employee && $row->sold_by_employee !== $row->employee_name)
                        {{ $row->employee_name }}/{{ $row->sold_by_employee }}
                    @else
                        {{ $row->employee_name ?? '' }}
                    @endif
                </td>
                <td>{{ $row->customer_name ?? $row->denormalized_customer ?? '' }}</td>
                <td>{{ $row->customer_email ?? '' }}</td>
                <td>{{ $row->customer_phone ?? '' }}</td>
                <td class="text-end">{{ number_format($row->subtotal ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($row->total ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($row->tip ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($row->tax ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($row->profit ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format(($row->subtotal ?? 0) - ($row->profit ?? 0), 2) }}</td>
                <td>{{ $row->payment_type ?? '' }}</td>
                <td>{{ $row->comment ?? '' }}</td>
            </tr>
            @if(!$showSummaryOnly)
            <tr class="sale-details" id="details-{{ $row->sale_id }}" style="display:none;">
                <td colspan="100" class="innertable"></td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="100" class="text-center py-5 text-muted">
                    No data found for the selected period.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if(isset($totalRows) && $totalRows > $perPage)
    <div class="pagination-info">
        <span class="text-muted small">
            Showing {{ (($page - 1) * $perPage) + 1 }} - {{ min($page * $perPage, $totalRows) }} of {{ $totalRows }}
        </span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                @for($i = 1; $i <= ceil($totalRows / $perPage); $i++)
                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                    <a class="page-link" href="{{ route('reports.generate', $report) }}?{{ http_build_query(array_merge(request()->query(), ['page' => $i, 'show_summary_only' => $showSummaryOnly ? '1' : '0'])) }}">{{ $i }}</a>
                </li>
                @endfor
            </ul>
        </nav>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.expand-all').click(function(e) {
        e.preventDefault();
        var isExpand = $(this).text() === '+';
        $(this).text(isExpand ? '-' : '+');
        $('.expand').text(isExpand ? '-' : '+');

        if (isExpand) {
            var ids = [];
            $('.expand[data-id]').each(function() {
                ids.push($(this).data('id'));
            });
            if (ids.length) {
                showReportDetails(ids);
            }
        }
        $('.sale-details').toggle(isExpand);
    });

    $(document).on('click', '.expand[data-id]', function(e) {
        e.preventDefault();
        var $link = $(this);
        var id = $link.data('id');
        var $tr = $link.closest('tr');
        var $detailsRow = $('#details-' + id);

        if ($link.text() === '+') {
            $link.text('-');
            showReportDetails([id]);
        } else {
            $link.text('+');
            $detailsRow.hide();
        }
    });

    function showReportDetails(ids) {
        var url = '{{ route("reports.details", $report) }}';
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                ids: ids,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            cache: false,
            success: function(response) {
                var headers = response.headers || [];
                var detailsData = response.details_data || {};

                $.each(ids, function(i, saleId) {
                    var $detailsRow = $('#details-' + saleId);
                    var items = detailsData[saleId] || [];

                    if (!items.length) {
                        $detailsRow.find('td.innertable').html(
                            '<div class="p-3 text-muted text-center">No line items found.</div>'
                        );
                        $detailsRow.show();
                        return;
                    }

                    var tableHtml = '<table class="table table-bordered table-sm mb-0">';
                    tableHtml += '<thead><tr>';
                    $.each(headers, function(k, header) {
                        tableHtml += '<th class="text-' + (header.align === 'right' ? 'end' : 'start') + '">' + header.data + '</th>';
                    });
                    tableHtml += '</tr></thead><tbody>';

                    $.each(items, function(idx, item) {
                        tableHtml += '<tr>';
                        tableHtml += '<td>' + (item.item_id || '') + '</td>';
                        tableHtml += '<td>' + (item.item_number || '') + '</td>';
                        tableHtml += '<td>' + (item.item_product_id || '') + '</td>';
                        tableHtml += '<td>' + (item.item_name || '') + '</td>';
                        tableHtml += '<td>' + (item.category || '') + '</td>';
                        tableHtml += '<td>' + (item.size || '') + '</td>';
                        tableHtml += '<td>' + (item.supplier_name || '') + '</td>';
                        tableHtml += '<td>' + (item.manufacturer || '') + '</td>';
                        tableHtml += '<td>' + (item.serialnumber || '') + '</td>';
                        tableHtml += '<td>' + (item.description || '') + '</td>';
                        tableHtml += '<td class="text-end">' + (item.unit_price != null ? Number(item.unit_price).toFixed(2) : '') + '</td>';
                        tableHtml += '<td class="text-end">' + (item.quantity_purchased != null ? Number(item.quantity_purchased).toFixed(2) : '') + '</td>';
                        tableHtml += '<td class="text-end">' + (item.subtotal != null ? Number(item.subtotal).toFixed(2) : '') + '</td>';
                        tableHtml += '<td class="text-end">' + (item.total != null ? Number(item.total).toFixed(2) : '') + '</td>';
                        tableHtml += '<td class="text-end">' + (item.tax != null ? Number(item.tax).toFixed(2) : '') + '</td>';
                        tableHtml += '<td class="text-end">' + (item.profit != null ? Number(item.profit).toFixed(2) : '') + '</td>';
                        tableHtml += '<td class="text-end">' + (item.discount_percent != null ? Number(item.discount_percent) + '%' : '') + '</td>';
                        tableHtml += '</tr>';
                    });
                    tableHtml += '</tbody></table>';

                    $detailsRow.find('td.innertable').html(tableHtml);
                    $detailsRow.show();
                });
            },
            error: function(xhr) {
                alert('Error loading details: ' + (xhr.responseJSON?.message || xhr.statusText));
            }
        });
    }
});
</script>
@endpush