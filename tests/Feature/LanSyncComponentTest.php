<?php

namespace Tests\Feature;

use App\Models\PhpposItem;
use App\Models\PhpposItemKit;
use App\Models\PhpposLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LanSyncComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        @touch(storage_path('app/install.lock'));
    }

    public function test_lan_controller_receive_handles_component_types_and_nested_kits(): void
    {
        // 1. Create locations
        $fromLocation = PhpposLocation::create([
            'name' => 'From Store',
            'code' => 'FROM',
            'ulid' => '01AN4V9BY28F4NNM2XFAAXG1BY',
        ]);

        $toLocation = PhpposLocation::create([
            'name' => 'To Store',
            'code' => 'TO',
            'ulid' => '01AN4V9BY28F4NNM2XFAAXG1BZ',
        ]);

        // Ensure employees exist
        DB::table('phppos_employees')->insert([
            'username' => 'test_admin',
            'password' => '12345678',
            'person_id' => 1,
        ]);

        $payload = [
            'item_type' => 'transfer_out',
            'item_id' => 123,
            'from_ip' => '127.0.0.1',
            'payload' => [
                'source_device_id' => 'node_1',
                'source_port' => 8000,
                'transfer_out_id' => '123',
                'from_location_ulid' => '01AN4V9BY28F4NNM2XFAAXG1BY',
                'to_location_ulid' => '01AN4V9BY28F4NNM2XFAAXG1BZ',
                'status' => 'open',
                'lines' => [
                    [
                        'quantity' => 1.0,
                        'item_kit_product_id' => 'kit-prod-1',
                        'item_kit_name' => 'Parent Kit',
                        'components' => [
                            [
                                'type' => 'item',
                                'product_id' => 'item-prod-1',
                                'name' => 'Direct Item Component',
                                'quantity' => 2.0,
                            ],
                            [
                                'type' => 'kit',
                                'item_kit_product_id' => 'kit-prod-2',
                                'item_kit_name' => 'Nested Kit Component',
                                'quantity' => 3.0,
                                'components' => [
                                    [
                                        'type' => 'item',
                                        'product_id' => 'item-prod-2',
                                        'name' => 'Nested Item Component',
                                        'quantity' => 5.0,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/lan/receive', $payload, [
            'X-Sync-Token' => (string) config('sync.shared_token'),
        ]);

        $response->assertStatus(200);

        // Verify databases
        $parentKit = PhpposItemKit::where('product_id', 'kit-prod-1')->first();
        $this->assertNotNull($parentKit);
        
        $directItem = PhpposItem::where('product_id', 'item-prod-1')->first();
        $this->assertNotNull($directItem);
        $this->assertEquals('Direct Item Component', $directItem->name);

        // Verify parent kit has Direct Item Component linked
        $hasItemLink = DB::table('phppos_item_kit_items')
            ->where('item_kit_id', $parentKit->id)
            ->where('item_id', $directItem->item_id)
            ->where('quantity', 2.0)
            ->exists();
        $this->assertTrue($hasItemLink);

        // Verify nested kit is created
        $nestedKit = PhpposItemKit::where('product_id', 'kit-prod-2')->first();
        $this->assertNotNull($nestedKit);

        // Verify parent kit has nested kit linked in phppos_item_kit_item_kits
        $hasKitLink = DB::table('phppos_item_kit_item_kits')
            ->where('item_kit_id', $parentKit->id)
            ->where('item_kit_item_kit', $nestedKit->id)
            ->where('quantity', 3.0)
            ->exists();
        $this->assertTrue($hasKitLink);

        // Verify nested item component is created and linked to nested kit
        $nestedItem = PhpposItem::where('product_id', 'item-prod-2')->first();
        $this->assertNotNull($nestedItem);
        $this->assertEquals('Nested Item Component', $nestedItem->name);

        $hasNestedItemLink = DB::table('phppos_item_kit_items')
            ->where('item_kit_id', $nestedKit->id)
            ->where('item_id', $nestedItem->item_id)
            ->where('quantity', 5.0)
            ->exists();
        $this->assertTrue($hasNestedItemLink);
    }
}
