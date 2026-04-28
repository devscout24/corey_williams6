@extends('layouts.app')

@section('title', 'Receipt Settings')
@section('page-title', 'Receipt Settings')

@push('styles')
<style>
    .wrap { max-width: 760px; margin: 20px auto; padding: 0 16px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(15,23,42,.08); padding: 16px; }
    label { display: block; margin-bottom: 10px; }
    input, select { width: 100%; box-sizing: border-box; border: 1px solid #d2dce8; border-radius: 8px; padding: 8px; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    button, a { border: 0; background: #0f766e; color: #fff; border-radius: 8px; padding: 8px 12px; text-decoration: none; }
    .actions { display: flex; gap: 8px; }
    .status { background: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 8px; padding: 8px; margin-bottom: 10px; }
</style>
@endpush

@section('content')
<div class="wrap">
    <h1>Receipt Settings</h1>
    @if(session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif
    <form class="card" method="post" action="{{ route('sales.settings.save') }}">
        @csrf
        <label>Receipt Title
            <input type="text" name="title" maxlength="120" value="{{ old('title', $settings->title ?? 'Store Receipt') }}" required>
        </label>
        <label>Receipt Footer
            <input type="text" name="footer" maxlength="255" value="{{ old('footer', $settings->footer ?? 'Thank you') }}" required>
        </label>
        <label>Paper Size
            <select name="paper_size">
                <option value="58mm" {{ (old('paper_size', $settings->paper_size ?? '80mm') === '58mm') ? 'selected' : '' }}>58mm</option>
                <option value="80mm" {{ (old('paper_size', $settings->paper_size ?? '80mm') === '80mm') ? 'selected' : '' }}>80mm</option>
                <option value="a4" {{ (old('paper_size', $settings->paper_size ?? '80mm') === 'a4') ? 'selected' : '' }}>A4</option>
            </select>
        </label>
        <div class="row">
            <label><input type="checkbox" name="show_cashier" value="1" {{ old('show_cashier', $settings->show_cashier ?? 1) ? 'checked' : '' }}> Show cashier</label>
            <label><input type="checkbox" name="show_customer" value="1" {{ old('show_customer', $settings->show_customer ?? 1) ? 'checked' : '' }}> Show customer</label>
        </div>
        <div class="actions">
            <button type="submit">Save</button>
            <a href="{{ route('sales.index') }}">Back to Sales</a>
        </div>
    </form>
</div>
@endsection
