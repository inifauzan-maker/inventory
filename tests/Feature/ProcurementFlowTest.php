<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_procurement_pages(): void
    {
        $this->get('/pengadaan')->assertRedirect('/login');
        $this->get('/penerimaan')->assertRedirect('/login');
    }

    public function test_purchase_order_detail_page_shows_item_breakdown(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'order_number' => 'PO-2026-010',
            'vendor_name' => 'CV Detail',
            'order_date' => '2026-10-03',
            'status' => 'approved',
            'total_amount' => 15000000,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_name' => 'Laptop Detail',
            'quantity' => 1,
            'unit_price' => 15000000,
            'received_quantity' => 1,
        ]);

        $receipt = GoodsReceipt::create([
            'purchase_order_id' => $purchaseOrder->id,
            'receipt_number' => 'RCV-2026-010',
            'received_at' => '2026-10-03',
            'status' => 'received',
        ]);

        GoodsReceiptItem::create([
            'goods_receipt_id' => $receipt->id,
            'purchase_order_item_id' => $item->id,
            'item_name' => 'Laptop Detail',
            'quantity_received' => 1,
            'asset_number' => 'INV-9001',
            'asset_name' => 'Laptop Detail',
        ]);

        $response = $this->actingAs($user)->get('/pengadaan/'.$purchaseOrder->id);

        $response->assertOk();
        $response->assertSee('PO-2026-010');
        $response->assertSee('Laptop Detail');
        $response->assertSee('RCV-2026-010');
    }

    public function test_purchase_order_page_is_accessible(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->get('/pengadaan');

        $response->assertOk();
        $response->assertSee('Pengadaan');
    }

    public function test_purchase_order_can_be_approved_by_superadmin(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'order_number' => 'PO-2026-099',
            'vendor_name' => 'CV Persetujuan',
            'order_date' => '2026-10-03',
            'status' => 'draft',
            'total_amount' => 5000000,
        ]);

        $response = $this->actingAs($user)->post('/pengadaan/'.$purchaseOrder->id.'/approve');

        $response->assertRedirect('/pengadaan');
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'approved',
        ]);
    }

    public function test_purchase_order_can_be_created(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->post('/pengadaan', [
            'order_number' => 'PO-2026-001',
            'vendor_name' => 'CV Mitra Sejahtera',
            'order_date' => '2026-10-03',
            'notes' => 'Pembelian laptop kantor',
            'items' => [
                [
                    'item_name' => 'Laptop Lenovo ThinkPad',
                    'quantity' => 2,
                    'unit_price' => 15000000,
                ],
            ],
        ]);

        $response->assertRedirect('/pengadaan');
        $this->assertDatabaseHas('purchase_orders', [
            'order_number' => 'PO-2026-001',
            'vendor_name' => 'CV Mitra Sejahtera',
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'item_name' => 'Laptop Lenovo ThinkPad',
            'quantity' => 2,
        ]);
    }

    public function test_receipt_detail_page_shows_received_items(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'order_number' => 'PO-2026-011',
            'vendor_name' => 'CV Detail Receipt',
            'order_date' => '2026-10-03',
            'status' => 'approved',
            'total_amount' => 12000000,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_name' => 'Monitor Detail',
            'quantity' => 2,
            'unit_price' => 6000000,
            'received_quantity' => 2,
        ]);

        $receipt = GoodsReceipt::create([
            'purchase_order_id' => $purchaseOrder->id,
            'receipt_number' => 'RCV-2026-011',
            'received_at' => '2026-10-03',
            'status' => 'received',
        ]);

        GoodsReceiptItem::create([
            'goods_receipt_id' => $receipt->id,
            'purchase_order_item_id' => $item->id,
            'item_name' => 'Monitor Detail',
            'quantity_received' => 2,
            'asset_number' => 'INV-9002',
            'asset_name' => 'Monitor Detail',
        ]);

        $response = $this->actingAs($user)->get('/penerimaan/'.$receipt->id);

        $response->assertOk();
        $response->assertSee('RCV-2026-011');
        $response->assertSee('Monitor Detail');
    }

    public function test_receipt_can_create_assets_after_inspection_and_document_verification(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $category = AssetCategory::create([
            'code' => 'PC',
            'name' => 'Komputer',
        ]);

        $location = Location::create([
            'code' => 'KTR',
            'name' => 'Kantor Pusat',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'order_number' => 'PO-2026-002',
            'vendor_name' => 'CV Teknologi',
            'order_date' => '2026-10-03',
            'status' => 'approved',
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_name' => 'Laptop Lenovo ThinkPad',
            'quantity' => 1,
            'unit_price' => 18000000,
            'received_quantity' => 0,
        ]);

        $response = $this->actingAs($user)->post('/penerimaan', [
            'purchase_order_id' => $purchaseOrder->id,
            'receipt_number' => 'RCV-2026-002',
            'received_at' => '2026-10-03',
            'items' => [
                [
                    'purchase_order_item_id' => $item->id,
                    'quantity_received' => 1,
                    'asset_number' => 'INV-4001',
                    'asset_name' => 'Laptop Lenovo ThinkPad',
                    'asset_category_id' => $category->id,
                    'location_id' => $location->id,
                    'condition' => 'baik',
                    'usage_status' => 'dipakai',
                ],
            ],
        ]);

        $receipt = GoodsReceipt::where('receipt_number', 'RCV-2026-002')->firstOrFail();
        $response->assertRedirect(route('penerimaan.show', $receipt));
        $this->assertDatabaseCount('assets', 0);
        $this->post(route('penerimaan.workflow', [$receipt, 'inspect']), ['confirmed' => 1])->assertSessionHasNoErrors();
        $this->post(route('penerimaan.workflow', [$receipt, 'documents']), ['confirmed' => 1, 'document_reference' => 'SJ-002'])->assertSessionHasNoErrors();
        $this->post(route('penerimaan.workflow', [$receipt, 'register']), ['confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', [
            'asset_number' => 'INV-4001',
            'name' => 'Laptop Lenovo ThinkPad',
            'location_id' => $location->id,
        ]);
    }

    public function test_receipt_rejects_over_receipt_quantity(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $category = AssetCategory::create([
            'code' => 'PC',
            'name' => 'Komputer',
        ]);

        $location = Location::create([
            'code' => 'KTR',
            'name' => 'Kantor Pusat',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'order_number' => 'PO-2026-003',
            'vendor_name' => 'CV Teknologi',
            'order_date' => '2026-10-03',
            'status' => 'approved',
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_name' => 'Monitor LG 24',
            'quantity' => 2,
            'unit_price' => 3000000,
            'received_quantity' => 1,
        ]);

        $response = $this->actingAs($user)->from('/penerimaan')->post('/penerimaan', [
            'purchase_order_id' => $purchaseOrder->id,
            'receipt_number' => 'RCV-2026-003',
            'received_at' => '2026-10-03',
            'items' => [
                [
                    'purchase_order_item_id' => $item->id,
                    'quantity_received' => 2,
                    'asset_number' => 'INV-4002',
                    'asset_name' => 'Monitor LG 24',
                    'asset_category_id' => $category->id,
                    'location_id' => $location->id,
                    'condition' => 'baik',
                    'usage_status' => 'dipakai',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.quantity_received');
        $this->assertDatabaseMissing('goods_receipt_items', [
            'asset_number' => 'INV-4002',
        ]);
    }

    public function test_receipt_rejects_duplicate_asset_numbers(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $category = AssetCategory::create([
            'code' => 'PC',
            'name' => 'Komputer',
        ]);

        $location = Location::create([
            'code' => 'KTR',
            'name' => 'Kantor Pusat',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'order_number' => 'PO-2026-004',
            'vendor_name' => 'CV Teknologi',
            'order_date' => '2026-10-03',
            'status' => 'approved',
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_name' => 'Printer Laser',
            'quantity' => 1,
            'unit_price' => 5000000,
            'received_quantity' => 0,
        ]);

        Asset::create([
            'asset_number' => 'INV-4003',
            'name' => 'Printer Laser',
            'location_id' => $location->id,
            'asset_category_id' => $category->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $response = $this->actingAs($user)->from('/penerimaan')->post('/penerimaan', [
            'purchase_order_id' => $purchaseOrder->id,
            'receipt_number' => 'RCV-2026-004',
            'received_at' => '2026-10-03',
            'items' => [
                [
                    'purchase_order_item_id' => $item->id,
                    'quantity_received' => 1,
                    'asset_number' => 'INV-4003',
                    'asset_name' => 'Printer Laser',
                    'asset_category_id' => $category->id,
                    'location_id' => $location->id,
                    'condition' => 'baik',
                    'usage_status' => 'dipakai',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.asset_number');
    }

    public function test_receipt_rejects_cumulative_over_receipt_for_same_po_item_in_one_request(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $category = AssetCategory::create([
            'code' => 'PC',
            'name' => 'Komputer',
        ]);

        $location = Location::create([
            'code' => 'KTR',
            'name' => 'Kantor Pusat',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'order_number' => 'PO-2026-005',
            'vendor_name' => 'CV Teknologi',
            'order_date' => '2026-10-03',
            'status' => 'approved',
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_name' => 'Keyboard Wireless',
            'quantity' => 2,
            'unit_price' => 500000,
            'received_quantity' => 1,
        ]);

        $response = $this->actingAs($user)->from('/penerimaan')->post('/penerimaan', [
            'purchase_order_id' => $purchaseOrder->id,
            'receipt_number' => 'RCV-2026-005',
            'received_at' => '2026-10-03',
            'items' => [
                [
                    'purchase_order_item_id' => $item->id,
                    'quantity_received' => 1,
                    'asset_number' => 'INV-5001',
                    'asset_name' => 'Keyboard Wireless',
                    'asset_category_id' => $category->id,
                    'location_id' => $location->id,
                    'condition' => 'baik',
                    'usage_status' => 'dipakai',
                ],
                [
                    'purchase_order_item_id' => $item->id,
                    'quantity_received' => 1,
                    'asset_number' => 'INV-5002',
                    'asset_name' => 'Keyboard Wireless',
                    'asset_category_id' => $category->id,
                    'location_id' => $location->id,
                    'condition' => 'baik',
                    'usage_status' => 'dipakai',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.1.quantity_received');
    }
}
