<?php

namespace Tests\Feature;

use App\InventoryWorkbookReader;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class AssetExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_export_preserves_text_decimal_null_and_extra_columns(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $this->asset(['asset_number' => '000001', 'name' => '=1+1', 'serial_number' => '000123', 'purchase_price' => '1250.75', 'acquired_at' => '2026-10-01', 'description' => "Kode Barang: 0007\nMerk: Lenovo\nTanggal Pemeriksaan: 2026-10-05\nKeterangan: Contoh"]);
        $this->asset(['asset_number' => '000002', 'purchase_price' => null]);
        $this->asset(['asset_number' => '000003', 'purchase_price' => '0']);

        $response = $this->get(route('laporan.export.excel'));
        $response->assertDownload('inventaris.xlsx')->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $rows = (new InventoryWorkbookReader)->read($path);
            $this->assertCount(3, $rows);
            $this->assertSame('000001', $rows[0]['values']['Nomor Inventaris']);
            $this->assertSame('000123', $rows[0]['values']['Serial Number']);
            $this->assertSame('0007', $rows[0]['values']['Kode Barang']);
            $this->assertSame('Lenovo', $rows[0]['values']['Merk']);
            $this->assertSame('=1+1', $rows[0]['values']['Nama Barang']);
            $this->assertSame('1250.75', $rows[0]['values']['Harga Perolehan (Rp)']);
            $this->assertSame('2026-10-01', $rows[0]['values']['Tanggal Perolehan']);
            $this->assertSame('2026-10-05', $rows[0]['values']['Tanggal Pemeriksaan']);
            $this->assertSame('Digunakan', $rows[0]['values']['Status Penggunaan']);
            $this->assertSame('', $rows[1]['values']['Harga Perolehan (Rp)']);
            $this->assertSame('0.00', $rows[2]['values']['Harga Perolehan (Rp)']);
            $this->assertSame([], $rows[0]['errors']);
            $zip = new ZipArchive;
            $zip->open($path);
            $this->assertStringNotContainsString('<f>', $zip->getFromName('xl/worksheets/sheet1.xml'));
            $this->assertNotFalse($zip->getFromName('xl/worksheets/sheet2.xml'));
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    public static function filters(): array
    {
        return [
            'search' => ['search', 'Laptop'],
            'category' => ['category_id', null],
            'location' => ['location_id', null],
            'condition' => ['condition', 'baik'],
            'status' => ['usage_status', 'dipakai'],
        ];
    }

    #[DataProvider('filters')]
    public function test_excel_and_csv_exports_follow_each_inventory_filter(string $filter, ?string $value): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $wanted = $this->asset(['asset_number' => 'INCLUDED', 'name' => 'Laptop']);
        $this->asset(['asset_number' => 'EXCLUDED', 'name' => 'Meja', 'condition' => 'rusak berat', 'usage_status' => 'cadangan']);
        $value ??= (string) ($filter === 'category_id' ? $wanted->asset_category_id : $wanted->location_id);

        $response = $this->get(route('laporan.export.excel', [$filter => $value]));
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $rows = (new InventoryWorkbookReader)->read($path);
            $this->assertCount(1, $rows);
            $this->assertSame('INCLUDED', $rows[0]['values']['Nomor Inventaris']);
        } finally {
            unlink($path);
        }
        $csv = $this->get(route('laporan.export', [$filter => $value]))->streamedContent();
        $this->assertStringContainsString('INCLUDED', $csv);
        $this->assertStringNotContainsString('EXCLUDED', $csv);
    }

    public function test_example_download_can_be_previewed_without_saving_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));

        $response = $this->get(route('aset.import.example'))->assertDownload('Contoh_Import_Inventaris.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $rows = (new InventoryWorkbookReader)->read($path);
            $this->assertCount(3, $rows);
            $this->assertSame('00012345', $rows[0]['values']['Serial Number']);
            $this->assertSame('', $rows[1]['values']['Harga Perolehan (Rp)']);
            $this->assertSame('0', $rows[2]['values']['Harga Perolehan (Rp)']);
            $this->post(route('aset.import.preview'), ['file' => new UploadedFile($path, 'contoh.xlsx', null, null, true)])->assertSessionHasNoErrors();
            $this->get(route('aset.import.index'))->assertSee('CONTOH-0001')->assertSee('CONTOH-0003');
            $this->assertDatabaseCount('assets', 0);
            $this->assertDatabaseCount('asset_categories', 0);
        } finally {
            unlink($path);
        }
    }

    public function test_csv_export_neutralizes_formula_cells(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        $this->asset(['asset_number' => '=123', 'name' => '+SUM(1)', 'serial_number' => '@evil', 'description' => '-2+3']);

        $csv = $this->get(route('laporan.export'))->streamedContent();

        $this->assertStringContainsString("'=123", $csv);
        $this->assertStringContainsString("'+SUM(1)", $csv);
        $this->assertStringContainsString("'@evil", $csv);
        $this->assertStringContainsString("'-2+3", $csv);
    }

    public function test_export_requires_login_and_sample_requires_import_role(): void
    {
        $this->get(route('laporan.export.excel'))->assertRedirect(route('login'));
        $this->get(route('aset.import.example'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]))->get(route('aset.import.example'))->assertForbidden();
    }

    public function test_empty_export_contains_template_headers_and_no_assets(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));

        $response = $this->get(route('laporan.export.excel'))->assertDownload('inventaris.xlsx');
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $zip->open($path);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertStringContainsString('Nomor Inventaris', $xml);
            $this->assertStringNotContainsString('r="A2"', $xml);
            $zip->close();
        } finally {
            unlink($path);
        }
    }

    private function asset(array $attributes): Asset
    {
        $category = AssetCategory::create(['code' => fake()->unique()->bothify('CAT-####'), 'name' => 'Elektronik']);
        $location = Location::create(['code' => fake()->unique()->bothify('LOC-####'), 'name' => 'Kantor Pusat']);

        return Asset::create(array_replace(['asset_number' => 'INV-TEST', 'name' => 'Barang', 'asset_category_id' => $category->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai'], $attributes));
    }
}
