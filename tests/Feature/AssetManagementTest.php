<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_index_page_is_accessible(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->get('/master/ruangan');

        $response->assertOk();
        $response->assertSee('Master Ruangan');
    }

    public function test_room_can_be_created_for_a_location(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $location = Location::create([
            'code' => 'KTR',
            'name' => 'Kantor Pusat',
        ]);

        $response = $this->actingAs($user)->post('/master/ruangan', [
            'location_id' => $location->id,
            'code' => 'R-101',
            'name' => 'Ruang 101',
        ]);

        $response->assertRedirect('/master/ruangan');
        $this->assertDatabaseHas('rooms', [
            'location_id' => $location->id,
            'code' => 'R-101',
            'name' => 'Ruang 101',
        ]);
    }

    public function test_asset_can_be_created_with_valid_relationships(): void
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

        $response = $this->actingAs($user)->post('/aset', [
            'received_confirmed' => 1, 'inspection_confirmed' => 1, 'documents_confirmed' => 1,
            'asset_number' => 'INV-1001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lenovo',
            'serial_number' => 'SN-001',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
            'purchase_price' => '15000000',
            'acquired_at' => '2026-10-01',
            'description' => 'Laptop untuk staf',
        ]);

        $response->assertRedirect('/inventaris');
        $this->assertDatabaseHas('assets', [
            'asset_number' => 'INV-1001',
            'name' => 'Laptop Lenovo',
            'location_id' => $location->id,
            'room_id' => $room->id,
        ]);
    }
}
