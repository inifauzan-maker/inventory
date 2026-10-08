<?php

namespace App;

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class InventoryWorkbookReader
{
    public const HEADERS = ['Nomor Inventaris', 'Kode Barang', 'Nama Barang', 'Kategori', 'Merk', 'Tipe / Model', 'Spesifikasi', 'Serial Number', 'Satuan', 'Cabang / Lokasi', 'Ruangan', 'Unit Kerja', 'Penanggung Jawab', 'Kondisi', 'Status Penggunaan', 'Tanggal Perolehan', 'Harga Perolehan (Rp)', 'Sumber Perolehan', 'Vendor / Pemberi', 'Nomor Dokumen', 'Tanggal Pemeriksaan', 'Tautan Foto / Dokumen', 'Keterangan'];

    /** @return list<array{number: int, values: array<string, string>, errors: list<string>}> */
    public function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->fail('File bukan workbook XLSX yang valid.');
        }

        try {
            $size = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $size += $stat['size'];
                if ($size > 25 * 1024 * 1024 || $zip->numFiles > 500) {
                    $this->fail('Isi workbook terlalu besar. Maksimal 25 MB setelah dibuka.');
                }
                if (str_contains(strtolower($stat['name']), 'vbaproject')) {
                    $this->fail('Workbook dengan macro tidak diperbolehkan.');
                }
            }
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $sheet = $workbook->query('//*[local-name()="sheet" and @name="Inventaris"]')->item(0);
            if (! $sheet) {
                $this->fail('Sheet Inventaris tidak ditemukan.');
            }
            $relationship = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $target = null;
            foreach ($this->xml($zip, 'xl/_rels/workbook.xml.rels')->query('//*[local-name()="Relationship"]') as $rel) {
                if ($rel->getAttribute('Id') === $relationship && $rel->getAttribute('TargetMode') !== 'External') {
                    $target = $rel->getAttribute('Target');
                }
            }
            if (! $target || str_contains($target, '..') || str_contains($target, ':')) {
                $this->fail('Referensi sheet tidak valid.');
            }
            $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
            $strings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $stringXml = $this->xml($zip, 'xl/sharedStrings.xml');
                foreach ($stringXml->query('//*[local-name()="si"]') as $si) {
                    $strings[] = implode('', array_map(fn ($node): string => $node->textContent, iterator_to_array($stringXml->query('.//*[local-name()="t"]', $si))));
                }
            }
            $formats = [];
            $styles = [];
            if ($zip->locateName('xl/styles.xml') !== false) {
                $styleXml = $this->xml($zip, 'xl/styles.xml');
                foreach ($styleXml->query('//*[local-name()="numFmt"]') as $format) {
                    $formats[$format->getAttribute('numFmtId')] = $format->getAttribute('formatCode');
                }
                foreach ($styleXml->query('//*[local-name()="cellXfs"]/*') as $style) {
                    $styles[] = $formats[$style->getAttribute('numFmtId')] ?? '';
                }
            }
            $calendar = $workbook->query('//*[local-name()="workbookPr"]')->item(0)?->getAttribute('date1904');
            $date1904 = in_array($calendar, ['1', 'true'], true);
            $headers = null;
            $rows = [];
            $sheetXml = $this->xml($zip, $sheetPath);
            foreach ($sheetXml->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
                $cells = [];
                $formulaColumns = [];
                foreach ($sheetXml->query('./*[local-name()="c"]', $row) as $cell) {
                    $column = preg_replace('/[0-9]/', '', $cell->getAttribute('r'));
                    $value = $sheetXml->query('./*[local-name()="v"]', $cell)->item(0)?->textContent ?? '';
                    $type = $cell->getAttribute('t');
                    if ($type === 's') {
                        $value = $strings[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = $sheetXml->query('./*[local-name()="is"]', $cell)->item(0)?->textContent ?? '';
                    }
                    $format = $styles[(int) $cell->getAttribute('s')] ?? '';
                    if (($type === '' || $type === 'n') && preg_match('/^0{2,}$/', $format) && ctype_digit($value)) {
                        $value = str_pad($value, strlen($format), '0', STR_PAD_LEFT);
                    }
                    $cells[$column] = trim(preg_replace('/\s+/u', ' ', $value));
                    if ($sheetXml->query('./*[local-name()="f"]', $cell)->length > 0 || $type === 'e') {
                        $formulaColumns[] = $column;
                    }
                }
                if (count(array_filter($cells, fn (string $value): bool => $value !== '')) === 0 && $formulaColumns === []) {
                    continue;
                }
                if ($headers === null) {
                    if ($formulaColumns !== []) {
                        $this->fail('Header tidak boleh berisi formula.');
                    }
                    $headers = [];
                    foreach ($cells as $column => $label) {
                        foreach (self::HEADERS as $header) {
                            if (mb_strtolower($label) === mb_strtolower($header)) {
                                if (in_array($header, $headers, true)) {
                                    $this->fail('Header ganda: '.$header.'.');
                                }
                                $headers[$column] = $header;
                            }
                        }
                    }
                    $missing = array_diff(self::HEADERS, $headers);
                    if ($missing !== []) {
                        $this->fail('Header wajib tidak ditemukan: '.implode(', ', $missing).'.');
                    }

                    continue;
                }
                if (count($rows) >= 200) {
                    $this->fail('Maksimal 200 baris aset per impor.');
                }
                $values = array_fill_keys(self::HEADERS, '');
                foreach ($headers as $column => $header) {
                    $values[$header] = $cells[$column] ?? '';
                }
                $errors = $formulaColumns === [] ? [] : ['Formula atau sel error tidak diperbolehkan pada baris aset.'];
                foreach (['Tanggal Perolehan', 'Tanggal Pemeriksaan'] as $header) {
                    $date = $values[$header];
                    if ($date === '') {
                        continue;
                    }
                    if (ctype_digit($date) && (int) $date > 0 && (int) $date < 2957000 && ($date1904 || (int) $date !== 60)) {
                        $days = (int) $date;
                        $base = $date1904 ? '1904-01-01' : '1899-12-31';
                        if (! $date1904 && $days > 60) {
                            $days--;
                        }
                        $values[$header] = (new DateTimeImmutable($base))->modify('+'.$days.' days')->format('Y-m-d');
                    } else {
                        $parsed = DateTimeImmutable::createFromFormat('!d/m/Y', $date);
                        if (! $parsed || $parsed->format('d/m/Y') !== $date) {
                            $errors[] = $header.' harus tanggal Excel atau teks dd/mm/yyyy yang valid.';
                        } else {
                            $values[$header] = $parsed->format('Y-m-d');
                        }
                    }
                }
                $rows[] = ['number' => (int) $row->getAttribute('r'), 'values' => $values, 'errors' => $errors];
            }
            if ($headers === null || $rows === []) {
                $this->fail('Sheet Inventaris belum berisi aset. Isi template terlebih dahulu.');
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function xml(ZipArchive $zip, string $name): DOMXPath
    {
        $content = $zip->getFromName($name);
        if ($content === false || stripos($content, '<!DOCTYPE') !== false || stripos($content, '<!ENTITY') !== false) {
            $this->fail('Struktur workbook tidak valid.');
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $document->loadXML($content, LIBXML_NONET)) {
                $this->fail('XML workbook tidak valid.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
