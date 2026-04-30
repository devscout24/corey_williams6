@extends('layouts.app')

@section('title', isset($priceRule) ? 'Edit Price Rule' : 'Add Price Rule')
@section('page-title', 'Inventory / Price Rules')

@push('styles')
<!-- Selectize CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css" />
<style>
    .page-content-inner {
        max-width: 1000px;
        margin: 0 auto;
    }
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--gray-800);
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--gray-100);
    }
    .hidden {
        display: none !important;
    }
    .required:after {
        content: " *";
        color: var(--danger);
    }
</style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="page-content-inner">
        <form method="POST" action="{{ isset($priceRule) ? route('price-rules.update', $priceRule->id) : route('price-rules.store') }}" id="price_rule_form">
            @csrf
            @if(isset($priceRule))
                @method('PUT')
            @endif

            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="form-section-title">Basic Information</div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Rule Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="simple_discount" {{ (isset($priceRule) && $priceRule->type == 'simple_discount') ? 'selected' : '' }}>Simple Discount</option>
                            <option value="buy_x_get_y_free" {{ (isset($priceRule) && $priceRule->type == 'buy_x_get_y_free') ? 'selected' : '' }}>Buy X Get Y Free</option>
                            <option value="buy_x_get_discount" {{ (isset($priceRule) && $priceRule->type == 'buy_x_get_discount') ? 'selected' : '' }}>Buy X Get Discount</option>
                            <option value="spend_x_get_discount" {{ (isset($priceRule) && $priceRule->type == 'spend_x_get_discount') ? 'selected' : '' }}>Spend X Get Discount</option>
                            <option value="advanced_discount" {{ (isset($priceRule) && $priceRule->type == 'advanced_discount') ? 'selected' : '' }}>Advanced Discount</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">Rule Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $priceRule->name ?? '' }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $priceRule->description ?? '' }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ isset($priceRule->start_date) ? $priceRule->start_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ isset($priceRule->end_date) ? $priceRule->end_date->format('Y-m-d') : '' }}">
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="active" value="1" id="active" {{ (!isset($priceRule) || $priceRule->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Rule is Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="form-section-title">Conditions & Exclusions</div>
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Locations (Leave blank for all)</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($locations as $location)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="locations[]" value="{{ $location->location_id }}" id="loc_{{ $location->location_id }}" 
                                    {{ (isset($priceRule) && $priceRule->locations->contains($location->location_id)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="loc_{{ $location->location_id }}">{{ $location->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-12 mt-4">
                        <label class="form-label">Exclude from Price Tiers</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($tiers as $tier)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="excluded_tiers[]" value="{{ $tier->id }}" id="tier_{{ $tier->id }}"
                                    {{ (isset($priceRule) && DB::table('phppos_price_rules_tiers_exclude')->where('price_rule_id', $priceRule->id)->where('tier_id', $tier->id)->exists()) ? 'checked' : '' }}>
                                <label class="form-check-label" for="tier_{{ $tier->id }}">{{ $tier->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-6 mt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="requires_coupon" {{ !empty($priceRule->coupon_code) ? 'checked' : '' }}>
                            <label class="form-check-label" for="requires_coupon">Requires Coupon Code</label>
                        </div>
                        <div id="coupon_fields" class="{{ empty($priceRule->coupon_code) ? 'hidden' : '' }} mt-3">
                            <input type="text" name="coupon_code" class="form-control mb-2" placeholder="Enter Coupon Code" value="{{ $priceRule->coupon_code ?? '' }}">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_on_receipt" value="1" id="show_on_receipt" {{ ($priceRule->show_on_receipt ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_on_receipt">Show on Receipt</label>
                            </div>
                            <input type="number" step="any" name="coupon_spend_amount" class="form-control mt-2" placeholder="Min Spend Amount" value="{{ $priceRule->coupon_spend_amount ?? '' }}">
                        </div>
                    </div>

                    <div class="col-md-6 mt-4">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="disable_loyalty_for_rule" value="1" id="disable_loyalty" {{ ($priceRule->disable_loyalty_for_rule ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="disable_loyalty">Disable Loyalty for this Rule</label>
                        </div>
                    </div>
                </div>
            </div>

            <div id="select_fields" class="card border-0 shadow-sm p-4 mb-4 hidden">
                <div class="form-section-title">Select Items / Categories / Tags</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Items</label>
                        <input type="text" name="items" class="selectize-input" value="{{ isset($rule_items) ? $rule_items->pluck('id')->implode(',') : '' }}">
                        <small class="text-muted">Comma separated IDs (e.g. 1,2,3)</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Item Kits</label>
                        <input type="text" name="itemkits" class="selectize-input" value="{{ isset($rule_item_kits) ? $rule_item_kits->pluck('id')->implode(',') : '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Categories</label>
                        <input type="text" name="categories" class="selectize-input" value="{{ isset($rule_cats) ? $rule_cats->pluck('id')->implode(',') : '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tags</label>
                        <input type="text" name="tags" class="selectize-input" value="{{ isset($rule_tags) ? $rule_tags->pluck('id')->implode(',') : '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Manufacturers</label>
                        <input type="text" name="manufacturers" class="selectize-input" value="{{ isset($rule_manus) ? $rule_manus->pluck('id')->implode(',') : '' }}">
                    </div>
                    <div class="col-md-6 mt-4 d-flex align-items-center" id="mix_and_match_container">
                         <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="mix_and_match" value="1" id="mix_and_match" {{ ($priceRule->mix_and_match ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="mix_and_match">Mix and Match</label>
                        </div>
                    </div>
                </div>
            </div>

            <div id="rule_details" class="card border-0 shadow-sm p-4 mb-4 hidden">
                <div class="form-section-title">Rule Details</div>
                
                <div id="buy_get_fields" class="row g-3 hidden">
                    <div class="col-md-6">
                        <label class="form-label">Items to Buy</label>
                        <input type="number" name="items_to_buy" class="form-control" value="{{ $priceRule->items_to_buy ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Items to Get (Free or Discounted)</label>
                        <input type="number" name="items_to_get" class="form-control" value="{{ $priceRule->items_to_get ?? '' }}">
                    </div>
                </div>

                <div id="spend_amount_field" class="row g-3 hidden">
                    <div class="col-md-6">
                        <label class="form-label">Spend Amount</label>
                        <input type="number" step="any" name="spend_amount" class="form-control" value="{{ $priceRule->spend_amount ?? '' }}">
                    </div>
                </div>

                <div id="discount_fields" class="row g-3 mt-2 hidden">
                    <div class="col-md-6">
                        <label class="form-label">Percent Off</label>
                        <div class="input-group">
                            <input type="number" step="any" name="percent_off" id="percent_off" class="form-control" value="{{ isset($priceRule->percent_off) ? (float)$priceRule->percent_off : '' }}">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-center justify-content-center mt-4">
                        <strong>OR</strong>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Fixed Off</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="any" name="fixed_off" id="fixed_off" class="form-control" value="{{ isset($priceRule->fixed_off) ? (float)$priceRule->fixed_off : '' }}">
                        </div>
                    </div>
                </div>

                <div id="times_to_apply_container" class="row g-3 mt-3 hidden">
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="unlimited" {{ (isset($priceRule) && $priceRule->num_times_to_apply == 0) ? 'checked' : '' }}>
                            <label class="form-check-label" for="unlimited">Unlimited Applications</label>
                        </div>
                        <div id="num_times_field" class="{{ (isset($priceRule) && $priceRule->num_times_to_apply == 0) ? 'hidden' : '' }}">
                            <label class="form-label">Max Times to Apply</label>
                            <input type="number" name="num_times_to_apply" id="num_times_to_apply" class="form-control" value="{{ $priceRule->num_times_to_apply ?? 1 }}">
                        </div>
                    </div>
                </div>

                <div id="price_breaks_container" class="mt-4 hidden">
                    <label class="form-label">Price Breaks</label>
                    <table class="table table-bordered align-middle" id="price_breaks_table">
                        <thead>
                            <tr class="bg-light">
                                <th width="50"></th>
                                <th>Qty to Buy</th>
                                <th>Flat Discount</th>
                                <th>Percent Discount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($priceRule) && $priceRule->priceBreaks->count() > 0)
                                @foreach($priceRule->priceBreaks as $break)
                                <tr>
                                    <td><button type="button" class="btn btn-sm text-danger p-0 remove-row"><i class="bi bi-x-circle fs-5"></i></button></td>
                                    <td><input type="number" name="qty_to_buy[]" class="form-control" value="{{ $break->item_qty_to_buy }}"></td>
                                    <td><input type="number" step="any" name="flat_unit_discount[]" class="form-control" value="{{ $break->discount_per_unit_fixed }}"></td>
                                    <td><input type="number" step="any" name="percent_unit_discount[]" class="form-control" value="{{ $break->discount_per_unit_percent }}"></td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td><button type="button" class="btn btn-sm text-danger p-0 remove-row"><i class="bi bi-x-circle fs-5"></i></button></td>
                                    <td><input type="number" name="qty_to_buy[]" class="form-control"></td>
                                    <td><input type="number" step="any" name="flat_unit_discount[]" class="form-control"></td>
                                    <td><input type="number" step="any" name="percent_unit_discount[]" class="form-control"></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add_price_break"><i class="bi bi-plus-lg"></i> Add Price Break</button>
                </div>
            </div>

            <div class="mt-4 mb-5 text-end">
                <a href="{{ route('price-rules.index') }}" class="btn btn-light border me-2">Cancel</a>
                <button type="submit" class="btn btn-primary px-5">Save Price Rule</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"></script>
<script>
    $(document).ready(function() {
        function toggleFields() {
            const type = $('#type').val();
            
            // Default hidden
            $('#select_fields, #rule_details, #buy_get_fields, #spend_amount_field, #discount_fields, #times_to_apply_container, #price_breaks_container, #mix_and_match_container').addClass('hidden');
            
            if (type) {
                $('#rule_details').removeClass('hidden');
            }

            switch(type) {
                case 'simple_discount':
                    $('#select_fields, #discount_fields, #times_to_apply_container').removeClass('hidden');
                    break;
                case 'buy_x_get_y_free':
                    $('#select_fields, #buy_get_fields, #times_to_apply_container, #mix_and_match_container').removeClass('hidden');
                    break;
                case 'buy_x_get_discount':
                    $('#select_fields, #buy_get_fields, #discount_fields, #times_to_apply_container, #mix_and_match_container').removeClass('hidden');
                    break;
                case 'spend_x_get_discount':
                    $('#spend_amount_field, #discount_fields, #times_to_apply_container').removeClass('hidden');
                    break;
                case 'advanced_discount':
                    $('#select_fields, #price_breaks_container, #mix_and_match_container').removeClass('hidden');
                    break;
            }
        }

        $('#type').change(toggleFields);
        toggleFields(); // Init

        $('#requires_coupon').change(function() {
            $('#coupon_fields').toggleClass('hidden', !$(this).is(':checked'));
            if (!$(this).is(':checked')) {
                $('#coupon_fields input').val('');
                $('#show_on_receipt').prop('checked', false);
            }
        });

        $('#unlimited').change(function() {
            $('#num_times_field').toggleClass('hidden', $(this).is(':checked'));
            if ($(this).is(':checked')) {
                $('#num_times_to_apply').val(0);
            } else if ($('#num_times_to_apply').val() == 0) {
                $('#num_times_to_apply').val(1);
            }
        });

        $('#add_price_break').click(function() {
            const newRow = `
                <tr>
                    <td><button type="button" class="btn btn-sm text-danger p-0 remove-row"><i class="bi bi-x-circle fs-5"></i></button></td>
                    <td><input type="number" name="qty_to_buy[]" class="form-control"></td>
                    <td><input type="number" step="any" name="flat_unit_discount[]" class="form-control"></td>
                    <td><input type="number" step="any" name="percent_unit_discount[]" class="form-control"></td>
                </tr>`;
            $('#price_breaks_table tbody').append(newRow);
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#price_breaks_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                $(this).closest('tr').find('input').val('');
            }
        });

        // Mutually exclusive discounts
        $('#percent_off').keyup(function() {
            if ($(this).val()) $('#fixed_off').val('');
        });
        $('#fixed_off').keyup(function() {
            if ($(this).val()) $('#percent_off').val('');
        });

        // Initialize Selectize
        $('.selectize-input').each(function() {
            $(this).selectize({
                plugins: ['remove_button'],
                delimiter: ',',
                persist: false,
                create: function(input) {
                    return {
                        value: input,
                        text: input
                    }
                }
            });
        });
    });
</script>
@endpush
