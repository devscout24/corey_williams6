@extends('layouts.app')

@section('title', 'Price Rules')
@section('page-title', 'Inventory / Price Rules')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="m-0">Price Rules</h4>
        </div>
        <div>
            <a href="{{ route('price-rules.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Rule</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="py-3">Type</th>
                        <th class="py-3">Start Date</th>
                        <th class="py-3">End Date</th>
                        <th class="py-3">Status</th>
                        <th class="px-4 py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($priceRules as $rule)
                    <tr>
                        <td class="px-4">{{ $rule->name }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($rule->type)) }}</td>
                        <td>{{ $rule->start_date ? $rule->start_date->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ $rule->end_date ? $rule->end_date->format('Y-m-d') : 'N/A' }}</td>
                        <td>
                            @if($rule->active)
                                <span class="badge bg-success-subtle text-success px-2 py-1">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger px-2 py-1">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 text-end">
                            <div class="dropdown shadow-none">
                                <button class="btn btn-sm btn-icon border-0 p-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical text-secondary"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                    <li><a class="dropdown-item py-2" href="{{ route('price-rules.edit', $rule->id) }}"><i class="bi bi-pencil me-2"></i> Edit</a></li>
                                    <li>
                                        <form action="{{ route('price-rules.destroy', $rule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this rule?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item py-2 text-danger"><i class="bi bi-trash me-2"></i> Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-tag-fill display-4 mb-3 d-block"></i>
                            <p>No price rules found.</p>
                            <a href="{{ route('price-rules.create') }}" class="btn btn-primary btn-sm mt-2">Add your first rule</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($priceRules->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $priceRules->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
