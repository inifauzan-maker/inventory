<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetActivationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_does_not_assign_numbers_until_goods_and_documents_are_verified(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $receipt = $this->receive(2);
        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('issued_inventory_numbers', 0);
        $this->post(route('penerimaan.workflow', [$receipt, 'register']), ['confirmed' => 1])->assertSessionHasErrors('stage');
        $this->post(route('penerimaan.workflow', [$receipt, 'documents']), ['confirmed' => 1, 'document_reference' => 'SJ-1'])->assertSessionHasErrors('stage');
        $this->post(route('penerimaan.workflow', [$receipt, 'inspect']), ['confirmed' => 1, 'notes' => 'Dua unit sesuai PO'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('issued_inventory_numbers', 0);
        $this->post(route('penerimaan.workflow', [$receipt, 'documents']), ['confirmed' => 1])->assertSessionHasErrors('document_reference');
        $this->post(route('penerimaan.workflow', [$receipt, 'documents']), ['confirmed' => 1, 'document_reference' => 'SJ-1'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('assets', 0);
        $this->post(route('penerimaan.workflow', [$receipt, 'register']), ['confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('assets', 2);
        $this->assertDatabaseCount('issued_inventory_numbers', 2);
        $assets = Asset::orderBy('receipt_unit_index')->get();
        $this->assertSame([1, 2], $assets->pluck('receipt_unit_index')->all());
        $this->assertNotSame($assets[0]->asset_number, $assets[1]->asset_number);
        $this->assertSame('registered', $assets[0]->activation_stage);
        $this->assertSame($receipt->items->first()->id, $assets[0]->goods_receipt_item_id);
        $this->get(route('penerimaan.show', $receipt))->assertSee($assets[0]->asset_number);
        $this->post(route('penerimaan.workflow', [$receipt, 'register']), ['confirmed' => 1])->assertSessionHasErrors('stage');
        $this->assertDatabaseCount('assets', 2);
    }

    public function test_activation_requires_print_attach_placement_and_handover_in_order(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERASIONAL]);
        $this->actingAs($user);
        $asset = $this->registeredAsset();
        $this->get(route('aset.show', $asset))->assertSee('Nomor diterbitkan')->assertSee('Buka dan Cetak Label');
        $this->get(route('aset.label', $asset))
            ->assertSee($asset->asset_number)
            ->assertSee('Cetak Label')
            ->assertSee('VILLA MERAH')
            ->assertSee('ASET INVENTARIS')
            ->assertSee('data:image/svg+xml;base64,', false);
        $this->assertNull($asset->fresh()->label_printed_at);
        $this->post(route('aset.activation', [$asset, 'handover']), ['confirmed' => 1, 'recipient_name' => 'Budi', 'handover_reference' => 'BAST-1'])->assertSessionHasErrors('stage');
        $this->post(route('aset.activation', [$asset, 'attach']), ['confirmed' => 1])->assertSessionHasErrors('stage');
        $this->post(route('aset.activation', [$asset, 'print']), [])->assertSessionHasErrors('confirmed');
        $this->post(route('aset.activation', [$asset, 'print']), ['confirmed' => 1])->assertSessionHasNoErrors();
        $this->post(route('aset.activation', [$asset, 'attach']), ['confirmed' => 1])->assertSessionHasNoErrors();
        $this->post(route('aset.activation', [$asset, 'place']), ['confirmed' => 1, 'location_id' => $asset->location_id])->assertSessionHasNoErrors();
        $this->assertSame('placed', $asset->fresh()->activation_stage);
        $this->post(route('aset.activation', [$asset, 'handover']), ['confirmed' => 1])->assertSessionHasErrors(['recipient_name', 'handover_reference']);
        $this->post(route('aset.activation', [$asset, 'handover']), ['confirmed' => 1, 'recipient_name' => 'Budi', 'handover_reference' => 'BAST-1'])->assertSessionHasNoErrors();
        $asset = $asset->fresh();
        $this->assertSame('active', $asset->activation_stage);
        $this->assertSame('Budi', $asset->recipient_name);
        $this->assertNotNull($asset->handed_over_at);
        $this->assertSame(4, $asset->activities()->where('activity_type', 'like', 'activation_%')->count());
        $this->assertDatabaseHas('asset_activities', ['asset_id' => $asset->id, 'user_id' => $user->id, 'activity_type' => 'activation_handover']);
        $this->post(route('aset.activation', [$asset, 'handover']), ['confirmed' => 1, 'recipient_name' => 'Budi', 'handover_reference' => 'BAST-1'])->assertSessionHasErrors('stage');
        $this->assertSame(4, $asset->activities()->where('activity_type', 'like', 'activation_%')->count());
    }

    public function test_room_must_match_placement_location(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $asset = $this->registeredAsset();
        $this->post(route('aset.activation', [$asset, 'print']), ['confirmed' => 1]);
        $this->post(route('aset.activation', [$asset, 'attach']), ['confirmed' => 1]);
        $other = Location::create(['code' => 'OTHER', 'name' => 'Lokasi Lain']);
        $room = Room::create(['code' => 'R1', 'name' => 'Ruang', 'location_id' => $other->id]);
        $this->post(route('aset.activation', [$asset, 'place']), ['confirmed' => 1, 'location_id' => $asset->location_id, 'room_id' => $room->id])->assertSessionHasErrors('room_id');
        $this->assertSame('label_attached', $asset->fresh()->activation_stage);
    }

    public function test_unprivileged_user_cannot_skip_or_confirm_receipt_and_asset_steps(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $receipt = $this->receive(1);
        $asset = $this->registeredAsset();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->post(route('penerimaan.workflow', [$receipt, 'inspect']), ['confirmed' => 1])->assertForbidden();
        $this->post(route('aset.activation', [$asset, 'print']), ['confirmed' => 1])->assertForbidden();
        $this->assertSame('received', $receipt->fresh()->workflow_stage);
        $this->assertSame('registered', $asset->fresh()->activation_stage);
    }

    public function test_manual_registration_requires_receipt_inspection_and_document_confirmation(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $category = AssetCategory::create(['code' => 'EL', 'name' => 'Elektronik']);
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $data = ['asset_category_id' => $category->id, 'name' => 'Laptop', 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai'];
        $this->post(route('aset.store'), $data)->assertSessionHasErrors(['received_confirmed', 'inspection_confirmed', 'documents_confirmed']);
        $this->assertDatabaseCount('issued_inventory_numbers', 0);
        $this->post(route('aset.store'), [...$data, 'received_confirmed' => 1, 'inspection_confirmed' => 1, 'documents_confirmed' => 1, 'activation_stage' => 'active'])->assertSessionHasNoErrors();
        $this->assertSame('registered', Asset::first()->activation_stage);
        $this->get(route('inventaris.index'))->assertSee('menunggu cetak label');
    }

    public function test_registration_failure_rolls_back_all_assets_numbers_and_stage(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $receipt = $this->receive(2);
        $this->post(route('penerimaan.workflow', [$receipt, 'inspect']), ['confirmed' => 1]);
        $this->post(route('penerimaan.workflow', [$receipt, 'documents']), ['confirmed' => 1, 'document_reference' => 'SJ-1']);
        DB::unprepared("CREATE TRIGGER fail_second_unit BEFORE INSERT ON assets WHEN NEW.receipt_unit_index = 2 BEGIN SELECT RAISE(ABORT, 'Simulated failure'); END");
        $this->post(route('penerimaan.workflow', [$receipt, 'register']), ['confirmed' => 1])->assertSessionHasErrors('stage');
        $this->assertSame('documents_verified', $receipt->fresh()->workflow_stage);
        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('issued_inventory_numbers', 0);
        $this->assertDatabaseCount('inventory_number_sequences', 0);
    }

    public function test_legacy_asset_is_not_reclassified(): void
    {
        $category = AssetCategory::create(['code' => 'EL', 'name' => 'Elektronik']);
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $asset = Asset::create(['asset_number' => 'LEGACY', 'name' => 'Aset lama', 'asset_category_id' => $category->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai']);
        $this->assertSame('active', $asset->fresh()->activation_stage);
        $this->assertNull($asset->fresh()->handed_over_at);
    }

    private function receive(int $quantity): GoodsReceipt
    {
        $category = AssetCategory::create(['code' => 'EL', 'name' => 'Elektronik']);
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $po = PurchaseOrder::create(['order_number' => 'PO-1', 'vendor_name' => 'Vendor', 'order_date' => '2026-10-06', 'status' => 'approved']);
        $item = PurchaseOrderItem::create(['purchase_order_id' => $po->id, 'item_name' => 'Laptop', 'quantity' => $quantity, 'unit_price' => '12500000', 'received_quantity' => 0]);
        $this->post(route('penerimaan.store'), ['purchase_order_id' => $po->id, 'receipt_number' => 'RCV-1', 'received_at' => '2026-10-06', 'items' => [['purchase_order_item_id' => $item->id, 'quantity_received' => $quantity, 'asset_category_id' => $category->id, 'location_id' => $location->id]]])->assertSessionHasNoErrors();

        return GoodsReceipt::where('receipt_number', 'RCV-1')->firstOrFail();
    }

    private function registeredAsset(): Asset
    {
        $category = AssetCategory::first() ?? AssetCategory::create(['code' => 'EL', 'name' => 'Elektronik']);
        $location = Location::first() ?? Location::create(['code' => 'L', 'name' => 'Lokasi']);

        return Asset::create(['asset_category_id' => $category->id, 'location_id' => $location->id, 'name' => 'Laptop', 'condition' => 'baik', 'usage_status' => 'dipakai', 'activation_stage' => 'registered', 'verification_confirmed_at' => now(), 'verification_confirmed_by' => auth()->id()]);
    }
}
