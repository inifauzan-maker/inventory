<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PurchaseOrderLetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_letter_and_word_show_exact_decimal_amounts_and_escape_vendor_content(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $order = $this->order();
        $this->get(route('pengadaan.letter', $order))->assertSee('VILLA MERAH')->assertSee('Rp 301,50')->assertSee('&lt;script&gt;', false)->assertSee('DRAF — BELUM DISETUJUI');
        $response = $this->get(route('pengadaan.document', $order));
        $response->assertDownload('Surat-PO-'.$order->id.'.docx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('word/document.xml');
            $this->assertStringContainsString('Rp 301,50', $xml);
            $this->assertStringContainsString('&lt;script&gt;', $xml);
            $this->assertNotFalse(simplexml_load_string($xml));
            $this->assertNotFalse($zip->getFromName('[Content_Types].xml'));
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    public function test_only_authorized_users_can_view_or_download_letters(): void
    {
        $order = $this->order();
        $this->get(route('pengadaan.letter', $order))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(route('pengadaan.letter', $order))->assertForbidden();
        $this->get(route('pengadaan.document', $order))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KEUANGAN]));
        $this->get(route('pengadaan.letter', $order))->assertSee('SURAT PESANAN PEMBELIAN');
    }

    public function test_status_is_visible_without_changing_order_or_creating_receipts(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_DIREKSI]));
        $order = $this->order();
        $order->update(['status' => 'approved']);
        $this->get(route('pengadaan.letter', $order))->assertSee('DISETUJUI')->assertDontSee('DRAF');
        $this->assertSame('approved', $order->fresh()->status);
        $this->assertDatabaseCount('goods_receipts', 0);
        $order->update(['status' => 'rejected']);
        $this->get(route('pengadaan.letter', $order))->assertSee('DITOLAK — BUKAN PESANAN AKTIF');
    }

    private function order(): PurchaseOrder
    {
        $order = PurchaseOrder::create(['order_number' => 'PO-LETTER-1', 'vendor_name' => '<script>Vendor & Mitra</script>', 'order_date' => '2026-10-07', 'status' => 'draft', 'total_amount' => 0]);
        $order->items()->create(['item_name' => 'Laptop', 'quantity' => 3, 'unit_price' => '100.50', 'received_quantity' => 0]);

        return $order;
    }
}
