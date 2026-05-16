<?php

namespace App\Http\Controllers;

use App\Models\PhpposEmployeeAppConfig;
use Illuminate\Http\Request;

class InventoryColumnSettingController extends Controller
{
    public function saveColumnPrefs(Request $request)
    {
        $employee_id = auth('employee')->id();
        $columns = $request->input('columns', []);
        
        $key = 'items_column_prefs';
        
        PhpposEmployeeAppConfig::updateOrCreate(
            ['employee_id' => $employee_id, 'key' => $key],
            ['value' => implode(',', $columns)]
        );

        return response()->json(['success' => true]);
    }

    public function resetColumnPrefs()
    {
        $employee_id = auth('employee')->id();
        $key = 'items_column_prefs';
        
        PhpposEmployeeAppConfig::where('employee_id', $employee_id)
            ->where('key', $key)
            ->delete();

        return response()->json(['success' => true]);
    }
}
