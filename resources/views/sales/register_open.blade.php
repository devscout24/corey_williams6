@extends('layouts.app')

@section('title', 'Open Register')
@section('page-title', 'Open Register')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 radius-lg overflow-hidden">
                <div class="card-header bg-primary text-white py-4 text-center">
                    <i class="bi bi-door-open fs-1 mb-2 d-block"></i>
                    <h4 class="m-0 fw-bold">Open Register</h4>
                    <p class="m-0 opacity-75 mt-1">Specify opening cash amount to start selling</p>
                </div>
                
                <div class="card-body p-4 bg-white">
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

                    <!-- Change Register Form -->
                    <div class="mb-4 p-3 bg-light rounded border">
                        <form action="{{ route('sales.register.change') }}" method="POST" id="changeRegisterForm">
                            @csrf
                            <label for="register_id" class="form-label small fw-bold text-uppercase text-muted mb-2">Active Register</label>
                            <select name="register_id" id="register_id" class="form-select border-0 bg-transparent fw-bold" onchange="document.getElementById('changeRegisterForm').submit()">
                                @foreach($registers as $reg)
                                    <option value="{{ $reg->register_id }}" @selected($reg->register_id == $currentRegister->register_id)>
                                        {{ $reg->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <!-- Open Register Form -->
                    <form action="{{ route('sales.register.open.post') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="opening_amount" class="form-label fw-semibold text-secondary">Opening Cash Amount</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0 text-muted">$</span>
                                <input type="number" step="0.01" name="opening_amount" id="opening_amount" 
                                    class="form-control border-start-0 ps-1" 
                                    value="{{ number_format($lastCloseAmount, 2, '.', '') }}" 
                                    placeholder="0.00" required autofocus>
                            </div>
                            @if($lastCloseAmount > 0)
                                <div class="form-text text-muted mt-2">
                                    <i class="bi bi-info-circle me-1"></i> Suggested from last close amount: <strong>${{ number_format($lastCloseAmount, 2) }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-semibold text-secondary">Opening Notes <span class="text-muted font-normal">(Optional)</span></label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add any notes about this shift..."></textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> Start Shift / Open Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
