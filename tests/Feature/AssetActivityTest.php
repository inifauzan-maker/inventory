<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_detail_page_shows_status_change_activity_log(): void
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

        $asset = Asset::create([
            'asset_number' => 'INV-5001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lenovo',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $asset->update([
            'condition' => 'rusak',
            'usage_status' => 'perbaikan',
        ]);

        $response = $this->actingAs($user)->get('/aset/' . $asset->id);

        $response->assertOk();
        $response->assertSee('Riwayat Aktivitas');
        $response->assertSee('Status aset');
        $response->assertSee('rusak');
        $response->assertSee('perbaikan');
    }

    public function test_activity_log_page_lists_recent_user_actions(): void
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

        $asset = Asset::create([
            'asset_number' => 'INV-5002',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Dell',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $asset->update([
            'condition' => 'rusak',
            'usage_status' => 'perbaikan',
        ]);

        $response = $this->actingAs($user)->get('/audit');

        $response->assertOk();
        $response->assertSee('Audit Trail');
        $response->assertSee('Laptop Dell');
        $response->assertSee('Status aset');
    }
}
