<?php

namespace Tests\Feature;

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderRequestLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_request_can_be_ordered_in_parts_without_exceeding_total(): void
    {
        [$proposal, $item] = $this->source();
        $data = $this->payload($proposal, $item, 2, 'PO-1');
        $this->post('/pengadaan', $data)->assertSessionHasNoErrors();
        $order = PurchaseOrder::first();
        $this->assertSame($proposal->id, $order->procurement_request_id);
        $this->assertSame('Laptop', $order->items->first()->item_name);
        $this->assertSame('200.50', $order->total_amount);
        $this->get('/pengadaan?proposal='.$proposal->id)->assertSee('sisa 1');
        $this->post('/pengadaan', $this->payload($proposal, $item, 2, 'PO-2'))->assertSessionHasErrors('items.0.quantity');
        $this->assertDatabaseCount('purchase_orders', 1);
        $this->post('/pengadaan', $this->payload($proposal, $item, 1, 'PO-3'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('purchase_orders', 2);
    }

    public function test_pending_request_and_unrelated_item_are_rejected(): void
    {
        [$proposal, $item] = $this->source();
        $proposal->update(['status' => 'pending_director']);
        $this->post('/pengadaan', $this->payload($proposal, $item, 1, 'PO-1'))->assertSessionHasErrors('procurement_request_id');
        $proposal->update(['status' => 'approved']);
        $other = ProcurementRequestItem::factory()->create();
        $this->post('/pengadaan', $this->payload($proposal, $other, 1, 'PO-2'))->assertSessionHasErrors('items.0.procurement_request_item_id');
        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_only_finance_selected_alternative_can_be_ordered(): void
    {
        [$proposal, $item] = $this->source();
        $proposal->update(['selection_mode' => 'alternatives']);
        $alternative = ProcurementRequestItem::factory()->create(['procurement_request_id' => $proposal->id]);
        $reviewer = User::factory()->create(['role' => User::ROLE_KEUANGAN]);
        $proposal->decisions()->create(['reviewer_id' => $reviewer->id, 'reviewer_name' => $reviewer->name, 'level' => 'finance', 'decision' => 'approved', 'version' => 1, 'selected_item_id' => $item->id, 'decided_at' => now()]);
        $this->post('/pengadaan', $this->payload($proposal, $alternative, 1, 'PO-1'))->assertSessionHasErrors('items.0.procurement_request_item_id');
        $this->post('/pengadaan', $this->payload($proposal, $item, 1, 'PO-2'))->assertSessionHasNoErrors();
    }

    public function test_rejection_releases_allocation_and_reapproval_is_blocked(): void
    {
        [$proposal, $item] = $this->source();
        $this->post('/pengadaan', $this->payload($proposal, $item, 3, 'PO-1'))->assertSessionHasNoErrors();
        $order = PurchaseOrder::first();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->post(route('pengadaan.reject', $order))->assertSessionHasNoErrors();
        $this->post(route('pengadaan.approve', $order))->assertSessionHasErrors('status');
        $this->post('/pengadaan', $this->payload($proposal, $item, 3, 'PO-2'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('purchase_orders', 2);
    }

    public function test_staff_cannot_create_linked_order_and_operational_can_view_approved_source(): void
    {
        [$proposal, $item] = $this->source();
        $this->get(route('pengajuan.show', $proposal))->assertSee('Buat PO dari Pengajuan');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->post('/pengadaan', $this->payload($proposal, $item, 1, 'PO-1'))->assertForbidden();
        $this->get('/pengadaan?proposal='.$proposal->id)->assertForbidden();
    }

    private function source(): array
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $proposal = ProcurementRequest::factory()->create(['status' => 'approved']);
        $item = ProcurementRequestItem::factory()->create(['procurement_request_id' => $proposal->id, 'quantity' => 3]);

        return [$proposal, $item];
    }

    private function payload(ProcurementRequest $proposal, ProcurementRequestItem $item, int $quantity, string $number): array
    {
        return ['procurement_request_id' => $proposal->id, 'order_number' => $number, 'vendor_name' => 'Vendor', 'order_date' => '2026-10-07', 'items' => [['procurement_request_item_id' => $item->id, 'item_name' => 'Nama dimanipulasi', 'quantity' => $quantity, 'unit_price' => '100.25']]];
    }
}
