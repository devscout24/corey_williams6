<tr class="variation-row" data-index="{{ $varIndex }}">
    <td>
        <input type="hidden" name="variations[{{ $varIndex }}][id]" value="{{ $vId }}">
        <input type="text" class="form-control form-control-sm" name="variations[{{ $varIndex }}][name]" value="{{ $vName }}" placeholder="e.g. Red Large">
    </td>
    <td>
        <input type="text" class="form-control form-control-sm" name="variations[{{ $varIndex }}][item_number]" value="{{ $vSku }}" placeholder="SKU">
    </td>
    <td>
        <div class="attr-assignments" data-index="{{ $varIndex }}">
            @php
                $groupedAv = [];
                foreach ($attributes as $attr) {
                    foreach ($attr->values as $val) {
                        if (in_array($val->id, $avIds)) {
                            $groupedAv[$attr->id]['attribute'] = $attr;
                            $groupedAv[$attr->id]['values'][] = $val;
                        }
                    }
                }
            @endphp

            @foreach($groupedAv as $attrId => $group)
                @php $selectedValIds = collect($group['values'])->pluck('id'); @endphp
                <div class="attr-assignment mb-2 p-2 border rounded bg-light"
                     data-selected-ids='{{ $selectedValIds->toJson() }}'>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <select class="form-select form-select-sm attr-select flex-grow-1">
                            <option value="">— Select Attribute —</option>
                            @foreach($attributes as $attr)
                                <option value="{{ $attr->id }}" {{ $attr->id == $attrId ? 'selected' : '' }}>
                                    {{ $attr->name }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-outline-danger remove-attr-assignment" type="button">&times;</button>
                    </div>
                    <div class="attr-values d-flex flex-wrap gap-2"></div>
                </div>
            @endforeach

            <button type="button" class="btn btn-sm btn-outline-primary add-attr-assignment">
                <i class="bi bi-plus"></i> Attribute
            </button>
        </div>
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
        <select class="form-select form-select-sm supplier-select" name="variations[{{ $varIndex }}][supplier_ids][]" multiple>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->person_id }}" {{ in_array($supplier->person_id, $supIds) ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
            @endforeach
        </select>
    </td>
    <td class="text-center">
        <button class="btn btn-sm btn-outline-danger remove-row" type="button"><i class="bi bi-trash"></i></button>
    </td>
</tr>
