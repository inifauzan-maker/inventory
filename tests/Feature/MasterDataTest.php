<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_category_index_page_is_accessible(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->get('/master/kategori');

        $response->assertOk();
        $response->assertSee('Master Kategori');
    }

    public function test_asset_category_can_be_created(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->post('/master/kategori', [
            'code' => 'PC',
            'name' => 'Komputer',
            'description' => 'Perangkat komputer kantor',
        ]);

        $response->assertRedirect('/master/kategori');
        $this->assertDatabaseHas('asset_categories', [
            'code' => 'PC',
            'name' => 'Komputer',
        ]);
    }

    public function test_location_can_be_created(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->post('/master/lokasi', [
            'code' => 'KTR',
            'name' => 'Kantor Pusat',
            'description' => 'Unit utama',
        ]);

        $response->assertRedirect('/master/lokasi');
        $this->assertDatabaseHas('locations', [
            'code' => 'KTR',
            'name' => 'Kantor Pusat',
        ]);
    }

    public function test_room_can_be_created_under_location(): void
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
            'code' => 'r-101',
            'name' => 'Ruang 101',
        ]);

        $response->assertRedirect('/master/ruangan');
        $this->assertDatabaseHas('rooms', [
            'location_id' => $location->id,
            'code' => 'R-101',
            'name' => 'Ruang 101',
        ]);
    }
}
