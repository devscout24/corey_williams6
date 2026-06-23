<?php

namespace App\Http\Controllers;

use App\Models\PhpposEmployeeAppConfig;
use Illuminate\Http\Request;

class InventoryColumnSettingController extends Controller
{
    private function getConfigKey(Request $request): string
    {
        $type = $request->segment(1);

        return match ($type) {
            'items' => 'items_column_prefs',
            'customers' => 'customers_column_prefs',
            'suppliers' => 'suppliers_column_prefs',
            default => $type . '_column_prefs',
        };
    }

    public function saveColumnPrefs(Request $request)
    {
        $employee_id = auth('employee')->id();
        $columns = $request->input('columns', []);
        $key = $this->getConfigKey($request);

        PhpposEmployeeAppConfig::updateOrCreate(
            ['employee_id' => $employee_id, 'key' => $key],
            ['value' => implode(',', $columns)]
        );

        return response()->json(['success' => true]);
    }

    public function resetColumnPrefs(Request $request)
    {
        $employee_id = auth('employee')->id();
        $key = $this->getConfigKey($request);

        PhpposEmployeeAppConfig::where('employee_id', $employee_id)
            ->where('key', $key)
            ->delete();

        return response()->json(['success' => true]);
    }
}
