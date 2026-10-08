<?php

namespace App;

use RuntimeException;
use ZipArchive;

class AssetHandoverWriter
{
    public function write(array $document): string
    {
        $data = $document['data'];
        $body = $this->paragraph('VILLA MERAH', true, true, 32);
        $body .= $this->paragraph('BERITA ACARA SERAH TERIMA ASET', true, true, 28);
        $body .= $this->paragraph('Nomor / Referensi: '.$data['reference'], true, true);
        if (! $document['snapshot']) {
            $body .= $this->paragraph('DRAF REKONSTRUKSI — DATA BARANG TERKINI', true, true);
            $body .= $this->paragraph('Serah terima lama tidak memiliki snapshot. Nama petugas dan data barang dapat berubah sejak serah terima. Verifikasi kembali sebelum dokumen ditandatangani.');
        }
        $body .= $this->paragraph('Tanggal serah terima: '.$data['date']);
        $body .= $this->paragraph('Pihak yang menyerahkan: '.$data['sender']);
        $body .= $this->paragraph('Pihak yang menerima: '.$data['recipient']);
        $body .= $this->paragraph('Dokumen ini mencatat serah terima satu unit aset dengan rincian berikut:');
        foreach (['asset_number' => 'Nomor inventaris', 'name' => 'Nama barang', 'category' => 'Kategori', 'serial' => 'Serial number', 'condition' => 'Kondisi', 'location' => 'Lokasi penempatan', 'room' => 'Ruangan'] as $key => $label) {
            $body .= $this->paragraph($label.': '.($data[$key] ?: 'Tidak tercatat'));
        }
        if ($data['notes']) {
            $body .= $this->paragraph('Catatan: '.$data['notes']);
        }
        $body .= $this->paragraph('Pihak yang menerima menyatakan telah menerima aset sesuai rincian di atas. Pengesahan fisik dilakukan dengan tanda tangan kedua pihak.');
        $body .= $this->paragraph('Yang menyerahkan                         Yang menerima', true);
        $body .= $this->paragraph("\n\n____________________                    ____________________");
        $body .= $this->paragraph($data['sender'].'                         '.$data['recipient']);
        $body .= $this->paragraph('Nama tercetak dan catatan aplikasi bukan tanda tangan digital.', false, false, 18);
        $body .= '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>';
        $path = tempnam(sys_get_temp_dir(), 'siip-bast-');
        if ($path === false) {
            throw new RuntimeException('File BAST tidak dapat dibuat.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            unlink($path);
            throw new RuntimeException('File BAST tidak dapat dibuka.');
        }
        try {
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
        } catch (\Throwable $exception) {
            $zip->close();
            unlink($path);
            throw $exception;
        }
        $zip->close();

        return $path;
    }

    private function paragraph(string $text, bool $bold = false, bool $center = false, int $size = 22): string
    {
        $runs = '';
        foreach (explode("\n", $text) as $index => $line) {
            $runs .= ($index ? '<w:r><w:br/></w:r>' : '').'<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="'.$size.'"/>'.($bold ? '<w:b/>' : '').'</w:rPr><w:t xml:space="preserve">'.htmlspecialchars($line, ENT_QUOTES | ENT_XML1, 'UTF-8').'</w:t></w:r>';
        }

        return '<w:p><w:pPr><w:spacing w:after="150"/>'.($center ? '<w:jc w:val="center"/>' : '').'</w:pPr>'.$runs.'</w:p>';
    }
}
