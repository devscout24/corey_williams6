<?php

namespace App\Http\Controllers;

use App\Models\PhpposRegisterCurrencyDenomination;
use App\Services\AppConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfigController extends Controller
{
    public function index(AppConfigService $configService): View
    {
        // Define all configuration keys we want to manage
        $configKeys = [
            'company', 'company_logo', 'address', 'phone', 'website', 'email', 'fax',
            'return_policy', 'announcement_special',
            'default_tax_1_name', 'default_tax_1_rate', 'default_tax_2_name', 'default_tax_2_rate', 'default_tax_2_cumulative',
            'currency_symbol', 'currency_code', 'currency_symbol_location', 'number_of_decimals', 'thousands_separator', 'decimal_point',
            'language', 'date_format', 'time_format', 'timezone',
            'print_after_sale', 'print_after_receiving', 'automatically_email_receipt', 'hide_signature',
            'enable_customer_loyalty_system', 'loyalty_option', 'point_value', 'spend_to_point_ratio',
            'customers_store_accounts', 'suppliers_store_accounts', 'calculate_average_cost_price_from_receivings',
            'sale_prefix', 'receiving_prefix', 'id_to_show_on_sale_interface', 'number_of_recent_sales',
            'hide_dashboard_statistics', 'show_language_switcher', 'show_clock_on_header',
            'additional_payment_types', 'default_payment_type',
            'barcode_type', 'barcode_width', 'barcode_height', 'barcode_quality', 'barcode_font_size',
            'barcode_background',
            'label_sheet_background',
            'show_barcode_company_name', 'hide_barcode_on_barcode_labels',
            'phppos_session_expiration', 'speed_up_search_queries', 'enable_sounds',
            'receipt_text_size', 'disable_price_rules_dialog',
        ];

        $values = [];
        foreach ($configKeys as $key) {
            $values[$key] = $configService->get($key, '');
        }

        $exchange_rates = \App\Models\PhpposCurrencyExchangeRate::all();
        $currency_denoms = PhpposRegisterCurrencyDenomination::query()
            ->where('deleted', 0)
            ->orderBy('id')
            ->get();
        $locations = \App\Models\PhpposLocation::where('deleted', 0)->get();

        return view('config.index', compact('values', 'exchange_rates', 'currency_denoms', 'locations'));
    }

    public function update(Request $request, AppConfigService $configService): RedirectResponse
    {
        // In a real app, you might want more specific validation per field
        // For now, we'll allow all keys provided in the request that match our config
        $data = $request->except([
            '_token',
            '_method',
            'ecommerce_locations',
            'currency_exchange_rates_to',
            'currency_exchange_rates_symbol',
            'currency_exchange_rates_rate',
            'currency_exchange_rates_symbol_location',
            'currency_exchange_rates_number_of_decimals',
            'currency_exchange_rates_thousands_separator',
            'currency_exchange_rates_decimal_point',
            'locations_color',
            'locations_secondary_color',
            'currency_denoms_name',
            'currency_denoms_value',
            'currency_denoms_ids',
            'deleted_denmos',
            'config_exchange_rates_sync',
        ]);
        
        // Handle checkboxes (convert missing values to 0)
        $checkboxes = [
            'print_after_sale', 'print_after_receiving', 'automatically_email_receipt', 'hide_signature',
            'default_tax_2_cumulative', 'enable_customer_loyalty_system', 'customers_store_accounts',
            'suppliers_store_accounts', 'calculate_average_cost_price_from_receivings',
            'hide_dashboard_statistics', 'show_language_switcher', 'show_clock_on_header',
            'speed_up_search_queries', 'enable_sounds',
            'show_barcode_company_name', 'hide_barcode_on_barcode_labels',
            'disable_price_rules_dialog'
        ];

        foreach ($checkboxes as $checkbox) {
            $data[$checkbox] = $request->has($checkbox) ? '1' : '0';
        }

        // Handle File Uploads
        $files = ['company_logo', 'barcode_background', 'label_sheet_background'];
        foreach ($files as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $fileData = file_get_contents($file->getRealPath());
                
                $appFile = \App\Models\PhpposAppFile::create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_data' => $fileData,
                ]);
                
                $data[$fileKey] = $appFile->file_id;
            }
        }

        if (!$configService->batchSave($data)) {
            return back()->withErrors(['config' => 'Error saving configuration. Please check for duplicate tax settings.']);
        }



        if ($request->boolean('config_exchange_rates_sync')) {
            \App\Models\PhpposCurrencyExchangeRate::truncate();
            $tos = (array) $request->input('currency_exchange_rates_to', []);
            $symbols = (array) $request->input('currency_exchange_rates_symbol', []);
            $rates = (array) $request->input('currency_exchange_rates_rate', []);
            $symbolLocations = (array) $request->input('currency_exchange_rates_symbol_location', []);
            $decimals = (array) $request->input('currency_exchange_rates_number_of_decimals', []);
            $thousands = (array) $request->input('currency_exchange_rates_thousands_separator', []);
            $decimalPoints = (array) $request->input('currency_exchange_rates_decimal_point', []);

            for ($i = 0, $n = count($tos); $i < $n; $i++) {
                if (($tos[$i] ?? '') === '' || ($rates[$i] ?? '') === '') {
                    continue;
                }
                \App\Models\PhpposCurrencyExchangeRate::create([
                    'currency_code_to' => $tos[$i],
                    'currency_symbol' => $symbols[$i] ?? '',
                    'exchange_rate' => $rates[$i],
                    'currency_symbol_location' => $symbolLocations[$i] ?? 'before',
                    'number_of_decimals' => $decimals[$i] ?? '',
                    'thousands_separator' => $thousands[$i] ?? '',
                    'decimal_point' => $decimalPoints[$i] ?? '',
                ]);
            }
        }

        $deletedDenomIds = array_values(array_filter(
            array_map(static fn ($v): int => (int) $v, (array) $request->input('deleted_denmos', [])),
            static fn (int $id): bool => $id > 0
        ));
        if ($deletedDenomIds !== []) {
            PhpposRegisterCurrencyDenomination::query()
                ->whereIn('id', $deletedDenomIds)
                ->update(['deleted' => 1]);
        }

        $denomNames = $request->input('currency_denoms_name', []);
        $denomValues = $request->input('currency_denoms_value', []);
        $denomIds = $request->input('currency_denoms_ids', []);
        if (is_array($denomNames)) {
            for ($k = 0, $denomCount = count($denomNames); $k < $denomCount; $k++) {
                $name = trim((string) ($denomNames[$k] ?? ''));
                $rawValue = $denomValues[$k] ?? '0';
                $value = is_numeric($rawValue)
                    ? (float) $rawValue
                    : (float) preg_replace('/[^0-9.\-]/', '', (string) $rawValue);
                $idRaw = $denomIds[$k] ?? '';
                $id = is_numeric($idRaw) && (string) $idRaw !== '' ? (int) $idRaw : null;

                if ($name === '') {
                    continue;
                }

                if ($id !== null && PhpposRegisterCurrencyDenomination::query()->whereKey($id)->exists()) {
                    PhpposRegisterCurrencyDenomination::query()->whereKey($id)->update([
                        'name' => $name,
                        'value' => $value,
                        'deleted' => 0,
                    ]);
                } else {
                    PhpposRegisterCurrencyDenomination::query()->create([
                        'name' => $name,
                        'value' => $value,
                        'deleted' => 0,
                    ]);
                }
            }
        }

        if ($request->has('locations_color') && is_array($request->locations_color)) {
            foreach ($request->locations_color as $locId => $color) {
                \App\Models\PhpposLocation::where('location_id', $locId)->update([
                    'color' => $color,
                    'secondary_color' => $request->locations_secondary_color[$locId] ?? null
                ]);
            }
        }

        return back()->with('status', 'Store configuration updated successfully.');
    }
}
