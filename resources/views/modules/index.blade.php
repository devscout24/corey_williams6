@extends('layouts.app')

@section('title', 'POS Modules')
@section('page-title', 'Module Map')

@push('styles')
<style>
    .wrap { max-width: 1000px; margin: 24px auto; padding: 0 16px; }
    .head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-top: 18px; }
    .card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08); }
    .card h2 { margin: 0 0 10px; font-size: 1.05rem; }
    .sub { list-style: none; margin: 0; padding: 0; }
    .sub li { padding: 6px 0; border-bottom: 1px dashed #dce3ee; }
    .sub li:last-child { border-bottom: 0; }
    .actions a, .actions button { border: 0; background: #0e7490; color: #fff; border-radius: 8px; padding: 8px 12px; text-decoration: none; cursor: pointer; }
    form { margin: 0; }
</style>
@endpush

@section('content')
<div class="wrap">
    <div class="head">
        <h1>Module Map</h1>
        <div class="actions" style="display:flex; gap:8px;">
            <a href="{{ route('labels.index') }}">Open Labels</a>
            <a href="{{ route('inventory.operations') }}">Inventory Ops</a>
            <a href="{{ route('sales.index') }}">Sales</a>
            <a href="{{ route('messages.index') }}">Messages</a>
            <form method="post" action="{{ route('employee.logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </div>

    <div class="grid">
        @foreach($modules as $module)
            <article class="card">
                <h2>{{ ucfirst($module->module_id) }}</h2>
                <ul class="sub">
                    @foreach($module->submodules as $sub)
                        <li>
                            @if($module->module_id === 'items' && $sub->submodule_key === 'labels')
                                <a href="{{ route('labels.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'items' && $sub->submodule_key === 'items')
                                <a href="{{ route('items.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'items' && $sub->submodule_key === 'item_kits')
                                <a href="{{ route('item-kits.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'items' && $sub->submodule_key === 'categories')
                                <a href="{{ route('categories.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'receivings')
                                <a href="{{ route('inventory.operations') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'sales')
                                <a href="{{ route('sales.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'messages')
                                <a href="{{ route('messages.index') }}">{{ $sub->label }}</a>
                            @elseif($module->module_id === 'contacts' && $sub->submodule_key === 'suppliers')
                                <a href="{{ route('suppliers.index') }}">{{ $sub->label }}</a>
                            @else
                                {{ $sub->label }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>
</div>
@endsection
