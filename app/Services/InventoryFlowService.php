<?php

namespace App\Services;

use App\Models\PhpposItem;
use App\Models\PhpposReceiving;
use App\Models\PhpposReceivingItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryFlowService
{
    public function receive(int $locationId, int $itemId, float $quantity, ?int $employeePersonId = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($locationId, $itemId, $quantity, $employeePersonId, $notes): void {
            $this->adjustQuantity($locationId, $itemId, $quantity);

            DB::table('phppos_inventory_movements')->insert([
                'movement_type' => 'receiving',
                'item_id' => $itemId,
                'to_location_id' => $locationId,
                'quantity' => $quantity,
                'created_by_person_id' => $employeePersonId,
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function returnFromInventory(int $locationId, int $itemId, float $quantity, ?int $employeePersonId = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($locationId, $itemId, $quantity, $employeePersonId, $notes): void {
            $this->adjustQuantity($locationId, $itemId, -$quantity);

            DB::table('phppos_inventory_movements')->insert([
                'movement_type' => 'return',
                'item_id' => $itemId,
                'from_location_id' => $locationId,
                'quantity' => $quantity,
                'created_by_person_id' => $employeePersonId,
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Transfer out closes immediately and auto-creates a closed transfer in.
     *
     * @param array<int, array{item_id:int, quantity:float}> $lines
     */
    public function transferOutAndAutoIn(int $fromLocationId, int $toLocationId, array $lines, ?int $employeePersonId = null, ?string $notes = null): array
    {
        if ($fromLocationId === $toLocationId) {
            throw new RuntimeException('From and To location must be different.');
        }

        return DB::transaction(function () use ($fromLocationId, $toLocationId, $lines, $employeePersonId, $notes): array {
            $transferOutId = DB::table('phppos_transfers')->insertGetId([
                'transfer_type' => 'out',
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'status' => 'closed',
                'created_by_person_id' => $employeePersonId,
                'closed_at' => now(),
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $transferInId = DB::table('phppos_transfers')->insertGetId([
                'transfer_type' => 'in',
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'parent_transfer_id' => $transferOutId,
                'auto_generated' => true,
                'status' => 'closed',
                'created_by_person_id' => $employeePersonId,
                'closed_at' => now(),
                'notes' => 'Auto-created from transfer out #'.$transferOutId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lines as $line) {
                $itemId = (int) $line['item_id'];
                $qty = (float) $line['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                // transfer out: subtract from source
                $this->adjustQuantity($fromLocationId, $itemId, -$qty);

                // transfer in: add to destination (automatic)
                $this->adjustQuantity($toLocationId, $itemId, $qty);

                DB::table('phppos_transfer_items')->insert([
                    [
                        'transfer_id' => $transferOutId,
                        'item_id' => $itemId,
                        'quantity' => $qty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'transfer_id' => $transferInId,
                        'item_id' => $itemId,
                        'quantity' => $qty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);

                DB::table('phppos_inventory_movements')->insert([
                    [
                        'movement_type' => 'transfer_out',
                        'item_id' => $itemId,
                        'from_location_id' => $fromLocationId,
                        'to_location_id' => $toLocationId,
                        'quantity' => $qty,
                        'reference_id' => $transferOutId,
                        'reference_type' => 'transfer',
                        'created_by_person_id' => $employeePersonId,
                        'notes' => $notes,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'movement_type' => 'transfer_in',
                        'item_id' => $itemId,
                        'from_location_id' => $fromLocationId,
                        'to_location_id' => $toLocationId,
                        'quantity' => $qty,
                        'reference_id' => $transferInId,
                        'reference_type' => 'transfer',
                        'created_by_person_id' => $employeePersonId,
                        'notes' => 'Auto transfer-in',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }

            return [
                'transfer_out_id' => $transferOutId,
                'transfer_in_id' => $transferInId,
            ];
        });
    }

    /**
     * @param array<int, array{item_id:int, quantity:float}> $lines
     */
    public function importTransferIn(
        int $fromLocationId,
        int $toLocationId,
        array $lines,
        string $externalSource,
        string $externalTransferId,
        ?string $notes = null,
        ?string $createdAt = null,
        ?int $employeePersonId = null
    ): array {
        if ($fromLocationId === $toLocationId) {
            throw new RuntimeException('From and To location must be different.');
        }

        return DB::transaction(function () use ($fromLocationId, $toLocationId, $lines, $externalSource, $externalTransferId, $notes, $createdAt): array {
            $existing = DB::table('phppos_transfers')
                ->where('transfer_type', 'in')
                ->where('external_source', $externalSource)
                ->where('external_transfer_id', $externalTransferId)
                ->first();

            if ($existing) {
                return [
                    'transfer_in_id' => $existing->id,
                    'already_imported' => true,
                ];
            }

            $timestamp = $createdAt ? Carbon::parse($createdAt) : now();

            $transferInId = DB::table('phppos_transfers')->insertGetId([
                'transfer_type' => 'in',
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'parent_transfer_id' => null,
                'auto_generated' => false,
                'status' => 'closed',
                'created_by_person_id' => null,
                'closed_at' => $timestamp,
                'notes' => $notes,
                'external_source' => $externalSource,
                'external_transfer_id' => $externalTransferId,
                'created_at' => $timestamp,
                'updated_at' => now(),
            ]);

            $receivingEmployeeId = $employeePersonId ?? $this->resolveSyncEmployeeId();
            $receivingComment = 'Synced Transfer Out '.$externalTransferId.' from '.$externalSource;
            if ($notes) {
                $receivingComment .= ' - '.$notes;
            }

            $subtotal = 0.0;
            $totalQty = 0.0;
            foreach ($lines as $line) {
                $itemId = (int) $line['item_id'];
                $qty = (float) $line['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $itemCost = (float) (PhpposItem::find($itemId)?->cost_price ?? 0);
                $subtotal += $itemCost * $qty;
                $totalQty += $qty;
            }

            $receiving = PhpposReceiving::create([
                'receiving_time' => $timestamp,
                'supplier_id' => null,
                'employee_id' => $receivingEmployeeId,
                'comment' => $receivingComment,
                'location_id' => $toLocationId,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'total_quantity_purchased' => $totalQty,
                'total_quantity_received' => $totalQty,
                'mode' => 'receive',
            ]);

            $lineNumber = 0;
            foreach ($lines as $line) {
                $itemId = (int) $line['item_id'];
                $qty = (float) $line['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $itemCost = (float) (PhpposItem::find($itemId)?->cost_price ?? 0);

                PhpposReceivingItem::create([
                    'receiving_id' => $receiving->receiving_id,
                    'item_id' => $itemId,
                    'line' => $lineNumber,
                    'quantity_purchased' => $qty,
                    'quantity_received' => $qty,
                    'item_cost_price' => $itemCost,
                    'item_unit_price' => $itemCost,
                    'discount_percent' => 0,
                    'subtotal' => $itemCost * $qty,
                    'total' => $itemCost * $qty,
                ]);
                $lineNumber++;

                $this->adjustQuantity($toLocationId, $itemId, $qty);

                DB::table('phppos_transfer_items')->insert([
                    'transfer_id' => $transferInId,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'created_at' => $timestamp,
                    'updated_at' => now(),
                ]);

                DB::table('phppos_inventory_movements')->insert([
                    'movement_type' => 'transfer_in',
                    'item_id' => $itemId,
                    'from_location_id' => $fromLocationId,
                    'to_location_id' => $toLocationId,
                    'quantity' => $qty,
                    'reference_id' => $transferInId,
                    'reference_type' => 'transfer',
                    'created_by_person_id' => null,
                    'notes' => $notes,
                    'created_at' => $timestamp,
                    'updated_at' => now(),
                ]);
            }

            return [
                'transfer_in_id' => $transferInId,
                'already_imported' => false,
                'receiving_id' => $receiving->receiving_id,
            ];
        });
    }

    private function resolveSyncEmployeeId(): int
    {
        $employeeId = DB::table('phppos_employees')->value('person_id');
        if (! $employeeId) {
            throw new RuntimeException('No employees available for sync receiving.');
        }

        return (int) $employeeId;
    }

    private function adjustQuantity(int $locationId, int $itemId, float $delta): void
    {
        $row = DB::table('phppos_location_items')
            ->where('location_id', $locationId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            DB::table('phppos_location_items')->insert([
                'location_id' => $locationId,
                'item_id' => $itemId,
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $current = 0.0;
        } else {
            $current = (float) $row->quantity;
        }

        $newQty = $current + $delta;
        if ($newQty < 0) {
            throw new RuntimeException('Insufficient inventory at source location for item '.$itemId.'.');
        }

        DB::table('phppos_location_items')
            ->where('location_id', $locationId)
            ->where('item_id', $itemId)
            ->update([
                'quantity' => $newQty,
                'updated_at' => now(),
            ]);
    }
}
