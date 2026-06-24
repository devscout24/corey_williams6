@extends('layouts.app')

@section('title', 'Receipt Settings')
@section('page-title', 'Receipt Settings')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 radius-lg overflow-hidden">
                <div class="card-header bg-primary text-white py-4 text-center">
                    <i class="bi bi-printer fs-1 mb-2 d-block"></i>
                    <h4 class="m-0 fw-bold">Receipt Settings</h4>
                    <p class="m-0 opacity-75 mt-1">Configure receipt printing preferences</p>
                </div>

                <div class="card-body p-4 bg-white">
                    @if(session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

                    <form method="post" action="{{ route('sales.settings.save') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold text-secondary">Receipt Title</label>
                            <input type="text" name="title" id="title" class="form-control" maxlength="120"
                                value="{{ old('title', $settings->title ?? 'Store Receipt') }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="footer" class="form-label fw-semibold text-secondary">Receipt Footer</label>
                            <input type="text" name="footer" id="footer" class="form-control" maxlength="255"
                                value="{{ old('footer', $settings->footer ?? 'Thank you') }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="paper_size" class="form-label fw-semibold text-secondary">Paper Size</label>
                            <select name="paper_size" id="paper_size" class="form-select">
                                <option value="58mm" @selected(old('paper_size', $settings->paper_size ?? '80mm') === '58mm')>58mm</option>
                                <option value="80mm" @selected(old('paper_size', $settings->paper_size ?? '80mm') === '80mm')>80mm</option>
                                <option value="a4" @selected(old('paper_size', $settings->paper_size ?? '80mm') === 'a4')>A4</option>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="show_cashier" id="show_cashier" class="form-check-input" value="1"
                                        @checked(old('show_cashier', $settings->show_cashier ?? 1))>
                                    <label class="form-check-label fw-semibold text-secondary" for="show_cashier">Show cashier</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="show_customer" id="show_customer" class="form-check-input" value="1"
                                        @checked(old('show_customer', $settings->show_customer ?? 1))>
                                    <label class="form-check-label fw-semibold text-secondary" for="show_customer">Show customer</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer bg-white border-top text-center py-3">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-arrow-left me-2"></i> Back to Sales
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
