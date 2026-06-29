@extends('layouts.app')

@section('title', 'Import Items')
@section('page-title', 'Inventory / Items / Import')

@push('styles')
<style>
  .page-content-inner {
    max-width: 700px;
    margin: 0 auto;
  }
  .category-tree-card {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs);
    padding: 24px 30px;
  }
  .form-row-custom {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }
  .form-label-custom {
    width: 150px;
    text-align: right;
    padding-right: 25px;
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
  }
  .form-input-custom {
    flex: 1;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
    color: #1e293b;
    outline: none;
  }
  .form-input-custom:focus {
    border-color: var(--primary);
  }
  .input-group-custom {
    display: flex;
    flex: 1;
  }
  .input-group-custom .form-input-custom {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
  }
  .btn-choose-file {
    background: #3b82f6;
    color: #fff;
    border: none;
    padding: 8px 20px;
    font-size: 13.5px;
    font-weight: 600;
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
    transition: background 0.2s;
  }
  .btn-choose-file:hover {
    background: #2563eb;
  }
  .btn-save-category {
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 8px 25px;
    font-size: 14px;
    font-weight: 600;
    float: right;
  }
  .btn-save-category:hover {
    background: #2563eb;
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="page-content-inner">
        <div class="category-tree-card">
            <h5 style="font-size: 16px; font-weight: 600; color: #475569; margin-bottom: 6px;">Import Items from CSV / XLS</h5>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 24px;">
                Upload a CSV or XLS file with columns: <code>name</code>, <code>item_number</code> (optional), <code>product_id</code> (optional), <code>category</code> (optional), <code>supplier</code> (optional), <code>cost_price</code>, <code>unit_price</code>, <code>quantity</code>, <code>reorder_level</code>, <code>description</code>, <code>size</code>, <code>weight</code>, <code>is_service</code>, <code>item_inactive</code>, <code>is_barcoded</code>.
                New items are created immediately. Existing items (matched by product_id then item_number) are queued for review before updating.
            </p>

            @if(session('status'))
                <div class="alert alert-success" style="border-radius: 6px; font-size: 13.5px;">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" style="border-radius: 6px; font-size: 13.5px;">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('items.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-row-custom">
                    <label class="form-label-custom">File:</label>
                    <div class="input-group-custom">
                        <input type="text" class="form-input-custom" id="file-name-display" placeholder="No file chosen" readonly>
                        <button type="button" class="btn-choose-file" onclick="document.getElementById('import_file').click();">Choose File</button>
                    </div>
                    <input type="file" id="import_file" name="import_file" accept=".csv,.txt,.xls,.html" style="display:none;" onchange="document.getElementById('file-name-display').value = this.files[0]?.name || ''">
                </div>

                <div class="form-row-custom" style="margin-bottom: 25px;">
                    <label class="form-label-custom"></label>
                    <div style="flex: 1; font-size: 12.5px; color: #64748b;">
                        Accepted formats: <strong>.csv</strong>, <strong>.txt</strong>, <strong>.xls</strong>, <strong>.html</strong>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden">
                    <button type="submit" class="btn-save-category">Import</button>
                    <a href="{{ route('items.index') }}" style="float: left; font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; padding-top: 8px;">Back to Items</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
