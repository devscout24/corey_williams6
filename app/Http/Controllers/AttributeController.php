<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(): View
    {
        $attributes = Attribute::with('values')->where('deleted', 0)->whereNull('item_id')->orderBy('id')->get();

        return view('attributes.index', compact('attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $attributesData = $request->input('attributes', []);
        $itemsAdded = $request->input('items_added', []);
        $attributesToDelete = $request->input('attributes_to_delete', []);

        DB::transaction(function () use ($attributesData, $itemsAdded, $attributesToDelete) {
            // Process existing attributes
            foreach ($attributesData as $id => $data) {
                if (isset($data['name']) && trim($data['name']) !== '') {
                    $attribute = Attribute::find($id);
                    if ($attribute) {
                        $attribute->name = trim($data['name']);
                        $attribute->save();

                        $this->saveAttributeValues($attribute->id, $data['values'] ?? '');
                    }
                }
            }

            // Process newly added attributes
            if (is_array($itemsAdded)) {
                foreach ($itemsAdded as $data) {
                    if (isset($data['name']) && trim($data['name']) !== '') {
                        $attribute = Attribute::create([
                            'name' => trim($data['name']),
                            'deleted' => 0
                        ]);

                        $this->saveAttributeValues($attribute->id, $data['values'] ?? '');
                    }
                }
            }

            // Process deletions
            if (!empty($attributesToDelete)) {
                Attribute::whereIn('id', $attributesToDelete)->update(['deleted' => 1]);
                AttributeValue::whereIn('attribute_id', $attributesToDelete)->update(['deleted' => 1]);
            }
        });

        return redirect()->route('attributes.index')->with('status', 'Attributes successfully saved.');
    }

    private function saveAttributeValues(int $attributeId, string $valuesString)
    {
        $values = array_filter(array_map('trim', explode('|', $valuesString)));
        $existingValues = AttributeValue::where('attribute_id', $attributeId)->where('deleted', 0)->get();

        $existingNames = $existingValues->pluck('name')->toArray();
        
        // Find values to delete
        $valuesToDelete = array_diff($existingNames, $values);
        if (!empty($valuesToDelete)) {
            AttributeValue::where('attribute_id', $attributeId)
                ->whereIn('name', $valuesToDelete)
                ->update(['deleted' => 1]);
        }

        // Find values to add/restore
        foreach ($values as $val) {
            AttributeValue::updateOrCreate(
                ['attribute_id' => $attributeId, 'name' => $val],
                ['deleted' => 0]
            );
        }
    }
}
