@extends('layouts.app')

@section('title', 'Close Register')
@section('page-title', 'Close Register')

@section('content')
@php
    $currencySymbol = $baseCurrency['symbol'] ?? '$';
    $currencySymbolLocation = $baseCurrency['symbol_location'] ?? 'before';
    $currencyDecimals = (int) ($baseCurrency['decimals'] ?? 2);
    $currencyThousands = $baseCurrency['thousands_separator'] ?? ',';
    $currencyDecimalPoint = $baseCurrency['decimal_point'] ?? '.';

    $formatCurrencyWith = function (
        float $value,
        string $symbol,
        string $symbolLocation,
        int $decimals,
        string $thousandsSeparator,
        string $decimalPoint
    ): string {
        $formatted = number_format($value, $decimals, $decimalPoint, $thousandsSeparator);
        return $symbolLocation === 'after' ? $formatted . $symbol : $symbol . $formatted;
    };

    $formatCurrency = function (float $value) use (
        $currencySymbol,
        $currencySymbolLocation,
        $currencyDecimals,
        $currencyThousands,
        $currencyDecimalPoint,
        $formatCurrencyWith
    ): string {
        return $formatCurrencyWith(
            $value,
            $currencySymbol,
            $currencySymbolLocation,
            $currencyDecimals,
            $currencyThousands,
            $currencyDecimalPoint
        );
    };
