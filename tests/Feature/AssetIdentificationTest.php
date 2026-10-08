<?php

namespace Tests\Feature;

use App\AssetImportService;
use App\InventoryWorkbookReader;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\CatalogItem;
use App\Models\InventoryNumberSetting;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetIdentificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_code_is_generated_or_preserves_legacy_zeroes(): void
    {
        $category = AssetCategory::create(['code' => 'EL', 'name' => 'Elektronik']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->post(route('master.barang.store'), ['name' => 'Laptop', 'asset_category_id' => $category->id, 'unit' => 'Unit'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('catalog_items', ['code' => 'BRG-000001', 'name' => 'Laptop']);
        $this->post(route('master.barang.store'), ['code' => '0007', 'name' => 'Monitor', 'asset_category_id' => $category->id, 'unit' => 'Unit'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('catalog_items', ['code' => '0007']);
        $this->get(route('master.barang.index'))->assertSee('BRG-000001')->assertSee('0007');
        $this->get(route('master.nomor.index'))->assertSee('Format Penomoran Otomatis');
    }

    public function test_asset_number_and_identity_are_derived_from_master(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6));
        $item = CatalogItem::factory()->create(['code' => 'BRG-000001', 'name' => 'Laptop Master']);
        $location = Location::create(['code' => 'PST', 'name' => 'Pusat']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $payload = ['received_confirmed' => 1, 'inspection_confirmed' => 1, 'documents_confirmed' => 1, 'catalog_item_id' => $item->id, 'name' => 'Nama manipulasi', 'asset_category_id' => 999, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai'];
        $this->post(route('aset.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('aset.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['asset_number' => 'INV-2026-000001', 'catalog_item_id' => $item->id, 'name' => 'Laptop Master', 'asset_category_id' => $item->asset_category_id]);
        $this->assertDatabaseHas('assets', ['asset_number' => 'INV-2026-000002']);
        $this->get(route('aset.create'))->assertSee('BRG-000001')->assertSee('Dibuat otomatis saat disimpan');
    }

    public function test_automatic_number_skips_existing_number_and_resets_per_year(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6));
        $item = CatalogItem::factory()->create();
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $values = ['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai'];
        Asset::create([...$values, 'asset_number' => 'INV-2026-000001']);
        $second = Asset::create($values);
        $this->assertSame('INV-2026-000002', $second->asset_number);
        $this->travelTo(now()->setDate(2027, 1, 2));
        $third = Asset::create($values);
        $this->assertSame('INV-2027-000001', $third->asset_number);
    }

    public function test_format_can_include_master_code_and_existing_numbers_stay_unchanged(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6));
        $item = CatalogItem::factory()->create(['code' => '0007']);
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $values = ['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai'];
        $old = Asset::create([...$values, 'asset_number' => 'OLD-001']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->post(route('master.nomor.store'), ['prefix' => 'VM', 'include_item_code' => 1])->assertSessionHasNoErrors();
        $new = Asset::create($values);
        $this->assertSame('VM-0007-2026-000001', $new->asset_number);
        $this->assertSame('OLD-001', $old->fresh()->asset_number);
    }

    public function test_staff_cannot_change_masters_and_operational_cannot_change_number_settings(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($staff)->get(route('master.barang.index'))->assertForbidden();
        $this->post(route('master.barang.store'), [])->assertForbidden();
        $this->post(route('master.nomor.store'), ['prefix' => 'BAD', 'include_item_code' => 0])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]))->post(route('master.nomor.store'), ['prefix' => 'BAD', 'include_item_code' => 0])->assertForbidden();
        $this->assertSame('INV', InventoryNumberSetting::find(1)->prefix);
    }

    public function test_asset_number_cannot_be_changed_on_edit(): void
    {
        $item = CatalogItem::factory()->create();
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $asset = Asset::create(['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai', 'asset_number' => 'OLD']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $this->put(route('aset.update', $asset), ['asset_number' => 'NEW', 'catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai'])->assertSessionHasErrors('asset_number');
        $this->assertSame('OLD', $asset->fresh()->asset_number);
    }

    public function test_import_preview_does_not_allocate_numbers_and_confirm_links_master(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->travelTo(now()->setDate(2026, 10, 6));
        $item = CatalogItem::factory()->create(['code' => '0007', 'name' => 'Laptop']);
        $location = Location::create(['code' => 'PST', 'name' => 'Pusat']);
        $rows = $this->rows($item, $location);
        $service = new AssetImportService;
        $preview = $service->preview($rows);
        $this->assertSame(0, $preview['invalid']);
        $this->assertSame(0, $preview['unmapped']);
        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('inventory_number_sequences', 0);
        $this->assertSame(2, $service->confirm($rows, []));
        $this->assertDatabaseHas('assets', ['asset_number' => 'INV-2026-000001', 'catalog_item_id' => $item->id]);
        $this->assertDatabaseHas('assets', ['asset_number' => 'INV-2026-000002', 'catalog_item_id' => $item->id]);
    }

    public function test_unknown_import_code_blocks_confirmation_without_creating_master(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $item = CatalogItem::factory()->create();
        $location = Location::create(['code' => 'PST', 'name' => 'Pusat']);
        $rows = $this->rows($item, $location);
        $rows[0]['values']['Kode Barang'] = 'UNKNOWN';
        $service = new AssetImportService;
        $this->assertSame(1, $service->preview($rows)['unmapped']);
        try {
            $service->confirm($rows, []);
            $this->fail('Unknown code was imported.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('assets', 0);
            $this->assertDatabaseCount('catalog_items', 1);
            $this->assertDatabaseCount('inventory_number_sequences', 0);
        }
        $mapping = [AssetImportService::mappingKey('catalog', 'UNKNOWN') => $item->id];
        $this->assertSame(2, $service->confirm($rows, $mapping));
        $this->assertDatabaseHas('assets', ['catalog_item_id' => $item->id]);
    }

    public function test_rollback_restores_sequence_and_assets_together(): void
    {
        $item = CatalogItem::factory()->create();
        $location = Location::create(['code' => 'PST', 'name' => 'Pusat']);
        try {
            DB::transaction(function () use ($item, $location): void {
                Asset::create(['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai']);
                throw new \RuntimeException('Rollback');
            });
        } catch (\RuntimeException) {
            $this->assertDatabaseCount('assets', 0);
            $this->assertDatabaseCount('inventory_number_sequences', 0);
            $this->assertDatabaseCount('asset_activities', 0);
            $this->assertDatabaseCount('issued_inventory_numbers', 0);
        }
    }

    public function test_excel_export_uses_master_code_instead_of_legacy_description(): void
    {
        $item = CatalogItem::factory()->create(['code' => '0007', 'unit' => 'Set']);
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        Asset::create(['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai', 'description' => 'Kode Barang: OLD']);
        $response = $this->actingAs(User::factory()->create())->get(route('laporan.export.excel'))->assertDownload('inventaris.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $row = (new InventoryWorkbookReader)->read($path)[0];
            $this->assertSame('0007', $row['values']['Kode Barang']);
            $this->assertSame('Set', $row['values']['Satuan']);
        } finally {
            unlink($path);
        }
    }

    private function rows(CatalogItem $item, Location $location): array
    {
        $values = array_replace(array_fill_keys(InventoryWorkbookReader::HEADERS, ''), ['Kode Barang' => $item->code, 'Nama Barang' => $item->name, 'Kategori' => $item->category->name, 'Cabang / Lokasi' => $location->name, 'Kondisi' => 'Baik', 'Status Penggunaan' => 'Digunakan']);

        return [['number' => 2, 'values' => $values, 'errors' => []], ['number' => 3, 'values' => $values, 'errors' => []]];
    }

    public function test_number_remains_reserved_after_asset_is_deleted(): void
    {
        $item = CatalogItem::factory()->create();
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $values = ['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai', 'asset_number' => 'OLD-NUMBER'];
        $asset = Asset::create($values);
        $originalId = $asset->id;
        $asset->delete();
        $this->assertDatabaseHas('issued_inventory_numbers', ['asset_number' => 'OLD-NUMBER', 'original_asset_id' => $originalId]);
        $rows = $this->rows($item, $location);
        $rows[0]['values']['Nomor Inventaris'] = 'OLD-NUMBER';
        $this->assertSame(1, (new AssetImportService)->preview($rows)['invalid']);
        try {
            Asset::create($values);
            $this->fail('Issued number was reused.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('assets', 0);
        }
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]))->post(route('aset.store'), $values)->assertSessionHasErrors('asset_number');
    }

    public function test_allocator_skips_permanently_issued_number_even_after_counter_is_reset(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 6));
        $item = CatalogItem::factory()->create();
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $values = ['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai'];
        Asset::create($values)->delete();
        DB::table('inventory_number_sequences')->delete();

        $asset = Asset::create($values);

        $this->assertSame('INV-2026-000002', $asset->asset_number);
    }

    public function test_number_cannot_be_changed_via_quiet_model_save(): void
    {
        $item = CatalogItem::factory()->create();
        $location = Location::create(['code' => 'L', 'name' => 'Lokasi']);
        $asset = Asset::create(['catalog_item_id' => $item->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai', 'asset_number' => 'PERMANENT']);
        $asset->asset_number = 'REPLACEMENT';
        try {
            $asset->saveQuietly();
            $this->fail('Permanent number was changed.');
        } catch (ValidationException) {
            $this->assertSame('PERMANENT', $asset->fresh()->asset_number);
        }
    }
}
