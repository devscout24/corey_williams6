@extends('layouts.app')

@section('title', 'Receiving Register')
@section('page-title', 'Receiving Register')

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
        <!-- Left Side: Cart -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Item Selection</h6>
                    <div class="input-group input-group-sm w-50">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="item_search" class="form-control bg-light border-start-0" placeholder="Search item or scan barcode..." autocomplete="off">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="min-height: 400px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" width="50">#</th>
                                    <th>Item Name</th>
                                    <th width="120">Cost Price</th>
                                    <th width="100">Qty</th>
                                    <th width="100">Disc %</th>
                                    <th class="text-end" width="120">Total</th>
                                    <th class="text-end pe-4" width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cart['items'] as $index => $item)
                                    <tr>
                                        <td class="ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $item['name'] }}</div>
                                            <small class="text-muted">ID: {{ $item['item_id'] }}</small>
                                        </td>
                                        <td>
                                            <form action="{{ route('receivings.item.edit', $index) }}" method="POST">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ $item['cost_price'] }}" onchange="this.form.submit()">
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('receivings.item.edit', $index) }}" method="POST">
                                                @csrf
                                                <input type="number" step="0.001" name="quantity" class="form-control form-control-sm" value="{{ $item['quantity'] }}" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('receivings.item.edit', $index) }}" method="POST">
                                                @csrf
                                                <input type="number" step="0.1" name="discount" class="form-control form-control-sm" value="{{ $item['discount'] }}" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="text-end fw-bold">
                                            ${{ number_format($item['cost_price'] * $item['quantity'] * (1 - $item['discount'] / 100), 2) }}
                                        </td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('receivings.item.remove', $index) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm text-danger"><i class="bi bi-x-circle"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-cart fs-1 d-block mb-2"></i>
                                            Your receiving cart is empty.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Controls -->
        <div class="col-lg-4">
            <!-- Mode Selection -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="{{ route('receivings.mode.set') }}" method="POST" id="mode-form">
                        @csrf
                        <label class="form-label small fw-bold text-uppercase text-muted">Receiving Mode</label>
                        <select name="mode" class="form-select form-select-lg border-primary text-primary fw-bold" onchange="this.form.submit()">
                            <option value="receive" @selected($cart['mode'] == 'receive')>Receive (Stock In)</option>
                            <option value="return" @selected($cart['mode'] == 'return')>Return (Stock Out)</option>
                            <option value="transfer" @selected($cart['mode'] == 'transfer')>Transfer</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Supplier Selection -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form action="{{ route('receivings.supplier.set') }}" method="POST">
                        @csrf
                        <label class="form-label small fw-bold text-uppercase text-muted">Select Supplier</label>
                        <div class="input-group">
                            <select name="supplier_id" class="form-select select2-supplier" onchange="this.form.submit()">
                                <option value="">— Select Supplier —</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->person_id }}" @selected($cart['supplier_id'] == $supplier->person_id)>
                                        {{ $supplier->company_name }} ({{ $supplier->person->last_name }})
                                    </option>
                                @endforeach
                            </select>
                            @if($cart['supplier_id'])
                                <button type="submit" name="supplier_id" value="" class="btn btn-outline-danger"><i class="bi bi-x"></i></button>
                            @endif
                        </div>
                    </form>
                    
                    @if($cart['supplier_id'])
                        @php $selectedSupplier = $suppliers->firstWhere('person_id', $cart['supplier_id']); @endphp
                        <div class="mt-3 p-3 bg-light rounded small">
                            <div class="fw-bold">{{ $selectedSupplier->company_name }}</div>
                            <div class="text-muted">{{ $selectedSupplier->person->phone_number }}</div>
                            <div class="mt-2 text-primary fw-bold">Current Balance: ${{ number_format($selectedSupplier->balance, 2) }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Summary & Complete -->
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase small mb-4 opacity-75 fw-bold">Order Summary</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span class="fw-bold">${{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 fs-4 border-top pt-3 mt-3">
                        <span class="fw-bold">TOTAL</span>
                        <span class="fw-bold">${{ number_format($total, 2) }}</span>
                    </div>

                    <form action="{{ route('receivings.complete') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea name="comment" class="form-control form-control-sm bg-primary border-white border-opacity-25 text-white placeholder-white" placeholder="Add notes/comments..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-light btn-lg w-100 fw-bold text-primary mb-2" {{ empty($cart['items']) ? 'disabled' : '' }}>
                            <i class="bi bi-check2-circle me-2"></i> COMPLETE
                        </button>
                    </form>
                    
                    <form action="{{ route('receivings.cancel') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm w-100 text-white opacity-75 text-decoration-none mt-2" onclick="return confirm('Clear cart?')">
                            <i class="bi bi-trash"></i> Cancel Receiving
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal Placeholder or Search suggestions would go here -->
<div id="search_results" class="position-absolute shadow-sm bg-white rounded-bottom" style="display:none; z-index: 1000; width: 300px;"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('item_search');
    const resultsDiv = document.getElementById('search_results');
    
    let timer;
    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        const term = this.value;
        if (term.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        timer = setTimeout(() => {
            fetch(`{{ route('receivings.search') }}?term=${term}`)
                .then(res => res.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'p-2 border-bottom cursor-pointer hover-bg-light';
                            div.style.cursor = 'pointer';
                            div.innerHTML = `<strong>${item.name}</strong> <small class="text-muted">($${item.cost_price})</small>`;
                            div.onclick = () => addItem(item.item_id);
                            resultsDiv.appendChild(div);
                        });
                        
                        const rect = searchInput.getBoundingClientRect();
                        resultsDiv.style.top = (rect.bottom + window.scrollY) + 'px';
                        resultsDiv.style.left = rect.left + 'px';
                        resultsDiv.style.width = rect.width + 'px';
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.style.display = 'none';
                    }
                });
        }, 300);
    });

    function addItem(itemId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('receivings.item.add') }}";
        form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="item_id" value="${itemId}">`;
        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
});
</script>
<style>
    .hover-bg-light:hover { background-color: #f8f9fa; }
</style>
@endpush
