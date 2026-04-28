<?php

namespace Database\Seeders;

use App\Services\SalesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        if (Schema::hasTable('phppos_messages') && Schema::hasTable('phppos_message_receiver')) {
            $hasAnyMessages = DB::table('phppos_messages')->exists();

            if (! $hasAnyMessages) {
                $messageId = DB::table('phppos_messages')->insertGetId([
                    'sender_id' => 1,
                    'subject' => 'Welcome',
                    'message' => 'Your POS demo data was seeded successfully.',
                    'sent_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('phppos_message_receiver')->updateOrInsert(
                    ['message_id' => $messageId, 'receiver_id' => 1],
                    [
                        'is_read' => 0,
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        if (
            Schema::hasTable('phppos_sales') &&
            Schema::hasTable('phppos_sales_items') &&
            Schema::hasTable('phppos_sales_payments') &&
            Schema::hasTable('phppos_items')
        ) {
            $hasAnySales = DB::table('phppos_sales')->exists();

            if (! $hasAnySales) {
                // Create one small sale using the service so inventory is adjusted consistently.
                /** @var SalesService $sales */
                $sales = app(SalesService::class);

                $sales->createSale(
                    locationId: 1,
                    employeeId: 1,
                    lines: [
                        ['item_id' => 1, 'quantity' => 1],
                    ],
                    customerName: 'Walk-in',
                    comment: 'Seeded demo sale'
                );
            }
        }
    }
}
