<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_edit_page_is_accessible(): void
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
            'asset_number' => 'INV-3001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lama',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $response = $this->actingAs($user)->get('/aset/' . $asset->id . '/edit');

        $response->assertOk();
        $response->assertSee('Edit Aset');
    }

    public function test_asset_detail_page_shows_asset_information_and_mutation_history(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $category = AssetCategory::create([
            'code' => 'PC',
            'name' => 'Komputer',
        ]);

        $locationA = Location::create([
            'code' => 'KTRA',
            'name' => 'Kantor A',
        ]);

        $locationB = Location::create([
            'code' => 'KTRB',
            'name' => 'Kantor B',
        ]);

        $roomA = Room::create([
            'location_id' => $locationA->id,
            'code' => 'R-10',
            'name' => 'Ruang 10',
        ]);

        $roomB = Room::create([
            'location_id' => $locationB->id,
            'code' => 'R-20',
            'name' => 'Ruang 20',
        ]);

        $asset = Asset::create([
            'asset_number' => 'INV-3003',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Detail',
            'location_id' => $locationA->id,
            'room_id' => $roomA->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $asset->mutations()->create([
            'from_location_id' => $locationA->id,
            'to_location_id' => $locationB->id,
            'from_room_id' => $roomA->id,
            'to_room_id' => $roomB->id,
            'notes' => 'Pemindahan awal',
            'moved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/aset/' . $asset->id);

        $response->assertOk();
        $response->assertSee('Laptop Detail');
        $response->assertSee('Mutasi');
        $response->assertSee('Ruang 20');
    }

    public function test_asset_can_be_updated(): void
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
            'asset_number' => 'INV-3002',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lama',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $response = $this->actingAs($user)->put('/aset/' . $asset->id, [
            'asset_number' => 'INV-3002',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Baru',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'rusak_ringan',
            'usage_status' => 'perbaikan',
        ]);

        $response->assertRedirect('/inventaris');
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Laptop Baru',
            'condition' => 'rusak_ringan',
            'usage_status' => 'perbaikan',
        ]);
    }
}
