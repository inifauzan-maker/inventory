<?php

namespace App;

use App\Models\Asset;
use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class InventoryWorkbookWriter
{
    /** @param iterable<array<string, string|null>> $rows */
    public function write(iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siip-export-');
        if ($path === false || ! copy(base_path('docs/Daftar_Inventaris_Data_Awal.xlsx'), $path)) {
            throw new RuntimeException('Tidak dapat membuat file ekspor.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            unlink($path);
            throw new RuntimeException('Tidak dapat membuka template ekspor.');
        }
        try {
            $document = new DOMDocument;
            $document->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'), LIBXML_NONET);
            $xpath = new DOMXPath($document);
            $sheetData = $xpath->query('//*[local-name()="sheetData"]')->item(0);
            while ($sheetData->firstChild) {
                $sheetData->removeChild($sheetData->firstChild);
            }
            $number = 1;
            $this->appendRow($document, $sheetData, array_combine(InventoryWorkbookReader::HEADERS, InventoryWorkbookReader::HEADERS), $number++, true);
            foreach ($rows as $row) {
                $this->appendRow($document, $sheetData, $row, $number++);
            }
            $range = 'A1:W'.max(2, $number - 1);
            foreach ($xpath->query('//*[local-name()="dimension"]') as $dimension) {
                $dimension->setAttribute('ref', $range);
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $document->saveXML());
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/tables/') && str_ends_with($name, '.xml')) {
                    $table = new DOMDocument;
                    $table->loadXML($zip->getFromName($name), LIBXML_NONET);
                    $table->documentElement->setAttribute('ref', $range);
                    foreach ($table->getElementsByTagNameNS('*', 'autoFilter') as $filter) {
                        $filter->setAttribute('ref', $range);
                    }
                    $zip->addFromString($name, $table->saveXML());
                }
            }
            if (! $zip->close()) {
                throw new RuntimeException('File ekspor gagal disimpan.');
            }

            return $path;
        } catch (\Throwable $exception) {
            $zip->close();
            unlink($path);
            throw $exception;
        }
    }

    private function appendRow(DOMDocument $document, \DOMNode $sheetData, array $values, int $number, bool $header = false): void
    {
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $row = $document->createElementNS($ns, 'row');
        $row->setAttribute('r', (string) $number);
        $row->setAttribute('ht', $header ? '34' : '22');
        $row->setAttribute('customHeight', '1');
        foreach (InventoryWorkbookReader::HEADERS as $index => $label) {
            $value = $values[$label] ?? '';
            $cell = $document->createElementNS($ns, 'c');
            $cell->setAttribute('r', chr(65 + $index).$number);
            $numeric = ! $header && $label === 'Harga Perolehan (Rp)' && $value !== '' && is_numeric($value);
            $cell->setAttribute('s', $header ? '7' : ($numeric ? '9' : '3'));
            $cell->setAttribute('t', $numeric ? 'n' : 'inlineStr');
            if ($numeric) {
                $node = $document->createElementNS($ns, 'v');
                $node->appendChild($document->createTextNode((string) $value));
            } else {
                $node = $document->createElementNS($ns, 'is');
                $text = $document->createElementNS($ns, 't');
                $text->setAttribute('xml:space', 'preserve');
                $text->appendChild($document->createTextNode((string) $value));
                $node->appendChild($text);
            }
            $cell->appendChild($node);
            $row->appendChild($cell);
        }
        $sheetData->appendChild($row);
    }

    public function assetRow(Asset $asset): array
    {
        $row = array_fill_keys(InventoryWorkbookReader::HEADERS, '');
        $notes = [];
        foreach (explode("\n", $asset->description ?? '') as $line) {
            [$label, $value] = array_pad(explode(': ', $line, 2), 2, null);
            if ($value !== null && array_key_exists($label, $row)) {
                $row[$label] = $value;
            } elseif ($line !== '') {
                $notes[] = $line;
            }
        }
        if ($notes !== []) {
            $row['Keterangan'] = implode("\n", array_filter([$row['Keterangan'], ...$notes]));
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['Tanggal Pemeriksaan'])) {
            $row['Tanggal Pemeriksaan'] = implode('/', array_reverse(explode('-', $row['Tanggal Pemeriksaan'])));
        }

        return array_replace($row, [
            'Kode Barang' => $asset->catalogItem?->code ?? $row['Kode Barang'],
            'Satuan' => $asset->catalogItem?->unit ?? $row['Satuan'],
            'Nomor Inventaris' => $asset->asset_number,
            'Nama Barang' => $asset->name,
            'Kategori' => $asset->category?->name ?? '',
            'Serial Number' => $asset->serial_number ?? '',
            'Cabang / Lokasi' => $asset->location?->name ?? '',
            'Ruangan' => $asset->room?->name ?? '',
            'Kondisi' => mb_convert_case($asset->condition, MB_CASE_TITLE),
            'Status Penggunaan' => $asset->usage_status === 'dipakai' ? 'Digunakan' : mb_convert_case($asset->usage_status, MB_CASE_TITLE),
            'Tanggal Perolehan' => $asset->acquired_at?->format('d/m/Y') ?? '',
            'Harga Perolehan (Rp)' => $asset->purchase_price,
        ]);
    }

    public function exampleRows(): array
    {
        $base = ['Cabang / Lokasi' => 'Kantor Pusat', 'Ruangan' => 'Ruang Operasional', 'Unit Kerja' => 'Operasional', 'Kondisi' => 'Baik', 'Status Penggunaan' => 'Digunakan', 'Satuan' => 'Unit', 'Sumber Perolehan' => 'Saldo Awal', 'Keterangan' => 'DATA CONTOH — ganti dengan data aset sebenarnya sebelum impor.'];

        return [
            array_replace($base, ['Nomor Inventaris' => 'CONTOH-0001', 'Kode Barang' => '0001', 'Nama Barang' => 'Laptop Operasional', 'Kategori' => 'Elektronik', 'Merk' => 'Lenovo', 'Tipe / Model' => 'ThinkPad', 'Spesifikasi' => 'RAM 16 GB, SSD 512 GB', 'Serial Number' => '00012345', 'Penanggung Jawab' => 'Budi (contoh)', 'Tanggal Perolehan' => '01/10/2026', 'Harga Perolehan (Rp)' => '12500000', 'Tanggal Pemeriksaan' => '05/10/2026']),
            array_replace($base, ['Nomor Inventaris' => 'CONTOH-0002', 'Kode Barang' => '0002', 'Nama Barang' => 'Kursi Kerja', 'Kategori' => 'Furniture', 'Satuan' => 'Buah', 'Penanggung Jawab' => 'Sari (contoh)', 'Tanggal Perolehan' => '15/09/2026', 'Harga Perolehan (Rp)' => null, 'Status Penggunaan' => 'Cadangan']),
            array_replace($base, ['Nomor Inventaris' => 'CONTOH-0003', 'Kode Barang' => '0003', 'Nama Barang' => 'Printer Hibah', 'Kategori' => 'Elektronik', 'Merk' => 'Epson', 'Serial Number' => '00067890', 'Penanggung Jawab' => 'Budi (contoh)', 'Tanggal Perolehan' => '20/09/2026', 'Harga Perolehan (Rp)' => '0', 'Sumber Perolehan' => 'Hibah', 'Vendor / Pemberi' => 'Mitra (contoh)']),
        ];
    }
}
