<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_inventory(): void
    {
        $response = $this->get('/inventaris');

        $response->assertRedirect('/login');
    }

    public function test_inventory_dashboard_shows_role_based_navigation_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $response = $this->actingAs($user)->get('/inventaris');

        $response->assertOk();
        $response->assertSee('Super Admin');
        $response->assertSee('Logout');
        $response->assertSee('Master');
    }

    public function test_inventory_dashboard_displays_summary_cards(): void
    {
        $user = User::factory()->create([
            'name' => 'Operasional',
            'role' => User::ROLE_OPERASIONAL,
        ]);

        $response = $this->actingAs($user)->get('/inventaris');

        $response->assertOk();
        $response->assertSee('Ringkasan');
        $response->assertSee('Total Aset');
        $response->assertSee('Kondisi');
    }

    public function test_inventory_dashboard_can_filter_by_search_and_status(): void
    {
        $user = User::factory()->create([
            'name' => 'Operasional',
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
            'asset_number' => 'INV-1001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lenovo',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        Asset::create([
            'asset_number' => 'INV-1002',
            'asset_category_id' => $category->id,
            'name' => 'Printer Epson',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'rusak',
            'usage_status' => 'perbaikan',
        ]);

        $response = $this->actingAs($user)->get('/inventaris?search=printer&condition=rusak&location_id='.$location->id);

        $response->assertOk();
        $response->assertSee('Printer Epson');
        $response->assertDontSee('Laptop Lenovo');
    }

    public function test_inventory_dashboard_displays_kpi_summary_by_category_and_location(): void
    {
        $user = User::factory()->create([
            'name' => 'Operasional',
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
            'asset_number' => 'INV-2001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lenovo',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $response = $this->actingAs($user)->get('/inventaris');

        $response->assertOk();
        $response->assertSee('KPI');
        $response->assertSee('Kategori');
        $response->assertSee('Lokasi');
        $response->assertSee('Komputer');
        $response->assertSee('Kantor Pusat');
    }

    public function test_asset_number_must_be_unique(): void
    {
        $category = AssetCategory::create([
            'code' => 'PC',
            'name' => 'Komputer',
        ]);

        $location = Location::create([
            'code' => 'KANTOR',
            'name' => 'Kantor Pusat',
        ]);

        $room = Room::create([
            'location_id' => $location->id,
            'code' => 'R-101',
            'name' => 'Ruang 101',
        ]);

        Asset::create([
            'asset_number' => 'INV-001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Lenovo',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Nomor inventaris pernah digunakan dan tidak boleh digunakan kembali untuk aset berbeda.');

        Asset::create([
            'asset_number' => 'INV-001',
            'asset_category_id' => $category->id,
            'name' => 'Laptop Dell',
            'location_id' => $location->id,
            'room_id' => $room->id,
            'condition' => 'baik',
            'usage_status' => 'dipakai',
        ]);
    }
}
