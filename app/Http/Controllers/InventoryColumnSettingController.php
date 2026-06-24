<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $key = $this->getConfigKey($request);
        $orderKey = str_replace('_prefs', '_order', $key);

        $columns = $request->input('columns', []);
        $visibleColumns = $request->input('visible_columns', $columns);

        // Store visible columns (backward compatible)
        DB::table('phppos_employees_app_config')->updateOrInsert(
            ['employee_id' => $employee_id, 'key' => $key],
            ['value' => implode(',', $visibleColumns)]
        );

        // Store full column order
        DB::table('phppos_employees_app_config')->updateOrInsert(
            ['employee_id' => $employee_id, 'key' => $orderKey],
            ['value' => implode(',', $columns)]
        );

        return response()->json(['success' => true]);
    }

    public function resetColumnPrefs(Request $request)
    {
        $employee_id = auth('employee')->id();
        $key = $this->getConfigKey($request);
        $orderKey = str_replace('_prefs', '_order', $key);

        DB::table('phppos_employees_app_config')
            ->where('employee_id', $employee_id)
            ->whereIn('key', [$key, $orderKey])
            ->delete();

        return response()->json(['success' => true]);
    }
}
