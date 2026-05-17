@extends('layouts.app')

@section('title', 'Purchases')
@section('page-title', 'Purchases')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/purchases-page.css') }}">
@endpush

@section('content')
    {{-- Structure from corey-dashboard/pages/purchases.html (#viewPurchasesList); data via /purchases/history-data --}}
    <div class="purchases-dashboard-root">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div id="viewPurchasesList">
            <div class="recv-card" >
                <div class="history-header">
                    <div class="history-title">
                        <i class="bi bi-clock-history"></i>
                        <span id="historyTitle" >Recent Purchases</span>
                    </div>
                    <div class="purchases-header-actions">
                        <a href="{{ $purchasesCreateUrl }}" class="btn-add-Purchases text-decoration-none">
                            <i class="bi bi-plus-lg"></i> Add Purchases
                        </a>
                        <a href="{{ $purchasesCreateReturnUrl }}" class="btn-add-Purchases btn-return-accent text-decoration-none">
                            <i class="bi bi-plus-lg"></i> Add Return
                        </a>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <div class="recv-mode-toggle" role="group" aria-label="List mode">
                        <button type="button" class="btn-mode-toggle active"  id="modeReceive"
                            onclick="window.purchasesSetListMode('Receive')">
                            <i class="bi bi-cart"></i> Purchases
                        </button>
                        <button type="button" class="btn-mode-toggle" id="modeReturn" 
                            onclick="window.purchasesSetListMode('Return')">
                            <i class="bi bi-arrow-return-left"></i> Return
                        </button>
                    </div>
                    <span id="listModeLabel" class="purchases-history-meta" >Showing: Purchases</span>
                </div>

                <div class="history-search-bar">
                    <select class="history-criteria-select" id="searchCriteria" aria-label="Search field">
                        <option value="id">ID / Code</option>
                        <option value="supplier">Supplier</option>
                        <option value="date">Date</option>
                        <option value="status">Status</option>
                    </select>
                    <input type="text" class="history-search-input" id="historySearchInput" placeholder="Search purchases…" />
                    <button type="button" class="btn-history-search" onclick="window.purchasesSearchHistory()">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <button type="button" class="btn-recv-action btn-blue" onclick="window.purchasesClearSearch()">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>

                <div class="table-responsive" >
                    <table class="history-table" style="color: black">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="selectAllPurchases" title="Select all">
                                </th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th style="text-align:center;">Items</th>
                                <th>Total</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody">
                            <tr>
                                <td colspan="10" class="text-center py-4 purchases-history-meta">Loading…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <span id="historyCount" class="purchases-history-meta"></span>
                    <div id="paginationControls"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.PURCHASES_PAGE_CONFIG = {
            historyUrl: @json($purchasesHistoryUrl),
            initialMode: @json($initialListMode),
        };
    </script>
    <script src="{{ asset('assets/js/purchases-page.js') }}"></script>
@endpush
