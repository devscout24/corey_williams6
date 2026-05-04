@extends('layouts.app')

@section('title', 'Tags')
@section('page-title', 'Inventory / Tags')

@push('styles')
<style>
  .page-content-inner {
    max-width: 1000px;
    margin: 0 auto;
  }

  .btn-add-tag {
    background: var(--primary);
    color: #fff;
    font-weight: 600;
    font-size: 13.5px;
    padding: 9px 18px;
    border-radius: var(--radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
    border: none;
    margin-bottom: 20px;
    float: right;
  }
  .btn-add-tag:hover {
    background: var(--primary-dark);
  }

  .tags-card {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs);
    clear: both;
    overflow: hidden;
  }
  [data-theme='dark'] .tags-card {
    background: var(--gray-100);
  }

  .tags-table {
    width: 100%;
    border-collapse: collapse;
  }
  .tags-table th {
    background: var(--gray-50);
    padding: 14px 24px;
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-800);
    text-transform: capitalize;
    border-bottom: 1px solid var(--gray-200);
  }
  [data-theme='dark'] .tags-table th {
    background: var(--gray-200);
  }
  .tags-table td {
    padding: 14px 24px;
    font-size: 14px;
    font-weight: 500;
    color: var(--gray-800);
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
  }
  .tags-table tr:last-child td {
    border-bottom: none;
  }
  
  .tags-table th.col-action,
  .tags-table td.col-action {
    text-align: right;
    width: 120px;
  }

  .row-actions {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: var(--gray-500);
  }
  .action-icon {
    cursor: pointer;
    transition: var(--transition);
    font-size: 16px;
  }
  .action-icon:hover {
    color: var(--primary);
  }
  .action-icon.text-danger:hover {
    color: var(--danger);
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="page-content-inner">
      <a href="{{ route('tags.create') }}" class="btn-add-tag"><i class="bi bi-plus-lg"></i> Add Tag</a>

      <div class="tags-card">
        <table class="tags-table">
          <thead>
            <tr>
              <th>Tag Name</th>
              <th class="col-action">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tags ?? [] as $tag)
            <tr>
              <td>{{ $tag->name }}</td>
              <td class="col-action">
                <div class="row-actions">
                  {{-- Edit Link --}}
                  <a href="{{ route('tags.edit', $tag->id) }}">
                    <i class="bi bi-pencil-square action-icon"></i>
                  </a>

                  {{-- Delete Form --}}
                  <form method="POST" action="{{ route('tags.destroy', $tag->id) }}" class="d-inline m-0 p-0 delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-0 border-0 bg-transparent">
                      <i class="bi bi-trash3 action-icon text-danger"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="2" class="text-center py-4 text-muted">No tags found.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
</div>
@endsection

@push('scripts')
  
@endpush
