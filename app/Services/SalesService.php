<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesService
{
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
    ): int {
        return DB::transaction(function () use ($locationId, $employeeId, $items, $payments, $customerName, $comment): int {
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
                    ->where('location_id', $locationId)
                    ->where('item_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || (float) $stock->quantity < $qty) {
                    throw new RuntimeException('Insufficient stock for item ' . $itemId . ' at location ' . $locationId . '.');
                }

                $lineTotal = $unitPrice * $qty * (1 - $discount / 100);
                $subtotal += $lineTotal;

                DB::table('phppos_location_items')
                    ->where('location_id', $locationId)
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
                'location_id' => $locationId,
                'employee_id' => $employeeId,
                'sale_type' => 'sale',
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'amount_tendered' => $amountTendered,
                'change_due' => $changeDue,
                'customer_name' => $customerName,
                'comment' => $comment,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'sale_id');

            foreach ($lineRows as $lineRow) {
                DB::table('phppos_sales_items')->insert([
                    'sale_id' => $saleId,
                    'item_id' => $lineRow['item_id'],
                    'quantity_purchased' => $lineRow['quantity_purchased'],
                    'item_unit_price' => $lineRow['item_unit_price'],
                    'line_total' => $lineRow['line_total'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('phppos_inventory_movements')->insert([
                    'movement_type' => 'sale',
                    'item_id' => $lineRow['item_id'],
                    'from_location_id' => $locationId,
                    'to_location_id' => null,
                    'quantity' => $lineRow['quantity_purchased'],
                    'reference_id' => $saleId,
                    'reference_type' => 'sale',
                    'created_by_person_id' => $employeeId,
                    'notes' => 'Sale stock out',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
