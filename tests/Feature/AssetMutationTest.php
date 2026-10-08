<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_mutation_page_is_accessible(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->get('/mutasi');

        $response->assertOk();
        $response->assertSee('Mutasi Aset');
    }

    public function test_asset_can_be_moved_between_rooms(): void
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
            'code' => 'R-101',
            'name' => 'Ruang 101',
        ]);

        $roomB = Room::create([
            'location_id' => $locationB->id,
            'code' => 'R-201',
            'name' => 'Ruang 201',
        ]);

        $asset = Asset::create([
            'asset_number' => 'INV-2001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lenovo',
            'location_id' => $locationA->id,
            'room_id' => $roomA->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $response = $this->actingAs($user)->post('/mutasi', [
            'asset_id' => $asset->id,
            'from_location_id' => $locationA->id,
            'to_location_id' => $locationB->id,
            'from_room_id' => $roomA->id,
            'to_room_id' => $roomB->id,
            'notes' => 'Pemindahan laporan',
        ]);

        $response->assertRedirect('/inventaris');
        $this->assertDatabaseHas('asset_mutations', [
            'asset_id' => $asset->id,
            'to_location_id' => $locationB->id,
            'to_room_id' => $roomB->id,
        ]);

        $asset->refresh();
        $this->assertSame($locationB->id, $asset->location_id);
        $this->assertSame($roomB->id, $asset->room_id);
    }
}
