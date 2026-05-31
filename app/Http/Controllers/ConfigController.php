<?php

namespace App\Http\Controllers;

use App\Models\PhpposRegisterCurrencyDenomination;
use App\Models\PhpposTaxClass;
use App\Models\PhpposTaxClassTax;
use App\Models\PhpposPriceTier;
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
            'tax_id',
            'tax_class_id',
            'flat_discounts_discount_tax', 'prices_include_tax', 'charge_tax_on_recv',
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
            // Sales module
            'hide_supplier_on_sales_interface', 'hide_supplier_on_recv_interface', 'allow_drag_drop_sale',
            'allow_drag_drop_recv', 'disable_discounts_percentage_per_line_item', 'disabled_fixed_discounts',
            'disable_discount_by_percentage', 'disable_sale_cloning', 'always_put_last_added_item_on_top_of_cart',
            'disable_recv_cloning', 'scan_and_set_sales', 'scan_and_set_recv', 'damaged_reasons',
            'enable_tips', 'tip_preset_zero', 'auto_focus_on_item_after_sale_and_receiving',
            'capture_internal_notes_during_sale', 'capture_sig_for_all_payments',
            'hide_customer_recent_sales', 'enable_customer_quick_add', 'enable_supplier_quick_add',
            'collapse_sales_ui_by_default', 'collapse_recv_ui_by_default', 'disable_confirmation_sale',
            'disable_confirm_recv', 'disable_quick_complete_sale', 'averaging_method',
            'update_cost_price_on_transfer', 'require_supplier_for_recv', 'track_shipping_cost_recv',
            'hide_suspended_recv_in_reports', 'do_not_show_closing', 'hide_available_giftcards',
            'show_giftcards_even_if_0_balance', 'disable_giftcard_detection', 'always_show_item_grid',
            'hide_images_in_grid', 'quick_variation_grid', 'hide_out_of_stock_grid', 'default_type_for_grid',
            'require_customer_for_sale', 'select_sales_person_during_sale', 'default_sales_person',
            'commission_default_rate', 'commission_percent_type', 'disable_sale_notifications',
            'confirm_error_adding_item', 'change_sale_date_for_new_sale', 'do_not_group_same_items',
            'do_not_allow_below_cost', 'do_not_allow_out_of_stock_items_to_be_sold',
            'do_not_allow_items_to_go_out_of_stock_when_transfering',
            'do_not_allow_item_with_variations_to_be_sold_without_selecting_variation',
            'edit_item_price_if_zero_after_adding', 'remind_customer_facing_display',
            'do_not_allow_sales_with_zero_value', 'prompt_amount_for_cash_sale', 'show_qr_code_for_sale',
            'qr_code_format', 'disable_verification_for_qr_codes', 'hide_categories_sales_grid',
            'hide_tags_sales_grid', 'hide_suppliers_sales_grid', 'hide_favorites_sales_grid',
            'number_of_decimals_displayed_on_sales_interface', 'do_not_allow_edit_of_overall_subtotal',
            'disable_supplier_selection_on_sales_interface', 'amount_of_cash_to_be_left_in_drawer_at_closing',
            'cash_alert_high', 'cash_alert_low',
            // Items module
            'number_of_items_per_page', 'items_per_search_suggestions', 'number_of_items_in_grid',
            'default_reorder_level_when_creating_items', 'default_days_to_expire_when_creating_items',
            'default_new_items_to_service', 'highlight_low_inventory_items_in_items_module',
            'limit_manual_price_adj', 'max_discount_percent', 'enable_markup_calculator',
            'enable_margin_calculator', 'verify_age_for_products', 'default_age_to_verify',
            'strict_age_format_check', 'hide_supplier_in_item_search_result', 'hide_supplier_from_item_popup',
            'easy_item_clone_button', 'add_ck_editor_to_item',
            // Price Tiers config
            'override_tier_name', 'hide_tier_on_receipt',
            'default_tier_percent_type_for_excel_import', 'default_tier_fixed_type_for_excel_import',
            'round_tier_prices_to_2_decimals',
            // Ecommerce
            'ecommerce_platform', 'sku_sync_field', 'do_not_upload_images_to_ecommerce',
            'ecommerce_only_sync_completed_orders', 'import_ecommerce_orders_suspended',
            'new_items_are_ecommerce_by_default', 'use_main_image_as_default_image_in_e_commerce',
            'ecom_store_location', 'online_price_tier', 'ecommerce_cron_sync_operations',
            'woocommerce_url', 'woocommerce_consumer_key', 'woocommerce_consumer_secret',
            'shopify_public', 'shopify_private', 'shopify_store_url',
            // Application Settings
            'store_closing_time', 'payvantage', 'offline_mode', 'auto_sync_offline_sales',
            'offline_mode_sync_period', 'dark_mode', 'only_allow_current_location_customers',
            'default_new_customer_to_current_location', 'force_https', 'hide_expire_dashboard',
            'do_not_delete_saved_card_after_failure', 'do_not_force_http', 'test_mode',
            'disable_test_mode', 'hide_item_descriptions_in_reports', 'enable_sounds',
            'show_language_switcher', 'show_clock_on_header', 'legacy_detailed_report_export',
            'overwrite_existing_items_on_excel_import', 'report_sort_order', 'speed_up_search_queries',
            'customer_allow_partial_match', 'enable_quick_edit', 'enable_quick_expense',
            'enhanced_search_method', 'include_child_categories_when_searching_or_reporting',
            'show_full_category_path', 'spreadsheet_format', 'mailing_labels_type',
            'phppos_session_expiration', 'always_minimize_menu', 'item_lookup_order',
            'allow_scan_of_customer_into_item_field', 'send_sms_via_whatsapp',
            'enable_quick_customers', 'enable_quick_suppliers', 'enable_quick_items',
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
        $locations      = \App\Models\PhpposLocation::where('deleted', 0)->get();
        $price_tiers    = PhpposPriceTier::where('deleted', 0)->orderBy('sort_order')->orderBy('id')->get();
        $ecommerce_locations = \App\Models\PhpposAppConfig::where('key', 'ecommerce_location')->pluck('value', 'value')->toArray();
        $taxClasses = PhpposTaxClass::query()
            ->where('deleted', 0)
            ->with(['taxes' => function ($query) {
                $query->orderBy('order')->orderBy('id');
            }])
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        return view('config.index', compact('values', 'exchange_rates', 'currency_denoms', 'locations', 'price_tiers', 'ecommerce_locations', 'taxClasses'));
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
            // tier fields
            'tiers_to_edit',
            'tiers_to_delete',
            // ecommerce
            'ecommerce_locations',
            // tax class management
            'tax_classes',
            'taxes',
            'taxes_to_delete',
            'tax_classes_to_delete',
        ]);
        
        // Handle checkboxes (convert missing values to 0)
        $checkboxes = [
            'print_after_sale', 'print_after_receiving', 'automatically_email_receipt', 'hide_signature',
            'flat_discounts_discount_tax', 'prices_include_tax', 'charge_tax_on_recv',
            'enable_customer_loyalty_system', 'customers_store_accounts',
            'suppliers_store_accounts', 'calculate_average_cost_price_from_receivings',
            'hide_dashboard_statistics', 'show_language_switcher', 'show_clock_on_header',
            'speed_up_search_queries', 'enable_sounds',
            'show_barcode_company_name', 'hide_barcode_on_barcode_labels',
            'disable_price_rules_dialog',
            'flat_discounts_discount_tax', 'prices_include_tax', 'charge_tax_on_recv',
            // Sales module checkboxes
            'hide_supplier_on_sales_interface', 'hide_supplier_on_recv_interface', 'allow_drag_drop_sale',
            'allow_drag_drop_recv', 'disable_discounts_percentage_per_line_item', 'disabled_fixed_discounts',
            'disable_discount_by_percentage', 'disable_sale_cloning', 'always_put_last_added_item_on_top_of_cart',
            'disable_recv_cloning', 'scan_and_set_sales', 'scan_and_set_recv',
            'enable_tips', 'tip_preset_zero', 'auto_focus_on_item_after_sale_and_receiving',
            'capture_internal_notes_during_sale', 'capture_sig_for_all_payments',
            'hide_customer_recent_sales', 'enable_customer_quick_add', 'enable_supplier_quick_add',
            'collapse_sales_ui_by_default', 'collapse_recv_ui_by_default', 'disable_confirmation_sale',
            'disable_confirm_recv', 'disable_quick_complete_sale', 'calculate_average_cost_price_from_receivings',
            'update_cost_price_on_transfer', 'require_supplier_for_recv', 'track_shipping_cost_recv',
            'hide_suspended_recv_in_reports', 'do_not_show_closing', 'hide_available_giftcards',
            'show_giftcards_even_if_0_balance', 'disable_giftcard_detection', 'always_show_item_grid',
            'hide_images_in_grid', 'quick_variation_grid', 'hide_out_of_stock_grid',
            'require_customer_for_sale', 'select_sales_person_during_sale',
            'disable_sale_notifications', 'confirm_error_adding_item', 'change_sale_date_for_new_sale',
            'do_not_group_same_items', 'do_not_allow_below_cost', 'do_not_allow_out_of_stock_items_to_be_sold',
            'do_not_allow_items_to_go_out_of_stock_when_transfering',
            'do_not_allow_item_with_variations_to_be_sold_without_selecting_variation',
            'edit_item_price_if_zero_after_adding', 'remind_customer_facing_display',
            'do_not_allow_sales_with_zero_value', 'prompt_amount_for_cash_sale', 'show_qr_code_for_sale',
            'disable_verification_for_qr_codes', 'hide_categories_sales_grid',
            'hide_tags_sales_grid', 'hide_suppliers_sales_grid', 'hide_favorites_sales_grid',
            'do_not_allow_edit_of_overall_subtotal', 'disable_supplier_selection_on_sales_interface',
            // Items module checkboxes
            'default_new_items_to_service', 'highlight_low_inventory_items_in_items_module',
            'limit_manual_price_adj', 'enable_markup_calculator', 'enable_margin_calculator',
            'verify_age_for_products', 'strict_age_format_check', 'hide_supplier_in_item_search_result',
            'hide_supplier_from_item_popup', 'easy_item_clone_button', 'add_ck_editor_to_item',
            // Price tier checkboxes
            'hide_tier_on_receipt', 'round_tier_prices_to_2_decimals',
            // Ecommerce checkboxes
            'do_not_upload_images_to_ecommerce', 'ecommerce_only_sync_completed_orders',
            'import_ecommerce_orders_suspended', 'new_items_are_ecommerce_by_default',
            'use_main_image_as_default_image_in_e_commerce',
            // Application Settings checkboxes
            'payvantage', 'offline_mode', 'auto_sync_offline_sales', 'dark_mode',
            'only_allow_current_location_customers', 'default_new_customer_to_current_location',
            'force_https', 'hide_expire_dashboard', 'do_not_delete_saved_card_after_failure',
            'do_not_force_http', 'test_mode', 'disable_test_mode',
            'hide_item_descriptions_in_reports', 'legacy_detailed_report_export',
            'overwrite_existing_items_on_excel_import', 'customer_allow_partial_match',
            'enable_quick_edit', 'enable_quick_expense', 'enhanced_search_method',
            'include_child_categories_when_searching_or_reporting', 'show_full_category_path',
            'always_minimize_menu', 'allow_scan_of_customer_into_item_field',
            'send_sms_via_whatsapp', 'enable_quick_customers', 'enable_quick_suppliers',
            'enable_quick_items',
        ];

        foreach ($checkboxes as $checkbox) {
            $data[$checkbox] = $request->has($checkbox) ? '1' : '0';
        }

        $taxClassIdInput = $request->input('tax_class_id');
        $taxClassIdMap = [];

        \DB::transaction(function () use ($request, &$taxClassIdMap): void {
            $taxClassesToDelete = (array) $request->input('tax_classes_to_delete', []);
            foreach ($taxClassesToDelete as $taxClassId) {
                $taxClassId = is_numeric($taxClassId) ? (int) $taxClassId : null;
                if (! $taxClassId) {
                    continue;
                }

                PhpposTaxClass::query()
                    ->whereKey($taxClassId)
                    ->update(['deleted' => 1]);

                PhpposTaxClassTax::query()
                    ->where('tax_class_id', $taxClassId)
                    ->delete();
            }

            $taxesToDelete = (array) $request->input('taxes_to_delete', []);
            if ($taxesToDelete !== []) {
                $taxIds = array_values(array_filter(
                    array_map(static fn ($id): int => (int) $id, $taxesToDelete),
                    static fn (int $id): bool => $id > 0
                ));
                if ($taxIds !== []) {
                    PhpposTaxClassTax::query()->whereIn('id', $taxIds)->delete();
                }
            }

            $taxClassesToSave = (array) $request->input('tax_classes', []);
            $taxesToSave = (array) $request->input('taxes', []);

            $taxClassOrder = 0;
            foreach ($taxClassesToSave as $taxClassId => $taxClassData) {
                $name = trim((string) ($taxClassData['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $taxClassOrder++;
                if (is_numeric($taxClassId)) {
                    $taxClass = PhpposTaxClass::query()->firstOrNew(['id' => (int) $taxClassId]);
                } else {
                    $taxClass = new PhpposTaxClass();
                }

                $taxClass->name = $name;
                $taxClass->deleted = 0;
                $taxClass->order = $taxClassOrder;
                $taxClass->save();

                if (! is_numeric($taxClassId)) {
                    $taxClassIdMap[(string) $taxClassId] = $taxClass->id;
                }

                $taxRows = (array) ($taxesToSave[$taxClassId] ?? []);
                $taxNames = (array) ($taxRows['name'] ?? []);
                $taxPercents = (array) ($taxRows['percent'] ?? []);
                $taxCumulatives = (array) ($taxRows['cumulative'] ?? []);
                $taxIds = (array) ($taxRows['tax_class_tax_id'] ?? []);

                $taxOrder = 0;
                foreach ($taxNames as $rateIndex => $taxName) {
                    $taxName = trim((string) $taxName);
                    $taxPercent = trim((string) ($taxPercents[$rateIndex] ?? ''));
                    if ($taxName === '' || $taxPercent === '') {
                        continue;
                    }

                    $taxOrder++;
                    $taxId = $taxIds[$rateIndex] ?? null;
                    $taxRow = is_numeric($taxId)
                        ? PhpposTaxClassTax::query()->firstOrNew(['id' => (int) $taxId])
                        : new PhpposTaxClassTax();

                    $taxRow->tax_class_id = $taxClass->id;
                    $taxRow->name = $taxName;
                    $taxRow->percent = $taxPercent;
                    $taxRow->cumulative = ! empty($taxCumulatives[$rateIndex]);
                    $taxRow->order = $taxOrder;
                    $taxRow->save();
                }
            }
        });

        if ($taxClassIdInput !== null && $taxClassIdInput !== '') {
            if (isset($taxClassIdMap[$taxClassIdInput])) {
                $data['tax_class_id'] = (string) $taxClassIdMap[$taxClassIdInput];
            } elseif (is_numeric($taxClassIdInput)) {
                $data['tax_class_id'] = (string) $taxClassIdInput;
            } else {
                $data['tax_class_id'] = '';
            }
        } else {
            $data['tax_class_id'] = '';
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

        // Handle Price Tier deletions
        $tiersToDelete = (array) $request->input('tiers_to_delete', []);
        foreach ($tiersToDelete as $tierId) {
            $tierId = (int) $tierId;
            if ($tierId > 0) {
                PhpposPriceTier::where('id', $tierId)->update(['deleted' => 1]);
            }
        }

        // Handle Price Tier save/create
        $tiersToEdit = (array) $request->input('tiers_to_edit', []);
        $sortOrder = 0;
        foreach ($tiersToEdit as $tierId => $tierData) {
            $name = trim((string) ($tierData['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $attrs = [
                'name'                        => $name,
                'default_percent_off'         => is_numeric($tierData['default_percent_off'] ?? '') ? (float) $tierData['default_percent_off'] : null,
                'default_cost_plus_percent'   => is_numeric($tierData['default_cost_plus_percent'] ?? '') ? (float) $tierData['default_cost_plus_percent'] : null,
                'default_cost_plus_fixed_amount' => is_numeric($tierData['default_cost_plus_fixed_amount'] ?? '') ? (float) $tierData['default_cost_plus_fixed_amount'] : null,
                'sort_order'                  => $sortOrder++,
                'deleted'                     => 0,
            ];
            $isNew = !is_numeric($tierId) || (int) $tierId <= 0;
            if ($isNew) {
                PhpposPriceTier::create($attrs);
            } else {
                PhpposPriceTier::where('id', (int) $tierId)->update($attrs);
            }
        }

        return back()->with('status', 'Store configuration updated successfully.');
    }
}
