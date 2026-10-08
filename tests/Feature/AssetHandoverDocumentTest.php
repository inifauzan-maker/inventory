<?php

namespace Tests\Feature;

use App\AssetActivationService;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class AssetHandoverDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_bast_preserves_handover_snapshot_after_asset_and_actor_change(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERASIONAL, 'name' => 'Petugas Awal']);
        $this->actingAs($user);
        $asset = $this->asset('placed');
        (new AssetActivationService)->advance($asset, $user, 'handover', ['recipient_name' => 'Penerima Awal', 'handover_reference' => 'BAST-1', 'notes' => 'Catatan <script>aman</script>']);
        $asset->refresh()->update(['condition' => 'rusak berat', 'name' => 'Nama Baru']);
        $user->update(['name' => 'Petugas Baru']);

        $this->get(route('aset.bast', $asset))->assertSee('Petugas Awal')->assertSee('Laptop Lama')->assertSee('Penerima Awal')->assertSee('&lt;script&gt;', false)->assertDontSee('DRAF REKONSTRUKSI')->assertDontSee('Nama Baru');
        $response = $this->get(route('aset.bast.docx', $asset));
        $response->assertDownload('BAST-Aset-'.$asset->id.'.docx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('word/document.xml');
            $this->assertStringContainsString('Petugas Awal', $xml);
            $this->assertStringContainsString('Laptop Lama', $xml);
            $this->assertNotFalse(simplexml_load_string($xml));
            $zip->close();
        } finally {
            unlink($path);
        }
        $this->assertSame(1, $asset->activities()->where('activity_type', 'activation_handover')->count());
    }

    public function test_guest_and_staff_are_denied_and_incomplete_handover_is_unavailable(): void
    {
        $asset = $this->asset('placed');
        $this->get(route('aset.bast', $asset))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(route('aset.bast', $asset))->assertForbidden();
        $this->get(route('aset.bast.docx', $asset))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $this->get(route('aset.bast', $asset))->assertStatus(409);
        $this->get(route('aset.bast.docx', $asset))->assertStatus(409);
    }

    public function test_legacy_handover_is_explicitly_marked_as_reconstruction(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KEUANGAN]));
        $asset = $this->asset('active');
        $asset->update(['handed_over_at' => now(), 'recipient_name' => 'Penerima Lama', 'handover_reference' => 'BAST-LAMA']);

        $this->get(route('aset.bast', $asset))->assertSee('DRAF REKONSTRUKSI — DATA BARANG TERKINI')->assertSee('Belum tercatat');
        $this->assertSame(0, $asset->activities()->where('activity_type', 'activation_handover')->count());
    }

    private function asset(string $stage): Asset
    {
        $category = AssetCategory::create(['code' => 'IT', 'name' => 'Komputer']);
        $location = Location::create(['code' => 'A', 'name' => 'Lokasi Awal']);

        return Asset::create(['asset_number' => 'INV-BAST-1', 'name' => 'Laptop Lama', 'serial_number' => 'SN-001', 'asset_category_id' => $category->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai', 'activation_stage' => $stage]);
    }
}
