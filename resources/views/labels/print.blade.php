@extends('layouts.app')

@section('title', 'Print Labels')
@section('page-title', 'Print Labels')

@push('styles')
<style>
    .sidebar, .topbar, .page-header, .sidebar-overlay { display: none; }
    .main-content { margin-left: 0; }
    .toolbar { position: sticky; top: 0; background: #f8fafc; border-bottom: 1px solid #dbe4ee; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; }
    .toolbar button { border: 0; background: #0f766e; color: white; border-radius: 8px; padding: 8px 12px; cursor: pointer; }

    .sheet { padding: 12px; display: grid; gap: 8px; position: relative; }
    .barcode-grid { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }
    .sheet-grid { 
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(8, 1fr);
        gap: 5mm;
        width: 100%;
        max-width: 210mm;
        height: 270mm; /* approximate height to fit A4 minus margins */
        margin: 0 auto;
        padding: 0;
    }

    .label { 
        border: 1px solid #cfd8e3; 
        border-radius: 6px; 
        padding: 8px; 
        min-height: 92px; 
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    .sheet-grid .label {
        min-height: 0;
        height: 100%;
        width: 100%;
        box-sizing: border-box;
    }
    .label-bg-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: var(--label-bg-opacity, 0.25);
        z-index: 0;
        pointer-events: none;
    }
    .label .name { font-size: 13px; line-height: 1.25; margin-top: 6px; position: relative; z-index: 1; }
    .label .price { font-weight: 700; margin-top: 2px; position: relative; z-index: 1; }
    .company-name { font-weight: 600; font-size: 12px; margin-bottom: 4px; text-align: center; }
    .barcode, .barcode-value { position: relative; z-index: 1; }

    .barcode-value { font-size: 11px; margin-top: 4px; letter-spacing: 1px; text-align: center; }
    .logo-box { display: none; }

    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        .toolbar { display: none; }
        .sidebar, .topbar, .page-header, .sidebar-overlay { display: none; }
        body { margin: 0; padding: 0; background: #fff; }
    }
</style>
@endpush

@section('content')
<div class="toolbar">
    <strong>Mode: {{ strtoupper($mode) }} | Labels: {{ count($labels) }}</strong>
    <button onclick="window.print()">Print</button>
</div>

@php
    $resolvedSheetOpacity = $sheetOpacity ?? 0.25;
    if (! is_numeric($resolvedSheetOpacity)) {
        $resolvedSheetOpacity = 0.25;
    }
    $resolvedSheetOpacity = max(0, min(1, (float) $resolvedSheetOpacity));
@endphp
<div class="sheet {{ $mode === 'barcode' ? 'barcode-grid' : 'sheet-grid' }}">
    @foreach($labels as $idx => $label)
        @php
            $backgroundUrl = null;
            if ($mode === 'barcode' && $barcodeBackground) {
                $backgroundUrl = route('app_files.view', ['fileId' => $barcodeBackground]);
            }
            if ($mode === 'sheet' && $sheetBackground) {
                $backgroundUrl = route('app_files.view', ['fileId' => $sheetBackground]);
            }
            $labelStyle = $backgroundUrl
                ? "--label-bg: url('{$backgroundUrl}'); --label-bg-opacity: {$resolvedSheetOpacity};"
                : '';
        @endphp
        <article class="label" style="{{ $labelStyle }}">
            @if($mode === 'sheet' && $backgroundUrl)
                <img class="label-bg-img" src="{{ $backgroundUrl }}" alt="Label Background">
            @endif
            @if($mode === 'barcode')
                @if($showCompanyOnBarcode && $companyName !== '')
                    <div class="company-name">{{ $companyName }}</div>
                @endif
                @if(! $hideBarcodeOnLabels)
                    <svg class="barcode"
                         jsbarcode-format="{{ strtoupper($barcodeType) }}"
                         jsbarcode-value="{{ $label['barcode_value'] }}"
                         jsbarcode-width="{{ $barcodeWidth }}"
                         jsbarcode-height="{{ $barcodeHeight }}"
                         jsbarcode-fontsize="{{ $barcodeFontSize }}"
                         jsbarcode-displayValue="false"></svg>
                @endif
                <div class="barcode-value">{{ $label['barcode_value'] }}</div>
                <div class="name">{{ $label['name'] }}</div>
                <div class="price">${{ $label['price'] }}</div>
            @else
                <div class="name">{{ $label['name'] }}</div>
                <div class="price">${{ $label['price'] }}</div>
            @endif
        </article>
    @endforeach
</div>
@endsection

@push('scripts')
@if($mode === 'barcode')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode('.barcode').init();
    </script>
@endif
@endpush
