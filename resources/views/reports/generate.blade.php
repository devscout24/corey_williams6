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

                    <div class="mb-4">
                        <label class="form-label font-weight-bold">Export Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="export_excel" id="exportExcel">
                            <label class="form-check-label" for="exportExcel">
                                Export to Excel
                            </label>
                        </div>
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
