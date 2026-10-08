<?php

namespace App;

use App\Models\PurchaseOrder;
use RuntimeException;
use ZipArchive;

class PurchaseOrderLetterWriter
{
    public function write(PurchaseOrder $order): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siip-po-');
        if ($path === false) {
            throw new RuntimeException('File surat PO tidak dapat dibuat.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            unlink($path);
            throw new RuntimeException('File surat PO tidak dapat dibuka.');
        }
        try {
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $body = $this->paragraph('VILLA MERAH', true, true, 32);
            $body .= $this->paragraph('SURAT PESANAN PEMBELIAN', true, true, 28);
            $body .= $this->paragraph('Nomor PO: '.$order->order_number, true, true);
            $body .= $this->paragraph($order->status === 'approved' ? 'DISETUJUI' : ($order->status === 'rejected' ? 'DITOLAK — BUKAN PESANAN AKTIF' : 'DRAF — BELUM DISETUJUI'), true, true);
            $body .= $this->paragraph('Tanggal: '.$order->order_date->format('d/m/Y'));
            $body .= $this->paragraph('Kepada vendor: '.$order->vendor_name);
            if ($order->procurementRequest) {
                $body .= $this->paragraph('Pengajuan sumber: '.$order->procurementRequest->letter_number.' | Versi '.$order->procurement_request_version);
            }
            $body .= $this->paragraph('Berikut rincian barang dalam PO ini:');
            $rows = $this->row(['No.', 'Barang / Satuan', 'Jumlah', 'Harga satuan', 'Subtotal'], true, true);
            $total = '0.00';
            foreach ($order->items as $index => $item) {
                $subtotal = bcmul((string) $item->unit_price, (string) $item->quantity, 2);
                $total = bcadd($total, $subtotal, 2);
                $description = $item->item_name;
                if ($item->procurementRequestItem) {
                    $description .= ' / '.$item->procurementRequestItem->unit;
                    if ($item->procurementRequestItem->specification) {
                        $description .= "\n".$item->procurementRequestItem->specification;
                    }
                }
                if ($item->notes) {
                    $description .= "\n".$item->notes;
                }
                $rows .= $this->row([(string) ($index + 1), $description, (string) $item->quantity, $this->money($item->unit_price), $this->money($subtotal)]);
            }
            $rows .= $this->row(['', 'TOTAL', '', '', $this->money($total)], true);
            $body .= '<w:tbl><w:tblPr><w:tblW w:w="10000" w:type="dxa"/><w:tblBorders>';
            foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $edge) {
                $body .= '<w:'.$edge.' w:val="single" w:sz="4" w:color="D9D9D9"/>';
            }
            $body .= '</w:tblBorders><w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="100" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid><w:gridCol w:w="500"/><w:gridCol w:w="3800"/><w:gridCol w:w="900"/><w:gridCol w:w="2400"/><w:gridCol w:w="2400"/></w:tblGrid>'.$rows.'</w:tbl>';
            if ($order->notes) {
                $body .= $this->paragraph('Catatan: '.$order->notes);
            }
            $body .= $this->paragraph('Disiapkan oleh: ____________________');
            $body .= $this->paragraph('Disetujui oleh Direksi: ____________________');
            $body .= $this->paragraph('Nama dan tanggal tanda tangan: ____________________');
            $body .= $this->paragraph('Status berasal dari aplikasi. Ruang tanda tangan di atas belum merupakan tanda tangan digital.', false, false, 18);
            $body .= '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="953" w:bottom="1134" w:left="953"/></w:sectPr>';
            $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
        } catch (\Throwable $exception) {
            $zip->close();
            unlink($path);
            throw $exception;
        }
        $zip->close();

        return $path;
    }

    public function money(string $value): string
    {
        return 'Rp '.number_format((float) $value, 2, ',', '.');
    }

    private function paragraph(string $text, bool $bold = false, bool $center = false, int $size = 22): string
    {
        $runs = '';
        foreach (explode("\n", $text) as $index => $line) {
            $runs .= ($index > 0 ? '<w:r><w:br/></w:r>' : '').'<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="'.$size.'"/>'.($bold ? '<w:b/>' : '').'</w:rPr><w:t xml:space="preserve">'.htmlspecialchars($line, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t></w:r>';
        }

        return '<w:p><w:pPr><w:spacing w:after="140"/>'.($center ? '<w:jc w:val="center"/>' : '').'</w:pPr>'.$runs.'</w:p>';
    }

    private function row(array $values, bool $header = false, bool $repeat = false): string
    {
        $row = '<w:tr><w:trPr><w:cantSplit/>'.($repeat ? '<w:tblHeader/>' : '').'</w:trPr>';
        foreach ($values as $index => $value) {
            $width = [500, 3800, 900, 2400, 2400][$index];
            $row .= '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'.($header ? '<w:shd w:fill="EAEAEA"/>' : '').'</w:tcPr>'.$this->paragraph($value, $header, false, 20).'</w:tc>';
        }

        return $row.'</w:tr>';
    }
}
