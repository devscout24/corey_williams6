@extends('layouts.app')

@section('title', 'Categories')
@section('page-title', 'Inventory / Categories')

@push('styles')
<style>
  /* Categories Specific Styles */
  .page-content-inner {
    max-width: 1000px;
    margin: 0 auto;
  }
  
  .btn-add-root {
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
  .btn-add-root:hover {
    background: var(--primary-dark);
  }
  
  .category-tree-card {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs);
    padding: 24px 30px;
    clear: both;
  }

  .tree-ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .tree-ul .tree-ul {
    padding-left: 36px;
    position: relative;
  }
  /* Vertical connecting line */
  .tree-ul .tree-ul::before {
    content: '';
    position: absolute;
    top: -12px;
    bottom: 22px;
    left: 11px;
    border-left: 1px solid var(--gray-200);
  }
  
  .tree-li {
    position: relative;
    margin: 0;
  }
  
  .tree-li-inner {
    display: flex;
    align-items: center;
    padding: 10px 0;
    position: relative;
    flex-wrap: wrap;
  }
  /* Horizontal connecting line */
  .tree-ul .tree-ul .tree-li-inner::before {
    content: '';
    position: absolute;
    top: 22px;
    left: -24px;
    width: 24px;
    border-top: 1px solid var(--gray-200);
  }
  
  .tree-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    z-index: 2;
    margin-right: 12px;
  }
  .tree-icon-inner {
    width: 8px;
    height: 8px;
    background: var(--primary);
    transform: rotate(45deg);
    border-radius: 1px;
  }
  
  .tree-text {
    font-size: 14px;
    font-weight: 500;
    color: var(--gray-800);
    min-width: 100px;
  }
  
  .tree-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-left: 8px;
  }
  .tree-action-link {
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    user-select: none;
  }
  .tree-action-link.blue { color: var(--primary); }
  .tree-action-link.blue:hover { color: var(--primary-dark); text-decoration: underline; }
  .tree-action-link.red { color: var(--danger); }
  .tree-action-link.red:hover { color: #dc2626; text-decoration: underline; }

  .tree-checkbox-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 20px;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-900);
    cursor: pointer;
  }
  .tree-checkbox {
    width: 15px;
    height: 15px;
    cursor: pointer;
    border: 1px solid var(--gray-300);
    border-radius: 3px;
  }

  /* Modal Styles */
  .modal-content-category {
    border-radius: 8px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  }
  .modal-header-category {
    background: #f8fafc;
    border-bottom: 1px solid var(--gray-200);
    padding: 15px 24px;
  }
  .modal-title-category {
    font-size: 16px;
    font-weight: 600;
    color: #475569;
  }
  .modal-body-category {
    padding: 30px 40px;
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
  .checkbox-label-custom {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-right: 15px;
    width: 250px;
    text-align: right;
  }
  .form-check-input-custom {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }
  .textarea-custom {
    flex: 1;
    height: 100px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 12px;
    font-size: 14px;
    resize: none;
    outline: none;
  }
  .textarea-custom:focus {
    border-color: var(--primary);
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
  .image-preview-box {
    width: 100%;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 5px;
  }
  .image-preview-box img {
    width: 100%;
    height: auto;
    display: block;
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="page-content-inner">
      <div class="dropdown" style="float: right; margin-left: 10px; margin-bottom: 20px;">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 13.5px; font-weight: 600; padding: 9px 18px; border-radius: var(--radius-sm); border-color: var(--gray-300); color: var(--gray-700); background: #fff;">
          <i class="bi bi-download"></i> Export / Import
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 180px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid var(--gray-200); padding: 6px 0;">
          <li><a class="dropdown-item" href="{{ route('categories.export', ['format' => 'csv']) }}" style="font-size: 13.5px; padding: 8px 16px; font-weight: 500;"><i class="bi bi-filetype-csv" style="margin-right: 8px; color: #10b981;"></i>Export CSV</a></li>
          <li><a class="dropdown-item" href="{{ route('categories.export', ['format' => 'xls']) }}" style="font-size: 13.5px; padding: 8px 16px; font-weight: 500;"><i class="bi bi-file-earmark-excel" style="margin-right: 8px; color: #059669;"></i>Export XLS</a></li>
          <li><hr class="dropdown-divider" style="margin: 4px 0; border-color: var(--gray-200);"></li>
          <li><a class="dropdown-item" href="{{ route('categories.import') }}" style="font-size: 13.5px; padding: 8px 16px; font-weight: 500;"><i class="bi bi-upload" style="margin-right: 8px; color: #f59e0b;"></i>Import</a></li>
        </ul>
      </div>
      <button class="btn-add-root" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-plus-lg"></i> Add root category</button>

      <div class="category-tree-card">
        <ul class="tree-ul">
          @forelse($categories->whereNull('parent_id') as $category)
          <li class="tree-li">
            <div class="tree-li-inner">
              <div class="tree-icon"><div class="tree-icon-inner" style="background: var(--primary);"></div></div>
              <div class="tree-text">{{ $category->name }}</div>
              <div class="tree-actions">
                <span class="tree-action-link blue" data-bs-toggle="modal" data-bs-target="#addCategoryModal" data-parent-id="{{ $category->id }}">[Add child category]</span>
                <span class="tree-action-link blue" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" data-parent-id="{{ $category->parent_id }}">[Edit]</span>
                <form method="post" action="{{ route('categories.destroy', $category->id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Archive this category?')">
                    @csrf
                    @method('delete')
                    <button type="submit" class="p-0 border-0 bg-transparent tree-action-link red">[Delete]</button>
                </form>
              </div>
              <label class="tree-checkbox-wrap">
                Hide from Item Grid
                <input type="checkbox" class="tree-checkbox">
              </label>
            </div>

            @if($categories->where('parent_id', $category->id)->count() > 0)
            <ul class="tree-ul">
              @foreach($categories->where('parent_id', $category->id) as $child1)
              <li class="tree-li">
                <div class="tree-li-inner">
                  <div class="tree-icon"><div class="tree-icon-inner" style="background: #3b82f6;"></div></div>
                  <div class="tree-text">{{ $child1->name }}</div>
                  <div class="tree-actions">
                    <span class="tree-action-link blue" data-bs-toggle="modal" data-bs-target="#addCategoryModal" data-parent-id="{{ $child1->id }}">[Add child category]</span>
                    <span class="tree-action-link blue" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-category-id="{{ $child1->id }}" data-category-name="{{ $child1->name }}" data-parent-id="{{ $child1->parent_id }}">[Edit]</span>
                    <form method="post" action="{{ route('categories.destroy', $child1->id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Archive this category?')">
                        @csrf
                        @method('delete')
                        <button type="submit" class="p-0 border-0 bg-transparent tree-action-link red">[Delete]</button>
                    </form>
                  </div>
                  <label class="tree-checkbox-wrap">
                    Hide from Item Grid
                    <input type="checkbox" class="tree-checkbox">
                  </label>
                </div>

                @if($categories->where('parent_id', $child1->id)->count() > 0)
                <ul class="tree-ul">
                  @foreach($categories->where('parent_id', $child1->id) as $child2)
                  <li class="tree-li">
                    <div class="tree-li-inner">
                      <div class="tree-icon"><div class="tree-icon-inner" style="background: #60a5fa;"></div></div>
                      <div class="tree-text">{{ $child2->name }}</div>
                      <div class="tree-actions">
                        <span class="tree-action-link blue" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-category-id="{{ $child2->id }}" data-category-name="{{ $child2->name }}" data-parent-id="{{ $child2->parent_id }}">[Edit]</span>
                        <form method="post" action="{{ route('categories.destroy', $child2->id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Archive this category?')">
                            @csrf
                            @method('delete')
                            <button type="submit" class="p-0 border-0 bg-transparent tree-action-link red">[Delete]</button>
                        </form>
                      </div>
                      <label class="tree-checkbox-wrap">
                        Hide from Item Grid
                        <input type="checkbox" class="tree-checkbox">
                      </label>
                    </div>
                  </li>
                  @endforeach
                </ul>
                @endif
              </li>
              @endforeach
            </ul>
            @endif
          </li>
          @empty
          <li class="tree-li">
            <div class="tree-li-inner">
              <div class="tree-text text-muted">No categories found.</div>
            </div>
          </li>
          @endforelse
        </ul>
      </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-content-category">
      <form method="post" action="{{ route('categories.store') }}">
        @csrf
        <div class="modal-header modal-header-category">
          <h5 class="modal-title modal-title-category">Add category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
        </div>
        <div class="modal-body modal-body-category">
          
          <div class="form-row-custom">
            <label class="form-label-custom">Parent Category:</label>
            <select class="form-select" id="parent_id" name="parent_id" style="flex: 1; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 12px; font-size: 14px; color: #1e293b; outline: none;">
                <option value="">None</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
          </div>

          <div class="form-row-custom">
            <label class="form-label-custom">Category Name:</label>
            <input type="text" class="form-input-custom" name="name" required>
          </div>

          <div class="form-row-custom" style="margin-bottom: 25px;">
            <label class="checkbox-label-custom">Default Hide From Grid:</label>
            <input type="checkbox" class="form-check-input-custom" checked>
          </div>

          <div class="mt-4 overflow-hidden">
            <button type="submit" class="btn-save-category">Save</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-content-category">
      <form method="post" id="editCategoryForm">
        @csrf
        @method('put')
        <div class="modal-header modal-header-category">
          <h5 class="modal-title modal-title-category">Edit category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 10px;"></button>
        </div>
        <div class="modal-body modal-body-category">
          
          <div class="form-row-custom">
            <label class="form-label-custom">Parent Category:</label>
            <select class="form-select" name="parent_id" style="flex: 1; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px 12px; font-size: 14px; color: #1e293b; outline: none;">
                <option value="">None</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
          </div>

          <div class="form-row-custom">
            <label class="form-label-custom">Category Name:</label>
            <input type="text" class="form-input-custom" name="name" required id="edit_category_name">
          </div>

          <div class="form-row-custom" style="margin-bottom: 25px;">
            <label class="checkbox-label-custom">Default Hide From Grid:</label>
            <input type="checkbox" class="form-check-input-custom" checked>
          </div>

          <div class="mt-4 overflow-hidden">
            <button type="submit" class="btn-save-category">Update</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const addCategoryModal = document.getElementById('addCategoryModal');
  if (addCategoryModal) {
    addCategoryModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const parentId = button.getAttribute('data-parent-id');
      const select = addCategoryModal.querySelector('select[name="parent_id"]');
      
      if (parentId) {
        select.value = parentId;
        addCategoryModal.querySelector('.modal-title-category').textContent = 'Add child category';
      } else {
        select.value = '';
        addCategoryModal.querySelector('.modal-title-category').textContent = 'Add root category';
      }
    });
  }

  const editCategoryModal = document.getElementById('editCategoryModal');
  if (editCategoryModal) {
    editCategoryModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const categoryId = button.getAttribute('data-category-id');
      const categoryName = button.getAttribute('data-category-name');
      const parentId = button.getAttribute('data-parent-id');
      
      const form = document.getElementById('editCategoryForm');
      form.action = `/categories/${categoryId}`;
      
      document.getElementById('edit_category_name').value = categoryName;
      const select = editCategoryModal.querySelector('select[name="parent_id"]');
      select.value = parentId || '';

      // Disable the current category and its children from the parent select to prevent circular references
      Array.from(select.options).forEach(option => {
        option.disabled = (option.value == categoryId);
      });
    });
  }
</script>
@endpush