@endphp

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 radius-lg overflow-hidden">
                <div class="card-header bg-danger text-white py-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="m-0 fw-bold"><i class="bi bi-door-closed me-2"></i> Close Register</h4>
                            <p class="m-0 opacity-75 mt-1">End shift for register: <strong>{{ $currentRegister->name }}</strong></p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-dark px-3 py-2">Shift #{{ $registerLog->register_log_id }}</span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 bg-white">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <label class="small text-uppercase text-muted fw-bold">Opened By</label>
                                <div class="fw-bold fs-5 mt-1">
                                    {{ $registerLog->employeeOpen?->person?->first_name }} 
                                    {{ $registerLog->employeeOpen?->person?->last_name }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <label class="small text-uppercase text-muted fw-bold">Shift Start</label>
                                <div class="fw-bold fs-5 mt-1">{{ $registerLog->shift_start }}</div>
                            </div>
                        </div>
                    </div>

                    @if($registerLog->notes)
                        <div class="alert alert-info border-0 mb-4 bg-light text-secondary">
                            <strong><i class="bi bi-info-circle me-1"></i> Opening Notes:</strong>
                            <p class="mb-0 mt-1 fst-italic">{{ $registerLog->notes }}</p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('sales.register.close.post') }}" method="POST">
                        @csrf
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-hover align-middle border rounded overflow-hidden">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Payment Type</th>
                                        <th class="text-end">Opening</th>
                                        <th class="text-end">Sales</th>
                                        <th class="text-end">Expected in Drawer</th>
                                        <th class="text-end" width="180">Actual in Drawer</th>
                                        <th class="text-end pe-4" width="120">Difference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalExpected = 0.0; @endphp
                                    @foreach($paymentsData as $type => $data)
                                        @php $totalExpected += $data['expected']; @endphp
                                        <tr class="payment-row" data-type="{{ $type }}" data-expected="{{ $data['expected'] }}">
                                            <td class="ps-4 fw-bold text-secondary">{{ $type }}</td>
                                            <td class="text-end text-muted">{{ $formatCurrency($data['open']) }}</td>
                                            <td class="text-end text-muted">{{ $formatCurrency($data['sales']) }}</td>
                                            <td class="text-end fw-bold text-dark">{{ $formatCurrency($data['expected']) }}</td>
                                            <td class="text-end">
                                                <div class="input-group input-group-sm justify-content-end">
                                                    <span class="input-group-text">{{ $currencySymbol }}</span>
                                                    <input type="number" step="0.01" name="closed_payments[{{ $type }}][actual]" 
                                                        class="form-control text-end actual-amount-input fw-semibold" 
                                                        value="{{ number_format($data['expected'], 2, '.', '') }}" 
                                                        style="max-width: 120px;" required>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4 fw-bold">
                                                <span class="diff-amount text-success">0.00</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold border-top">
                                    <tr>
                                        <td class="ps-4 text-uppercase">Total</td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-end fs-5 text-dark" id="grand-expected">{{ $formatCurrency($totalExpected) }}</td>
                                        <td class="text-end fs-5 text-dark" id="grand-actual">{{ $formatCurrency($totalExpected) }}</td>
                                        <td class="text-end pe-4 fs-5 text-success" id="grand-diff">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold text-secondary">Closing Notes <span class="text-muted font-normal">(Optional)</span></label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add any notes/explanations about shortages/surpluses or the shift in general..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 border-top pt-4">
                            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i> Keep Open / Return to Sales
                            </a>
                            <button type="submit" class="btn btn-danger px-4 py-2 fw-bold" onclick="return confirm('Are you sure you want to end this shift and close the register?')">
                                <i class="bi bi-x-circle me-2"></i> End Shift & Close Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('.payment-row');
    const grandExpectedVal = {{ $totalExpected }};
    const grandActualEl = document.getElementById('grand-actual');
    const grandDiffEl = document.getElementById('grand-diff');
    const baseCurrencySymbol = "{{ $currencySymbol }}";
    const symbolLocation = "{{ $currencySymbolLocation }}";
    const decimals = {{ $currencyDecimals }};
    const thousandsSep = "{{ $currencyThousands }}";
    const decimalPt = "{{ $currencyDecimalPoint }}";

    function formatNumber(value) {
        const fixed = Math.abs(value).toFixed(decimals);
        const parts = fixed.split('.');
        let whole = parts[0];
        const fraction = parts[1] || '';
        const rgx = /(\d+)(\d{3})/;
        while (rgx.test(whole)) {
            whole = whole.replace(rgx, `$1${thousandsSep}$2`);
        }
        const combined = fraction ? `${whole}${decimalPt}${fraction}` : whole;
        return value < 0 ? `-${combined}` : combined;
    }

    function formatCurrency(value) {
        const formatted = formatNumber(value);
        return symbolLocation === 'after' ? `${formatted}${baseCurrencySymbol}` : `${baseCurrencySymbol}${formatted}`;
    }

    function recalculateDiffs() {
        let totalActual = 0.0;
        
        rows.forEach(row => {
            const expected = parseFloat(row.dataset.expected);
            const actualInput = row.querySelector('.actual-amount-input');
            const actual = parseFloat(actualInput.value) || 0.0;
            totalActual += actual;

            const diff = actual - expected;
            const diffEl = row.querySelector('.diff-amount');
            diffEl.textContent = formatNumber(diff);
            
            if (Math.abs(diff) < 0.001) {
                diffEl.className = 'diff-amount text-success';
            } else if (diff > 0) {
                diffEl.className = 'diff-amount text-primary';
                diffEl.textContent = '+' + formatNumber(diff);
            } else {
                diffEl.className = 'diff-amount text-danger';
            }
        });

        const grandDiff = totalActual - grandExpectedVal;
        grandActualEl.textContent = formatCurrency(totalActual);
        grandDiffEl.textContent = formatNumber(grandDiff);

        if (Math.abs(grandDiff) < 0.001) {
            grandDiffEl.className = 'text-end pe-4 fs-5 text-success';
        } else if (grandDiff > 0) {
            grandDiffEl.className = 'text-end pe-4 fs-5 text-primary';
            grandDiffEl.textContent = '+' + formatNumber(grandDiff);
        } else {
            grandDiffEl.className = 'text-end pe-4 fs-5 text-danger';
        }
    }

    rows.forEach(row => {
        const input = row.querySelector('.actual-amount-input');
        input.addEventListener('input', recalculateDiffs);
    });

    recalculateDiffs(); // Initial calculation
});
</script>
@endpush
