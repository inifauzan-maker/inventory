<?php

namespace Tests\Feature;

use App\AssetImportService;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\CatalogItem;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class AssetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_preserves_identifiers_and_does_not_write_assets_or_masters(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $file = $this->workbook([['Nomor Inventaris' => '000123', 'Kode Barang' => '0005', 'Serial Number' => '0009', 'Tanggal Perolehan' => '01/10/2026']]);

        $this->post(route('aset.import.preview'), ['file' => $file])->assertRedirect(route('aset.import.index'));
        $this->get(route('aset.import.index'))->assertSee('000123')->assertSee('0009')->assertSee('0005')->assertSee('perlu dipetakan');

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('asset_categories', 0);
        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_confirm_imports_all_rows_preserving_null_zero_dates_and_audit(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERASIONAL]);
        $this->actingAs($user);
        $this->masters();
        $file = $this->workbook([
            ['Nomor Inventaris' => '0001', 'Harga Perolehan (Rp)' => '', 'Tanggal Perolehan' => '01/10/2026', 'Kode Barang' => '0007', 'Penanggung Jawab' => 'Budi'],
            [],
            ['Nomor Inventaris' => '0002', 'Harga Perolehan (Rp)' => '0', 'Tanggal Perolehan' => ['n', '46296']],
        ]);
        $this->post(route('aset.import.preview'), ['file' => $file])->assertSessionHasNoErrors();
        $token = session('asset_import_token');

        $this->post(route('aset.import.confirm'), ['token' => $token, 'confirmed' => '1'])->assertRedirect(route('aset.import.index'))->assertSessionHas('success', '2 aset berhasil diimpor. Seluruh baris disimpan.');

        $this->assertDatabaseCount('assets', 2);
        $this->assertDatabaseHas('assets', ['asset_number' => '0001', 'purchase_price' => null, 'condition' => 'baik', 'usage_status' => 'dipakai']);
        $this->assertDatabaseHas('assets', ['asset_number' => '0002', 'purchase_price' => 0]);
        $this->assertSame('2026-10-01', Asset::where('asset_number', '0001')->first()->acquired_at->format('Y-m-d'));
        $this->assertSame('2026-10-01', Asset::where('asset_number', '0002')->first()->acquired_at->format('Y-m-d'));
        $this->assertStringContainsString('Kode Barang: 0007', Asset::where('asset_number', '0001')->first()->description);
        $this->assertDatabaseCount('asset_activities', 2);
        $this->assertDatabaseHas('asset_activities', ['user_id' => $user->id, 'activity_type' => 'asset_created']);
        $this->post(route('aset.import.confirm'), ['token' => $token, 'confirmed' => '1'])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('assets', 2);
    }

    public function test_duplicate_and_invalid_rows_prevent_entire_import(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->masters();
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([
            ['Nomor Inventaris' => 'DUP'],
            ['Nomor Inventaris' => 'DUP', 'Tanggal Perolehan' => '31/02/2026', 'Harga Perolehan (Rp)' => '-1'],
        ])])->assertSessionHasNoErrors();

        $this->get(route('aset.import.index'))->assertSee('Nomor inventaris berulang dalam file.')->assertSee('dd/mm/yyyy')->assertSee('Harga perolehan harus angka');
        $this->post(route('aset.import.confirm'), ['token' => session('asset_import_token'), 'confirmed' => '1'])->assertSessionHasErrors('import');

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('asset_activities', 0);
    }

    public function test_confirm_rechecks_numbers_created_after_preview(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        [$category, $location] = $this->masters();
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([['Nomor Inventaris' => 'NEW'], ['Nomor Inventaris' => 'EXIST']])]);
        Asset::create(['asset_number' => 'EXIST', 'name' => 'Aset lama', 'asset_category_id' => $category->id, 'location_id' => $location->id, 'condition' => 'baik', 'usage_status' => 'dipakai']);

        $this->post(route('aset.import.confirm'), ['token' => session('asset_import_token'), 'confirmed' => '1'])->assertSessionHasErrors('import');

        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseMissing('assets', ['asset_number' => 'NEW']);
        $this->assertDatabaseHas('assets', ['asset_number' => 'EXIST', 'name' => 'Aset lama']);
    }

    public function test_unknown_masters_require_explicit_mapping_and_room_must_belong_to_location(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_OPERASIONAL]));
        [$category, $location, $room] = $this->masters();
        $other = Location::create(['code' => 'OTHER', 'name' => 'Cabang Lain']);
        $wrongRoom = Room::create(['code' => 'OTHER-ROOM', 'name' => 'Ruangan Lain', 'location_id' => $other->id]);
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([['Kategori' => 'Kategori sumber', 'Cabang / Lokasi' => 'Lokasi sumber', 'Ruangan' => 'Ruangan sumber']])]);
        $token = session('asset_import_token');
        $mapping = [
            AssetImportService::mappingKey('category', 'Kategori sumber') => $category->id,
            AssetImportService::mappingKey('location', 'Lokasi sumber') => $location->id,
            AssetImportService::mappingKey('room', 'Ruangan sumber', 'Lokasi sumber') => $wrongRoom->id,
        ];

        $this->post(route('aset.import.confirm'), ['token' => $token, 'confirmed' => '1', 'mapping' => $mapping])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('assets', 0);

        $mapping[AssetImportService::mappingKey('room', 'Ruangan sumber', 'Lokasi sumber')] = $room->id;
        $this->post(route('aset.import.mapping'), ['token' => $token, 'mapping' => $mapping])->assertRedirect(route('aset.import.index'));
        $this->get(route('aset.import.index'))->assertSee('Siap diimpor');
        $this->get(route('aset.import.index'))->assertSee('Siap diimpor');
        $this->post(route('aset.import.confirm'), ['token' => $token, 'confirmed' => '1', 'mapping' => $mapping])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['asset_category_id' => $category->id, 'location_id' => $location->id, 'room_id' => $room->id]);
        $this->assertDatabaseCount('asset_categories', 1);
        $this->assertDatabaseCount('locations', 2);
    }

    public function test_guest_and_staff_cannot_use_import_routes(): void
    {
        $this->get(route('aset.import.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(route('aset.import.index'))->assertForbidden();
        $this->get(route('aset.import.template'))->assertForbidden();
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([[]])])->assertForbidden();
        $this->post(route('aset.import.confirm'), ['token' => fake()->uuid(), 'confirmed' => '1'])->assertForbidden();
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_preview_token_is_private_and_expires(): void
    {
        $first = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $second = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $this->actingAs($first)->post(route('aset.import.preview'), ['file' => $this->workbook([['Nomor Inventaris' => 'SECRET-ASSET']])]);
        $token = session('asset_import_token');

        $this->actingAs($second)->get(route('aset.import.index'))->assertDontSee('SECRET-ASSET');
        $this->post(route('aset.import.confirm'), ['token' => $token, 'confirmed' => '1'])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('assets', 0);

        $this->travel(31)->minutes();
        $this->actingAs($first)->post(route('aset.import.confirm'), ['token' => $token, 'confirmed' => '1'])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('assets', 0);
        $this->travelBack();
    }

    public function test_template_download_and_empty_workbook_message(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->get(route('aset.import.template'))->assertDownload('Daftar_Inventaris_Data_Awal.xlsx');

        $this->post(route('aset.import.preview'), ['file' => new UploadedFile(base_path('docs/Daftar_Inventaris_Data_Awal.xlsx'), 'template.xlsx', null, null, true)])
            ->assertSessionHasErrors(['file' => 'Sheet Inventaris belum berisi aset. Isi template terlebih dahulu.']);
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_formula_is_rejected_and_html_is_escaped(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->masters();
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([['Nama Barang' => '<script>alert(1)</script>', 'Harga Perolehan (Rp)' => ['f', '1+1']]])])->assertSessionHasNoErrors();

        $this->get(route('aset.import.index'))->assertSee('Formula atau sel error')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post(route('aset.import.confirm'), ['token' => session('asset_import_token'), 'confirmed' => '1'])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_malformed_header_and_non_workbook_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([[]], 'Nama Barang')])
            ->assertSessionHasErrors(['file' => 'Header wajib tidak ditemukan: Nama Barang.']);
        $this->post(route('aset.import.preview'), ['file' => UploadedFile::fake()->createWithContent('data.xlsx', 'not a zip')])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_excel_1904_calendar_and_formatted_numeric_identifier_are_supported(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->masters();
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([['Nomor Inventaris' => ['padded', '123'], 'Tanggal Perolehan' => ['n', '1']]], null, true)])->assertSessionHasNoErrors();

        $this->post(route('aset.import.confirm'), ['token' => session('asset_import_token'), 'confirmed' => '1'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assets', ['asset_number' => '000123']);
        $this->assertSame('1904-01-02', Asset::first()->acquired_at->format('Y-m-d'));
    }

    private function masters(): array
    {
        $category = AssetCategory::create(['code' => 'EL', 'name' => 'Elektronik']);
        CatalogItem::create(['code' => '0007', 'name' => 'Laptop', 'asset_category_id' => $category->id, 'unit' => 'Unit']);
        $location = Location::create(['code' => 'PST', 'name' => 'Kantor Pusat']);
        $room = Room::create(['code' => 'R1', 'name' => 'Ruang Kerja', 'location_id' => $location->id]);

        return [$category, $location, $room];
    }

    public function test_storage_failure_rolls_back_assets_and_audit_for_all_rows(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->masters();
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([['Nomor Inventaris' => 'FIRST'], ['Nomor Inventaris' => 'FAIL']])]);
        DB::unprepared("CREATE TRIGGER fail_import BEFORE INSERT ON assets WHEN NEW.asset_number = 'FAIL' BEGIN SELECT RAISE(ABORT, 'Simulated storage failure'); END");

        $this->post(route('aset.import.confirm'), ['token' => session('asset_import_token'), 'confirmed' => '1'])->assertSessionHasErrors(['import' => 'Penyimpanan impor gagal. Tidak ada aset yang disimpan. Periksa data atau coba lagi.']);

        $this->assertDatabaseCount('assets', 0);
        $this->assertDatabaseCount('asset_activities', 0);
    }

    public function test_confirm_requires_user_acknowledgement(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->masters();
        $this->post(route('aset.import.preview'), ['file' => $this->workbook([['Nomor Inventaris' => 'INV-ACK']])]);

        $this->post(route('aset.import.confirm'), ['token' => session('asset_import_token')])
            ->assertSessionHasErrors(['confirmed' => 'Centang persetujuan setelah memeriksa preview.']);

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_shared_and_inline_strings_preserve_leading_zeroes(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERADMIN]));
        $this->masters();
        $file = $this->workbook([['Nomor Inventaris' => ['inlineStr', '000888'], 'Serial Number' => ['s', '0']]]);
        $zip = new ZipArchive;
        $zip->open($file->getRealPath());
        $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>000999</t></si></sst>');
        $zip->close();
        $this->post(route('aset.import.preview'), ['file' => $file])->assertSessionHasNoErrors();

        $this->post(route('aset.import.confirm'), ['token' => session('asset_import_token'), 'confirmed' => '1'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assets', ['asset_number' => '000888', 'serial_number' => '000999']);
    }

    private function workbook(array $rows, ?string $missingHeader = null, bool $date1904 = false): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'siip-import-');
        copy(base_path('docs/Daftar_Inventaris_Data_Awal.xlsx'), $path);
        $this->beforeApplicationDestroyed(function () use ($path): void {
            if (file_exists($path)) {
                unlink($path);
            }
        });
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = new DOMDocument;
        $xml->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
        $xpath = new DOMXPath($xml);
        $sheetData = $xpath->query('//*[local-name()="sheetData"]')->item(0);
        $header = $sheetData->firstChild;
        while ($sheetData->lastChild !== $header) {
            $sheetData->removeChild($sheetData->lastChild);
        }
        $columns = [];
        foreach ($xpath->query('./*[local-name()="c"]', $header) as $cell) {
            $label = $cell->textContent;
            $columns[$label] = preg_replace('/[0-9]/', '', $cell->getAttribute('r'));
            if ($label === $missingHeader) {
                $cell->firstChild->nodeValue = 'Header Tidak Dikenal';
            }
        }
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        foreach ($rows as $index => $values) {
            $row = $xml->createElementNS($ns, 'row');
            $row->setAttribute('r', (string) ($index + 2));
            if ($values !== []) {
                $values = array_replace(['Nomor Inventaris' => 'INV-001', 'Nama Barang' => 'Laptop', 'Kategori' => 'Elektronik', 'Cabang / Lokasi' => 'Kantor Pusat', 'Ruangan' => 'Ruang Kerja', 'Kondisi' => 'Baik', 'Status Penggunaan' => 'Digunakan'], $values);
            }
            foreach ($values as $label => $value) {
                $cell = $xml->createElementNS($ns, 'c');
                $cell->setAttribute('r', $columns[$label].($index + 2));
                $type = is_array($value) ? $value[0] : 'str';
                $text = is_array($value) ? $value[1] : $value;
                if ($type === 'padded') {
                    $cell->setAttribute('s', '0');
                    $type = 'n';
                }
                $cell->setAttribute('t', $type === 'f' ? 'n' : $type);
                $node = $xml->createElementNS($ns, $type === 'f' ? 'f' : ($type === 'inlineStr' ? 'is' : 'v'));
                if ($type === 'inlineStr') {
                    $textNode = $xml->createElementNS($ns, 't');
                    $textNode->appendChild($xml->createTextNode($text));
                    $node->appendChild($textNode);
                } else {
                    $node->appendChild($xml->createTextNode($text));
                }
                $cell->appendChild($node);
                $row->appendChild($cell);
            }
            $sheetData->appendChild($row);
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml->saveXML());
        if ($date1904) {
            $zip->addFromString('xl/workbook.xml', '<workbook xmlns="'.$ns.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><workbookPr date1904="1"/><sheets><sheet name="Inventaris" sheetId="1" r:id="Rac3dd1d41cc2458b"/></sheets></workbook>');
            $zip->addFromString('xl/styles.xml', '<styleSheet xmlns="'.$ns.'"><numFmts><numFmt numFmtId="164" formatCode="000000"/></numFmts><cellXfs><xf numFmtId="164"/></cellXfs></styleSheet>');
        }
        $zip->close();

        return new UploadedFile($path, 'inventaris.xlsx', null, null, true);
    }
}
