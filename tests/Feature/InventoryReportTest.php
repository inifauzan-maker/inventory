<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_report_page_displays_category_and_location_summary(): void
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

        $room = Room::create([
            'location_id' => $location->id,
            'code' => 'R-101',
            'name' => 'Ruang 101',
        ]);

        Asset::create([
            'asset_number' => 'INV-8001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lenovo',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $response = $this->actingAs($user)->get('/laporan');

        $response->assertOk();
        $response->assertSee('Laporan Inventaris');
        $response->assertSee('Komputer');
        $response->assertSee('Kantor Pusat');
    }

    public function test_inventory_report_export_returns_csv_data(): void
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

        $room = Room::create([
            'location_id' => $location->id,
            'code' => 'R-101',
            'name' => 'Ruang 101',
        ]);

        Asset::create([
            'asset_number' => 'INV-8002',
            'asset_category_id' => $category->id,
            'name' => 'Monitor Dell',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'cadangan',
            'serial_number' => 'SN-123',
            'purchase_price' => 2500000,
            'acquired_at' => '2026-01-15',
            'description' => 'Monitor ruang 101',
        ]);

        $response = $this->actingAs($user)->get('/laporan/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertDownload('laporan-inventaris.csv');

        $csv = method_exists($response, 'streamedContent')
            ? $response->streamedContent()
            : (method_exists($response->baseResponse, 'streamedContent') ? $response->baseResponse->streamedContent() : '');

        $this->assertStringContainsString('Serial Number', $csv);
        $this->assertStringContainsString('Harga Beli', $csv);
        $this->assertStringContainsString('SN-123', $csv);
    }
}
