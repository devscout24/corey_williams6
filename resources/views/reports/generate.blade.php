@extends('layouts.app')

@section('title', 'Generate Report')
@section('page-title', $title)

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 radius-lg">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">Report Parameters</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('reports.store', $report) }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Date Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small text-muted">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    @if(!in_array($report, ['closeout', 'closeout_condensed']))
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Sale Type</label>
                        <select name="sale_type" class="form-select">
                            <option value="all">All</option>
                            <option value="sales">Sales</option>
                            <option value="returns">Returns</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Payment Type</label>
                        <select name="payment_type" class="form-select">
                            <option value="all">All</option>
                            @foreach($paymentTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Location</label>
                        <select name="location_id" class="form-select">
                            <option value="all">All Locations</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->location_id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($report == 'detailed_sales')
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Register</label>
                        <select name="register_id" class="form-select">
                            <option value="all">All Registers</option>
                            @foreach($registers as $reg)
                                <option value="{{ $reg->register_id }}">{{ $reg->name }}@if($reg->location_name) ({{ $reg->location_name }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($report != 'detailed_sales')
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Group By</label>
                        <select name="group_by" class="form-select">
                            <option value="day">Day</option>
                            <option value="week">Week</option>
                            <option value="month">Month</option>
                            <option value="year">Year</option>
                        </select>
                    </div>
                    @endif

                    @if(isset($employees))
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Employee</label>
                        <select name="employee_id" class="form-select">
                            <option value="all">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->person_id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(isset($customers))
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="all">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->person_id }}">{{ $customer->first_name }} {{ $customer->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Employee Type</label>
                        <select name="employee_type" class="form-select">
                            <option value="sold_by_employee_id">Sales Person</option>
                            <option value="employee_id">Logged In Employee</option>
                        </select>
                    </div>

                    @if($report == 'giftcard_audit' || $report == 'detailed_giftcards')
                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Gift Card Number</label>
                        <input type="text" name="giftcard_number" class="form-control" placeholder="Optional: Enter gift card number">
                    </div>
                    @endif

                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Export Options</label>
                        <select name="export_format" class="form-select mb-2">
                            <option value="">View Report</option>
                            <option value="xls">Download Excel (.xls)</option>
                            <option value="csv">Download CSV (.csv)</option>
                        </select>
                        @if($report == 'detailed_sales')
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="show_summary_only" id="showSummaryOnly">
                            <label class="form-check-label" for="showSummaryOnly">
                                Show Summary Only
                            </label>
                        </div>
                        @endif
                        @if($report != 'detailed_sales')
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="top_level_categories_only" id="topLevelCategoriesOnly">
                            <label class="form-check-label" for="topLevelCategoriesOnly">
                                Top Level Categories Only
                            </label>
                        </div>
                        @endif
                        <input type="hidden" name="export_excel" value="0">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <a href="{{ route('reports.index') }}" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
