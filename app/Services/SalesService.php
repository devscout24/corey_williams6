<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesService
{
    public function __construct(private readonly LocationContextService $locationContextService)
    {
    }

    /**
     * @param array<int, array{item_id:int, quantity:float, unit_price:float, discount:float}> $items
     * @param array<int, array{type:string, amount:float}> $payments
     */
    public function createSaleFromCart(
        int $locationId,
        int $employeeId,
        array $items,
        array $payments,
        ?string $customerName = null,
        ?string $comment = null,
        ?int $soldByEmployeeId = null,
    ): int {
        $resolvedLocationId = $this->locationContextService->resolveLocationId($locationId);

        return DB::transaction(function () use ($resolvedLocationId, $employeeId, $items, $payments, $customerName, $comment, $soldByEmployeeId): int {
            $normalized = collect($items)
                ->map(static fn (array $line): array => [
                    'item_id' => (int) $line['item_id'],
                    'quantity' => (float) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'discount' => (float) ($line['discount'] ?? 0),
                ])
                ->filter(static fn (array $line): bool => $line['quantity'] > 0)
                ->values();

            if ($normalized->isEmpty()) {
                throw new RuntimeException('At least one sale line is required.');
            }

            $itemRows = DB::table('phppos_items')
                ->whereIn('item_id', $normalized->pluck('item_id')->all())
                ->get()
                ->keyBy('item_id');

            $subtotal = 0.0;
            $lineRows = [];
            $salesItemRows = [];

            // Fetch config once before processing items
            $config = DB::table('phppos_app_config')->get()->keyBy('key');

            foreach ($normalized as $line) {
                $itemId = $line['item_id'];
                $qty = $line['quantity'];
                $unitPrice = $line['unit_price'];
                $discount = $line['discount'];
                $item = $itemRows->get($itemId);

                if (! $item) {
                    throw new RuntimeException('Item ' . $itemId . ' not found.');
                }

                $stock = DB::table('phppos_location_items')
                    ->where('location_id', $resolvedLocationId)
                    ->where('item_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || (float) $stock->quantity < $qty) {
                    throw new RuntimeException('Insufficient stock for item ' . $itemId . ' at location ' . $resolvedLocationId . '.');
                }

                $lineTotal = $unitPrice * $qty * (1 - $discount / 100);
                $subtotal += $lineTotal;

                DB::table('phppos_location_items')
                    ->where('location_id', $resolvedLocationId)
                    ->where('item_id', $itemId)
                    ->update([
                        'quantity' => (float) $stock->quantity - $qty,
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

                $salesItemRows[] = [
                    'item_id' => $itemId,
                    'quantity_purchased' => $qty,
                    'item_unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'commission' => $commission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Log inventory movement
                DB::table('phppos_inventory_movements')->insert([
                    'movement_type' => 'sale',
                    'item_id' => $itemId,
                    'from_location_id' => $resolvedLocationId,
                    'to_location_id' => null,
                    'quantity' => $qty,
                    'reference_id' => null, // Will be updated after sale is created
                    'reference_type' => 'sale',
                    'created_by_person_id' => $employeeId,
                    'notes' => 'Sale stock out',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
                'sale_type' => 'sale',
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'amount_tendered' => $amountTendered,
                'change_due' => $changeDue,
                'customer_name' => $customerName,
                'comment' => $comment,
                'sold_by_employee_id' => $soldByEmployeeId ?? $employeeId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'sale_id');

            if (! empty($salesItemRows)) {
                $now = now();
                foreach ($salesItemRows as &$row) {
                    $row['sale_id'] = $saleId;
                    $row['created_at'] = $row['created_at'] ?? $now;
                    $row['updated_at'] = $row['updated_at'] ?? $now;
                }
                unset($row);
                DB::table('phppos_sales_items')->insert($salesItemRows);
            }

            // Update inventory movements with the sale ID
            foreach ($lineRows as $lineRow) {
                DB::table('phppos_inventory_movements')
                    ->where('item_id', $lineRow['item_id'])
                    ->where('reference_type', 'sale')
                    ->whereNull('reference_id')
                    ->limit(1)
                    ->update(['reference_id' => $saleId]);
            }

            // Insert payment records
            foreach ($paymentRows as $paymentRow) {
                DB::table('phppos_sales_payments')->insert([
                    'sale_id' => $saleId,
                    'payment_type' => $paymentRow['payment_type'],
                    'payment_amount' => $paymentRow['payment_amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $saleId;
        });
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
     * @param array<int, array{sale_item_id:int, quantity:float}> $lines
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
