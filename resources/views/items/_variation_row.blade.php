<tr class="variation-row" data-index="{{ $varIndex }}">
    <td>
        <input type="hidden" name="variations[{{ $varIndex }}][id]" value="{{ $vId }}">
        <input type="text" class="form-control form-control-sm" name="variations[{{ $varIndex }}][name]" value="{{ $vName }}">
    </td>
    <td>
        <input type="text" class="form-control form-control-sm" name="variations[{{ $varIndex }}][item_number]" value="{{ $vSku }}">
    </td>
    <td>
        @foreach($attributes as $attr)
            <div class="variation-attr-group">
                <label>{{ $attr->name }}</label>
                <div class="d-flex flex-wrap">
                    @foreach($attr->values as $val)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox"
                                name="variations[{{ $varIndex }}][attribute_value_ids][]"
                                value="{{ $val->id }}"
                                id="var_attr_val_{{ $varIndex }}_{{ $val->id }}"
                                {{ in_array($val->id, $avIds) ? 'checked' : '' }}>
                            <label class="form-check-label" for="var_attr_val_{{ $varIndex }}_{{ $val->id }}">{{ $val->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </td>
    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-text">$</span>
            <input type="number" step="0.001" class="form-control variation-cost" name="variations[{{ $varIndex }}][cost_price]" value="{{ $vCost }}">
        </div>
    </td>
    <td>
        <select class="form-select form-select-sm" name="variations[{{ $varIndex }}][markup_type]">
            <option value="flat" {{ $vMarkupType == 'flat' ? 'selected' : '' }}>Flat</option>
            <option value="percentage" {{ $vMarkupType == 'percentage' ? 'selected' : '' }}>%</option>
        </select>
    </td>
    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-text">$</span>
            <input type="number" step="0.001" class="form-control variation-markup" name="variations[{{ $varIndex }}][markup]" value="{{ $vMarkup }}">
        </div>
    </td>
    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-text">$</span>
            <input type="number" step="0.001" class="form-control variation-unit-price" name="variations[{{ $varIndex }}][unit_price]" value="{{ $vPrice }}">
        </div>
    </td>
    <td>
        <select class="form-select form-select-sm" name="variations[{{ $varIndex }}][supplier_ids][]" multiple size="3">
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->person_id }}" {{ in_array($supplier->person_id, $supIds) ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
            @endforeach
        </select>
    </td>
    <td class="text-center">
        <button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
    </td>
</tr>
