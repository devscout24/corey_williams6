<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesService
{
    public function __construct(private readonly LocationContextService $locationContextService) {}

    /**
     * @param  array<int, array{item_id:int, quantity:float, unit_price:float, discount:float, variation_id?:int|null}>  $items
     * @param  array<int, array{type:string, amount:float}>  $payments
     * @param  array<int, array{type:'kit', item_kit_id:int, name:string, quantity:float, unit_price:float, discount:float}>  $kitEntries
     */
    public function createSaleFromCart(
        int $locationId,
        int $employeeId,
        array $items,
        array $payments,
        ?string $customerName = null,
        ?string $comment = null,
        ?int $soldByEmployeeId = null,
        ?int $registerId = null,
        array $kitEntries = [],
    ): int {
        $resolvedLocationId = $this->locationContextService->resolveLocationId($locationId);

        return DB::transaction(function () use ($resolvedLocationId, $employeeId, $items, $payments, $customerName, $comment, $soldByEmployeeId, $registerId, $kitEntries): int {
            if (empty($items) && empty($kitEntries)) {
                throw new RuntimeException('At least one sale line is required.');
            }

            $normalized = collect($items)
                ->map(static fn (array $line): array => [
                    'item_id' => (int) $line['item_id'],
                    'quantity' => (float) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'discount' => (float) ($line['discount'] ?? 0),
                    'variation_id' => isset($line['variation_id']) ? (int) $line['variation_id'] : null,
                ])
                ->filter(static fn (array $line): bool => $line['quantity'] > 0)
                ->values();

            $itemRows = DB::table('phppos_items')
                ->whereIn('item_id', $normalized->pluck('item_id')->all())
                ->get()
                ->keyBy('item_id');

            $subtotal = 0.0;
            $lineRows = [];
            $lineEntries = [];

            // Fetch config once before processing items
            $config = DB::table('phppos_app_config')->get()->keyBy('key');
            $defaultTaxClassIdRaw = $config->get('tax_class_id')->value ?? null;
            $defaultTaxClassId = is_numeric($defaultTaxClassIdRaw) ? (int) $defaultTaxClassIdRaw : 0;
            $taxClassIds = $itemRows->pluck('tax_class_id')
                ->filter(static fn ($id): bool => is_numeric($id) && (int) $id > 0)
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();
            if ($defaultTaxClassId > 0) {
                $taxClassIds[] = $defaultTaxClassId;
            }
            $taxClassIds = array_values(array_unique($taxClassIds));
            $taxClassTaxes = $taxClassIds === []
                ? collect()
                : DB::table('phppos_tax_classes_taxes')
                    ->whereIn('tax_class_id', $taxClassIds)
                    ->orderBy('order')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('tax_class_id');

            $totalVat = 0.0;

            // ---- Process regular items (existing logic) ----
            foreach ($normalized as $line) {
                $itemId = $line['item_id'];
                $qty = $line['quantity'];
                $unitPrice = $line['unit_price'];
                $discount = $line['discount'];
                $item = $itemRows->get($itemId);

                if (! $item) {
                    throw new RuntimeException('Item '.$itemId.' not found.');
                }

                $stock = DB::table('phppos_location_items')
                    ->where('location_id', $resolvedLocationId)
                    ->where('item_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                $stockQty = $stock ? (float) $stock->quantity : 0.0;
                if (! $stock) {
                    DB::table('phppos_location_items')->insert([
                        'location_id' => $resolvedLocationId,
                        'item_id' => $itemId,
                        'quantity' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $lineTotal = $unitPrice * $qty * (1 - $discount / 100);
                $subtotal += $lineTotal;

                DB::table('phppos_location_items')
                    ->where('location_id', $resolvedLocationId)
                    ->where('item_id', $itemId)
                    ->update([
                        'quantity' => $stockQty - $qty,
                        'updated_at' => now(),
                    ]);

                $lineRows[] = [
                    'item_id' => $itemId,
                    'quantity_purchased' => $qty,
                    'item_unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];

                // Calculate commission for this line item
                $commission = $this->calculateCommission($item, $soldByEmployeeId ?? $employeeId, $lineTotal, $qty, $config);

                $lineTaxClassId = is_numeric($item->tax_class_id ?? null) ? (int) $item->tax_class_id : 0;
                if ($lineTaxClassId <= 0 && $defaultTaxClassId > 0) {
                    $lineTaxClassId = $defaultTaxClassId;
                }

                $taxRows = [];
                $lineVat = 0.0;
                if ($lineTaxClassId > 0 && $taxClassTaxes->has($lineTaxClassId)) {
                    $rates = $taxClassTaxes->get($lineTaxClassId);

                    $baseCumulative = $lineTotal;
                    $totalTax = 0.0;
                    foreach ($rates as $rate) {
                        $rateDecimal = (float) $rate->percent / 100;
                        $lineTaxAmount = $baseCumulative * $rateDecimal;
                        $totalTax += $lineTaxAmount;
                        if ((bool) $rate->cumulative) {
                            $baseCumulative += $lineTaxAmount;
                        }
                    }
                    $lineTotalWithTax = $lineTotal + $totalTax;

                    if ($lineTotal > 0) {
                        $effectiveTaxRate = ($lineTotalWithTax - $lineTotal) / $lineTotal;
                        if ($effectiveTaxRate > 0) {
                            $lineVat = $lineTotal * $effectiveTaxRate;
                        }
                    }

                    foreach ($rates as $rate) {
                        $taxRows[] = [
                            'name' => $rate->name,
                            'percent' => $rate->percent,
                            'cumulative' => (bool) $rate->cumulative,
                        ];
                    }
                }

                $totalVat += $lineVat;

                $lineEntries[] = [
                    'item_id' => $itemId,
                    'variation_id' => $line['variation_id'],
                    'quantity_purchased' => $qty,
                    'item_unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'commission' => $commission,
                    'vat' => $lineVat,
                    'tax_rows' => $taxRows,
                ];

                DB::table('phppos_inventory_movements')->insert([
                    'movement_type' => 'sale',
                    'item_id' => $itemId,
                    'from_location_id' => $resolvedLocationId,
                    'to_location_id' => null,
                    'quantity' => $qty,
                    'reference_id' => null,
                    'reference_type' => 'sale',
                    'created_by_person_id' => $employeeId,
                    'notes' => 'Sale stock out',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ---- Process kit entries (explode for inventory, use kit price for subtotal) ----
            $kitSaleLines = [];

            foreach ($kitEntries as $entry) {
                $kitQty = (float) $entry['quantity'];
                if ($kitQty <= 0) {
                    continue;
                }

                $kitLineTotal = (float) $entry['unit_price'] * $kitQty * (1 - ((float) ($entry['discount'] ?? 0) / 100));
                $subtotal += $kitLineTotal;

                $kitSaleLines[] = [
                    'item_kit_id' => (int) $entry['item_kit_id'],
                    'quantity_purchased' => $kitQty,
                    'item_kit_unit_price' => (float) $entry['unit_price'],
                    'line_total' => $kitLineTotal,
                ];

                // Load kit with items for inventory explosion
                $kit = DB::table('phppos_item_kits')->where('id', (int) $entry['item_kit_id'])->first();
                if (! $kit) {
                    continue;
                }

                // Decrement the kit's own stock (default_quantity)
                DB::table('phppos_item_kits')
                    ->where('id', (int) $entry['item_kit_id'])
                    ->decrement('default_quantity', $kitQty);

                $components = $this->explodeKitComponents((int) $kit->id, $kitQty);
                foreach ($components as $comp) {
                    $compItemId = $comp['item_id'];
                    $compQty = $comp['quantity'];

                    $compStock = DB::table('phppos_location_items')
                        ->where('location_id', $resolvedLocationId)
                        ->where('item_id', $compItemId)
                        ->lockForUpdate()
                        ->first();

                    $compStockQty = $compStock ? (float) $compStock->quantity : 0.0;
                    if (! $compStock) {
                        DB::table('phppos_location_items')->insert([
                            'location_id' => $resolvedLocationId,
                            'item_id' => $compItemId,
                            'quantity' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('phppos_location_items')
                        ->where('location_id', $resolvedLocationId)
                        ->where('item_id', $compItemId)
                        ->update([
                            'quantity' => $compStockQty - $compQty,
                            'updated_at' => now(),
                        ]);

                    DB::table('phppos_inventory_movements')->insert([
                        'movement_type' => 'sale',
                        'item_id' => $compItemId,
                        'from_location_id' => $resolvedLocationId,
                        'to_location_id' => null,
                        'quantity' => $compQty,
                        'reference_id' => null,
                        'reference_type' => 'kit_component_sale',
                        'created_by_person_id' => $employeeId,
                        'notes' => 'Kit component sale (kit #'.$kit->id.')',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // ---- Build payment rows ----
            $paymentRows = collect($payments)
                ->map(static fn (array $payment): array => [
                    'payment_type' => (string) $payment['type'],
                    'payment_amount' => (float) $payment['amount'],
                ])
                ->filter(static fn (array $payment): bool => $payment['payment_amount'] > 0)
                ->values();

            if ($paymentRows->isEmpty()) {
                $paymentRows = collect([
                    ['payment_type' => 'Cash', 'payment_amount' => $subtotal],
                ]);
            }

            $amountTendered = (float) $paymentRows->sum('payment_amount');
            $changeDue = max(0, $amountTendered - $subtotal);

            $saleId = DB::table('phppos_sales')->insertGetId([
                'location_id' => $resolvedLocationId,
                'employee_id' => $employeeId,
                'register_id' => $registerId,
                'sale_type' => 'sale',
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'vat' => round($totalVat, 10),
                'amount_tendered' => $amountTendered,
                'change_due' => $changeDue,
                'closed_at' => now(),
                'customer_name' => $customerName,
                'comment' => $comment,
                'sold_by_employee_id' => $soldByEmployeeId ?? $employeeId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'sale_id');

            // ---- Write phppos_sales_items for regular items ----
            if (! empty($lineEntries)) {
                $now = now();
                foreach ($lineEntries as $entry) {
                    $saleItemId = DB::table('phppos_sales_items')->insertGetId([
                        'sale_id' => $saleId,
                        'item_id' => $entry['item_id'],
                        'item_variation_id' => $entry['variation_id'],
                        'quantity_purchased' => $entry['quantity_purchased'],
                        'item_unit_price' => $entry['item_unit_price'],
                        'line_total' => $entry['line_total'],
                        'commission' => $entry['commission'],
                        'vat' => round($entry['vat'], 10),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if (! empty($entry['tax_rows'])) {
                        $taxInsert = [];
                        foreach ($entry['tax_rows'] as $taxRow) {
                            $taxInsert[] = [
                                'sale_id' => $saleId,
                                'sale_item_id' => $saleItemId,
                                'item_id' => $entry['item_id'],
                                'name' => $taxRow['name'],
                                'percent' => $taxRow['percent'],
                                'cumulative' => $taxRow['cumulative'],
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                        DB::table('phppos_sales_items_taxes')->insert($taxInsert);
                    }
                }
            }

            // Update inventory movements for regular items with the sale ID
            foreach ($lineRows as $lineRow) {
                DB::table('phppos_inventory_movements')
                    ->where('item_id', $lineRow['item_id'])
                    ->where('reference_type', 'sale')
                    ->whereNull('reference_id')
                    ->limit(1)
                    ->update(['reference_id' => $saleId]);
            }

            // Update inventory movements for kit component items with the sale ID
            foreach ($kitEntries as $entry) {
                $kitId = (int) $entry['item_kit_id'];
                DB::table('phppos_inventory_movements')
                    ->where('reference_type', 'kit_component_sale')
                    ->whereNull('reference_id')
                    ->where('notes', 'Kit component sale (kit #'.$kitId.')')
                    ->limit(PHP_INT_MAX)
                    ->update(['reference_id' => $saleId]);
            }

            // ---- Insert payment records ----
            foreach ($paymentRows as $paymentRow) {
                DB::table('phppos_sales_payments')->insert([
                    'sale_id' => $saleId,
                    'payment_type' => $paymentRow['payment_type'],
                    'payment_amount' => $paymentRow['payment_amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ---- Update register log payments ----
            if ($registerId) {
                $openLog = DB::table('phppos_register_log')
                    ->where('register_id', $registerId)
                    ->whereNull('shift_end')
                    ->first();

                if ($openLog) {
                    foreach ($paymentRows as $paymentRow) {
                        $paymentType = $paymentRow['payment_type'];
                        $amount = $paymentRow['payment_amount'];

                        $logPayment = DB::table('phppos_register_log_payments')
                            ->where('register_log_id', $openLog->register_log_id)
                            ->where('payment_type', $paymentType)
                            ->first();

                        if ($logPayment) {
                            DB::table('phppos_register_log_payments')
                                ->where('id', $logPayment->id)
                                ->increment('payment_sales_amount', $amount);
                        } else {
                            DB::table('phppos_register_log_payments')->insert([
                                'register_log_id' => $openLog->register_log_id,
                                'payment_type' => $paymentType,
                                'open_amount' => 0,
                                'close_amount' => 0,
                                'payment_sales_amount' => $amount,
                                'total_payment_additions' => 0,
                                'total_payment_subtractions' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            // ---- Insert kit-level sale records for receipt/report display ----
            if (! empty($kitSaleLines)) {
                $now = now();
                foreach ($kitSaleLines as $kl) {
                    DB::table('phppos_sales_item_kits')->insert([
                        'sale_id' => $saleId,
                        'item_kit_id' => $kl['item_kit_id'],
                        'quantity_purchased' => $kl['quantity_purchased'],
                        'item_kit_unit_price' => $kl['item_kit_unit_price'],
                        'line_total' => $kl['line_total'],
                        'subtotal' => $kl['line_total'],
                        'total' => $kl['line_total'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return $saleId;
        });
    }

    /**
     * Recursively explode a kit into flat component items with adjusted quantities.
     *
     * @return array<int, array{item_id:int, quantity:float}>
     */
    private function explodeKitComponents(int $kitId, float $kitQty): array
    {
        $items = [];

        $kitItemRows = DB::table('phppos_item_kit_items')
            ->where('item_kit_id', $kitId)
            ->get();

        foreach ($kitItemRows as $row) {
            $itemId = (int) $row->item_id;
            $qty = (float) $row->quantity * $kitQty;
            $items[] = ['item_id' => $itemId, 'quantity' => $qty];
        }

        // Nested kits
        $nestedRows = DB::table('phppos_item_kit_item_kits')
            ->where('item_kit_id', $kitId)
            ->get();

        foreach ($nestedRows as $row) {
            $nestedKitQty = (float) $row->quantity * $kitQty;
            $child = $this->explodeKitComponents((int) $row->item_kit_item_kit, $nestedKitQty);
            $items = [...$items, ...$child];
        }

        return $items;
    }

    /**
     * Calculate commission for a sale line item
     */
    private function calculateCommission(
        $item,
        int $employeeId,
        float $lineTotal,
        float $quantity,
        $config
    ): float {
        $commission = 0;

        // Item-level fixed commission
        if (isset($item->commission_fixed) && $item->commission_fixed !== null) {
            $commission = $quantity * (float) $item->commission_fixed;
        }
        // Item-level percentage commission
        elseif (isset($item->commission_percent) && $item->commission_percent !== null) {
            $commission = $this->calculatePercentCommission(
                $lineTotal,
                $quantity,
                $item->cost_price,
                (float) $item->commission_percent,
                $config
            );
        }
        // Employee-level commission
        else {
            $employee = DB::table('phppos_employees')
                ->where('person_id', $employeeId)
                ->first();

            if ($employee && ($employee->commission_percent ?? 0) > 0) {
                $commission = $this->calculatePercentCommission(
                    $lineTotal,
                    $quantity,
                    $item->cost_price,
                    $employee->commission_percent,
                    $config
                );
            }
            // Default commission
            else {
                $defaultRate = (float) ($config->get('commission_default_rate')->value ?? 0);
                $commission = $this->calculatePercentCommission(
                    $lineTotal,
                    $quantity,
                    $item->cost_price,
                    $defaultRate,
                    $config
                );
            }
        }

        return $commission;
    }

    /**
     * Calculate percentage-based commission
     */
    private function calculatePercentCommission(
        float $lineTotal,
        float $quantity,
        float $costPrice,
        float $percent,
        $config
    ): float {
        $type = ($config->get('commission_percent_type')->value ?? 'selling_price') === 'profit'
            ? 'profit'
            : 'selling_price';

        if ($type === 'selling_price') {
            return $lineTotal * ($percent / 100);
        }

        $profit = $lineTotal - ($costPrice * $quantity);

        return $profit * ($percent / 100);
    }

    /**
     * @param  array<int, array{sale_item_id:int, quantity:float}>  $lines
     */
    public function returnSaleItems(int $saleId, int $employeeId, array $lines, ?string $reason = null): void
    {
        DB::transaction(function () use ($saleId, $employeeId, $lines, $reason): void {
            $sale = DB::table('phppos_sales')->where('sale_id', $saleId)->first();
            if (! $sale) {
                throw new RuntimeException('Sale not found.');
            }

            $locationId = (int) $sale->location_id;

            foreach ($lines as $line) {
                $saleItemId = (int) $line['sale_item_id'];
                $returnQty = (float) $line['quantity'];

                if ($returnQty <= 0) {
                    continue;
                }

                $saleItem = DB::table('phppos_sales_items')
                    ->where('id', $saleItemId)
                    ->where('sale_id', $saleId)
                    ->first();

                if (! $saleItem) {
                    throw new RuntimeException('Invalid sale line selected.');
                }

                $returnedQty = (float) DB::table('phppos_sales_item_returns')
                    ->where('sale_item_id', $saleItemId)
                    ->sum('quantity_returned');

                $maxReturnable = (float) $saleItem->quantity_purchased - $returnedQty;
                if ($returnQty > $maxReturnable + 0.000001) {
                    throw new RuntimeException('Return quantity exceeds remaining quantity for line '.$saleItemId.'.');
                }

                $stock = DB::table('phppos_location_items')
                    ->where('location_id', $locationId)
                    ->where('item_id', $saleItem->item_id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    DB::table('phppos_location_items')->insert([
                        'location_id' => $locationId,
                        'item_id' => $saleItem->item_id,
                        'quantity' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $current = 0.0;
                } else {
                    $current = (float) $stock->quantity;
                }

                DB::table('phppos_location_items')
                    ->where('location_id', $locationId)
                    ->where('item_id', $saleItem->item_id)
                    ->update([
                        'quantity' => $current + $returnQty,
                        'updated_at' => now(),
                    ]);

                DB::table('phppos_sales_item_returns')->insert([
                    'sale_id' => $saleId,
                    'sale_item_id' => $saleItemId,
                    'item_id' => $saleItem->item_id,
                    'location_id' => $locationId,
                    'quantity_returned' => $returnQty,
                    'employee_id' => $employeeId,
                    'reason' => $reason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('phppos_inventory_movements')->insert([
                    'movement_type' => 'receiving',
                    'item_id' => $saleItem->item_id,
                    'from_location_id' => null,
                    'to_location_id' => $locationId,
                    'quantity' => $returnQty,
                    'reference_id' => $saleId,
                    'reference_type' => 'sale_return',
                    'created_by_person_id' => $employeeId,
                    'notes' => 'Return against sale #'.$saleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
