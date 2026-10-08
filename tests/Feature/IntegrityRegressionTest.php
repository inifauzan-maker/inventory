<?php

namespace Tests\Feature;

use App\AssetMutationService;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use App\PurchaseOrderService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IntegrityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_cannot_be_created_as_approved_or_by_staff(): void
    {
        $data = $this->orderData();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $this->post('/pengadaan', [...$data, 'status' => 'approved'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('purchase_orders', 0);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->post('/pengadaan', $data)->assertForbidden();
    }

    public function test_purchase_order_rolls_back_header_and_details_when_a_detail_fails(): void
    {
        DB::unprepared("CREATE TRIGGER fail_po_item BEFORE INSERT ON purchase_order_items WHEN NEW.item_name = 'Gagal' BEGIN SELECT RAISE(ABORT, 'Simulated failure'); END");
        $data = $this->orderData();
        $data['items'][] = ['item_name' => 'Gagal', 'quantity' => 1, 'unit_price' => '1.00'];
        try {
            (new PurchaseOrderService)->create($data);
            $this->fail('Kegagalan detail seharusnya membatalkan transaksi.');
        } catch (QueryException) {
            $this->assertDatabaseCount('purchase_orders', 0);
            $this->assertDatabaseCount('purchase_order_items', 0);
        }
    }

    public function test_mutation_rejects_wrong_room_and_forged_origin_and_clears_old_room(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        [$asset, $destination, $oldRoom] = $this->assetData();
        $data = ['asset_id' => $asset->id, 'to_location_id' => $destination->id];
        $this->post('/mutasi', [...$data, 'to_room_id' => $oldRoom->id])->assertSessionHasErrors('to_room_id');
        $this->post('/mutasi', [...$data, 'from_location_id' => $destination->id])->assertSessionHasErrors('from_location_id');
        $this->assertDatabaseCount('asset_mutations', 0);
        $this->post('/mutasi', $data)->assertRedirect('/inventaris');
        $this->assertSame($destination->id, $asset->fresh()->location_id);
        $this->assertNull($asset->fresh()->room_id);
        $this->assertDatabaseHas('asset_mutations', ['asset_id' => $asset->id, 'from_room_id' => $oldRoom->id, 'to_room_id' => null]);
    }

    public function test_mutation_rolls_back_position_and_history_when_audit_fails(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERASIONAL]);
        [$asset, $destination, $oldRoom] = $this->assetData();
        DB::unprepared("CREATE TRIGGER fail_mutation_audit BEFORE INSERT ON asset_activities WHEN NEW.activity_type = 'mutation' BEGIN SELECT RAISE(ABORT, 'Simulated failure'); END");
        try {
            (new AssetMutationService)->move($user, ['asset_id' => $asset->id, 'to_location_id' => $destination->id]);
            $this->fail('Kegagalan audit seharusnya membatalkan transaksi.');
        } catch (QueryException) {
            $this->assertSame($oldRoom->location_id, $asset->fresh()->location_id);
            $this->assertSame($oldRoom->id, $asset->fresh()->room_id);
            $this->assertDatabaseCount('asset_mutations', 0);
        }
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->post('/mutasi', ['asset_id' => $asset->id, 'to_location_id' => $destination->id])->assertForbidden();
    }

    private function orderData(): array
    {
        return ['order_number' => 'PO-TEST', 'vendor_name' => 'Vendor', 'order_date' => '2026-10-06', 'items' => [['item_name' => 'Laptop', 'quantity' => 2, 'unit_price' => '100.25']]];
    }

    private function assetData(): array
    {
        $category = AssetCategory::create(['code' => 'IT', 'name' => 'IT']);
        $origin = Location::create(['code' => 'A', 'name' => 'A']);
        $destination = Location::create(['code' => 'B', 'name' => 'B']);
        $room = Room::create(['code' => 'R', 'name' => 'R', 'location_id' => $origin->id]);
        $asset = Asset::create(['asset_number' => 'REG-1', 'name' => 'Laptop', 'asset_category_id' => $category->id, 'location_id' => $origin->id, 'room_id' => $room->id, 'condition' => 'baik', 'usage_status' => 'dipakai']);

        return [$asset, $destination, $room];
    }
}
