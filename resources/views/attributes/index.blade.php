@extends('layouts.app')

@section('title', 'Attributes')
@section('page-title', 'Inventory / Attributes')

@push('styles')
<!-- Selectize CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css" integrity="sha512-pTaEn+6gF1IeWv3W1+7X7eM60Tq/x83PTegs8tC1rSm2Z0x2xYd6mDk2/XGkFhXU0ZpU1wHbgm6w2Uv7X/23Aw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
  .page-content-inner {
    max-width: 1000px;
    margin: 0 auto;
  }

  .attributes-card {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
    box-shadow: var(--shadow-xs);
    overflow: hidden;
  }

  .attributes-table {
    width: 100%;
    border-collapse: collapse;
  }
  .attributes-table th {
    background: #f8fafc;
    padding: 14px 24px;
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-800);
    text-transform: capitalize;
    border-bottom: 1px solid var(--gray-200);
  }
  .attributes-table td {
    padding: 14px 24px;
    font-size: 14px;
    font-weight: 500;
    color: var(--gray-800);
    border-bottom: 1px solid var(--gray-100);
    vertical-align: top;
  }
  .attributes-table tr:last-child td {
    border-bottom: none;
  }

  .col-action {
    text-align: right;
    width: 120px;
  }
  .col-name {
    width: 30%;
  }
  .col-values {
    width: 60%;
  }

  .action-icon {
    cursor: pointer;
    transition: var(--transition);
    font-size: 16px;
    color: var(--danger);
  }
  .action-icon:hover {
    color: #a71d2a;
  }
  
  .add-attribute-btn {
    display: inline-block;
    margin: 16px 24px;
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
  }
  .add-attribute-btn:hover {
    text-decoration: underline;
  }

  .form-actions {
      padding: 16px 24px;
      background: #f8fafc;
      border-top: 1px solid var(--gray-200);
      text-align: right;
  }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="page-content-inner">
      <form method="POST" action="{{ route('attributes.store') }}" id="save_attributes_form">
        @csrf
        <div class="attributes-card">
          <table class="attributes-table" id="attributes_table">
            <thead>
              <tr>
                <th class="col-name">Name</th>
                <th class="col-values">Values</th>
                <th class="col-action">Delete</th>
              </tr>
            </thead>
            <tbody>
              @foreach($attributes as $attribute)
                @php
                  $valuesArray = $attribute->values->pluck('name')->toArray();
                  $valuesStr = implode('|', $valuesArray);
                @endphp
                <tr data-index="{{ $attribute->id }}">
                  <td>
                    <input type="text" class="form-control" name="attributes[{{ $attribute->id }}][name]" value="{{ $attribute->name }}" required>
                  </td>
                  <td>
                    <input type="text" class="form-control selectize-values" name="attributes[{{ $attribute->id }}][values]" value="{{ $valuesStr }}">
                  </td>
                  <td class="col-action">
                    <i class="bi bi-trash3 action-icon delete-attribute" title="Delete Attribute"></i>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <a href="javascript:void(0);" class="add-attribute-btn" id="add_attribute"><i class="bi bi-plus-circle"></i> Add Attribute</a>
          
          <div id="deleted_inputs"></div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Attributes</button>
          </div>
        </div>
      </form>
    </div>
</div>
@endsection

@push('scripts')
<!-- jQuery (Required for Selectize) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<!-- Selectize JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js" integrity="sha512-IOebNkvA/HZjMM7ziT+60A2u9m55C0xk1O81H2PqB1E2Qn2h7B1F34/jI2XoF6G2hF+pTqWpE1h2K8Z4Cxg8w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
  $(document).ready(function() {
      // Initialize existing selectize inputs
      function initSelectize($el) {
          $el.selectize({
              delimiter: '|',
              persist: false,
              create: function(input) {
                  return {
                      value: input,
                      text: input
                  }
              },
              render: {
                  option_create: function(data, escape) {
                      return '<div class="create">Add <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                  }
              }
          });
      }

      $('.selectize-values').each(function() {
          initSelectize($(this));
      });

      let attributeIndex = -1;

      // Add new attribute row
      $('#add_attribute').on('click', function(e) {
          e.preventDefault();
          const trHtml = `
            <tr data-index="${attributeIndex}">
              <td>
                <input type="text" class="form-control" name="items_added[${attributeIndex}][name]" value="" required placeholder="e.g. Color">
              </td>
              <td>
                <input type="text" class="form-control new-selectize" name="items_added[${attributeIndex}][values]" value="">
              </td>
              <td class="col-action">
                <i class="bi bi-trash3 action-icon delete-attribute" title="Delete Attribute"></i>
              </td>
            </tr>
          `;
          $('#attributes_table tbody').append(trHtml);
          
          // Initialize selectize on the newly added input
          const $newInput = $('#attributes_table tbody tr:last').find('.new-selectize');
          initSelectize($newInput);
          $newInput.removeClass('new-selectize');

          attributeIndex--;
      });

      // Handle deletion
      $(document).on('click', '.delete-attribute', function() {
          const $tr = $(this).closest('tr');
          const index = $tr.data('index');
          
          // If it's an existing attribute (index > 0), add a hidden input so the controller knows to delete it
          if (index > 0) {
              $('#deleted_inputs').append(`<input type="hidden" name="attributes_to_delete[]" value="${index}">`);
          }
          
          $tr.remove();
      });
  });
</script>
@endpush
