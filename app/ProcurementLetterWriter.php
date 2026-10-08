<?php

namespace App;

use App\Models\ProcurementRequest;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class ProcurementLetterWriter
{
    public function write(ProcurementRequest $proposal): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siip-letter-');
        if ($path === false || ! copy(base_path('docs/Form Pengajuan Inventaris (2).docx'), $path)) {
            throw new RuntimeException('Surat tidak dapat dibuat.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            unlink($path);
            throw new RuntimeException('Template surat tidak dapat dibuka.');
        }
        try {
            $doc = new DOMDocument;
            $doc->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET);
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $body = $xpath->query('//w:body')->item(0);
            $source = [];
            foreach ($body->childNodes as $node) {
                if ($node instanceof DOMElement) {
                    $source[] = $node->cloneNode(true);
                }
            }
            while ($body->firstChild) {
                $body->removeChild($body->firstChild);
            }
            foreach ([0, 1, 2] as $index) {
                $body->appendChild($source[$index]);
            }
            $number = $source[3];
            $this->text($doc, $number, 'No. '.$proposal->letter_number.' | '.ProcurementRequest::statusLabels()[$proposal->status]);
            $body->appendChild($number);
            $body->appendChild($source[4]);
            $metadata = $source[5];
            $values = [$proposal->request_date->translatedFormat('d F Y'), $proposal->subject, 'Direktur Utama melalui Bagian Keuangan'];
            foreach (iterator_to_array($xpath->query('./w:tr', $metadata)) as $index => $row) {
                if ($index >= count($values)) {
                    $metadata->removeChild($row);

                    continue;
                }
                $cell = $xpath->query('./w:tc', $row)->item(2);
                $this->text($doc, $cell, $values[$index]);
            }
            $body->appendChild($metadata);
            $body->appendChild($source[6]);
            $body->appendChild($source[7]);
            $purpose = $source[8];
            $this->text($doc, $purpose, 'Divisi / unit kerja: '.$proposal->division.'. '.$proposal->purpose);
            $body->appendChild($purpose);
            $body->appendChild($source[9]);
            $intro = $source[10];
            $this->text($doc, $intro, $proposal->selection_mode === 'alternatives' ? 'Berikut alternatif barang yang diajukan. Hanya satu alternatif yang dipilih; harga alternatif tidak dijumlahkan.' : 'Berikut daftar barang yang diajukan beserta estimasi biaya:');
            $body->appendChild($intro);
            $table = $source[11];
            $tableRows = iterator_to_array($xpath->query('./w:tr', $table));
            $itemTemplate = $tableRows[1]->cloneNode(true);
            $totalTemplate = $tableRows[count($tableRows) - 1]->cloneNode(true);
            foreach (array_slice($tableRows, 1) as $row) {
                $table->removeChild($row);
            }
            $selected = $proposal->decisions->firstWhere('level', 'finance')?->selected_item_id;
            foreach ($proposal->items as $index => $item) {
                $row = $itemTemplate->cloneNode(true);
                $values = [(string) ($index + 1), $item->name.($selected === $item->id ? ' (rekomendasi Keuangan)' : ''), $item->category, $item->quantity.' '.$item->unit, $this->money($item->estimated_unit_price), $this->money($item->subtotal)];
                foreach ($xpath->query('./w:tc', $row) as $column => $cell) {
                    $this->text($doc, $cell, $values[$column]);
                }
                $table->appendChild($row);
            }
            $totalCells = $xpath->query('./w:tc', $totalTemplate);
            $this->text($doc, $totalCells->item(0), $proposal->selection_mode === 'alternatives' ? 'ESTIMASI ALTERNATIF (TIDAK DIJUMLAHKAN)' : 'TOTAL ESTIMASI');
            $this->text($doc, $totalCells->item($totalCells->length - 1), $proposal->selection_mode === 'alternatives' ? 'Sesuai pilihan' : $this->money($proposal->estimated_total));
            $table->appendChild($totalTemplate);
            $body->appendChild($table);
            $body->appendChild($source[12]);
            $body->appendChild($source[13]);
            foreach ($proposal->items as $item) {
                $paragraph = $source[14]->cloneNode(true);
                $this->text($doc, $paragraph, $item->name.': '.($item->specification ?: 'Spesifikasi belum dicantumkan.'));
                $body->appendChild($paragraph);
            }
            if ($proposal->notes) {
                $paragraph = $source[14]->cloneNode(true);
                $this->text($doc, $paragraph, 'Catatan: '.$proposal->notes);
                $body->appendChild($paragraph);
            }
            $closing = $source[19];
            $this->text($doc, $closing, 'Demikian pengajuan ini kami sampaikan untuk menunjang kebutuhan '.$proposal->division.'. Atas perhatian dan persetujuannya, kami ucapkan terima kasih.');
            $body->appendChild($closing);
            foreach (['Yang Mengajukan' => $proposal->requester_name, 'Mengetahui — Keuangan' => $this->decisionText($proposal, 'finance'), 'Menyetujui — Direksi' => $this->decisionText($proposal, 'director')] as $label => $name) {
                $paragraph = $source[14]->cloneNode(true);
                $this->text($doc, $paragraph, $label.': '.$name);
                $body->appendChild($paragraph);
            }
            $notice = $source[14]->cloneNode(true);
            $this->text($doc, $notice, 'Keputusan tercatat di aplikasi. Nama di atas bukan tanda tangan digital. Versi pengajuan: '.$proposal->version.'.');
            $body->appendChild($notice);
            $body->appendChild($source[count($source) - 1]);
            $zip->addFromString('word/document.xml', $doc->saveXML());
            $relations = new DOMDocument;
            $relations->loadXML($zip->getFromName('word/_rels/document.xml.rels'), LIBXML_NONET);
            $removedImages = [];
            foreach (iterator_to_array($relations->documentElement->childNodes) as $relation) {
                if ($relation instanceof DOMElement && str_ends_with($relation->getAttribute('Type'), '/image')) {
                    $removedImages[] = 'word/'.ltrim($relation->getAttribute('Target'), '/');
                    $relations->documentElement->removeChild($relation);
                }
            }
            $zip->addFromString('word/_rels/document.xml.rels', $relations->saveXML());
            foreach ($removedImages as $imagePath) {
                $usedElsewhere = false;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (str_ends_with($name, '.rels') && str_contains($zip->getFromName($name), basename($imagePath))) {
                        $usedElsewhere = true;
                        break;
                    }
                }
                if (! $usedElsewhere) {
                    $zip->deleteName($imagePath);
                }
            }
            $zip->close();

            return $path;
        } catch (\Throwable $exception) {
            $zip->close();
            unlink($path);
            throw $exception;
        }
    }

    private function text(DOMDocument $doc, \DOMNode $container, string $value): void
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        if ($container->localName === 'tc') {
            $paragraph = $xpath->query('./w:p', $container)->item(0);
            foreach (iterator_to_array($xpath->query('./w:p', $container)) as $extra) {
                if ($extra !== $paragraph) {
                    $container->removeChild($extra);
                }
            }
            $container = $paragraph;
        }
        $properties = $xpath->query('./w:r/w:rPr', $container)->item(0)?->cloneNode(true);
        foreach (iterator_to_array($container->childNodes) as $node) {
            if ($node->localName !== 'pPr') {
                $container->removeChild($node);
            }
        }
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $run = $doc->createElementNS($ns, 'w:r');
        if ($properties) {
            $run->appendChild($properties);
        }
        foreach (explode("\n", $value) as $index => $line) {
            if ($index > 0) {
                $run->appendChild($doc->createElementNS($ns, 'w:br'));
            }
            $text = $doc->createElementNS($ns, 'w:t');
            $text->setAttribute('xml:space', 'preserve');
            $text->appendChild($doc->createTextNode($line));
            $run->appendChild($text);
        }
        $container->appendChild($run);
    }

    private function money(?string $value): string
    {
        return $value === null ? 'Belum diketahui' : 'Rp '.number_format((float) $value, 2, ',', '.');
    }

    private function decisionText(ProcurementRequest $proposal, string $level): string
    {
        $decision = $proposal->decisions->firstWhere('level', $level);

        return $decision ? $decision->reviewer_name.' — '.($decision->decision === 'approved' ? 'Disetujui' : 'Ditolak').' pada '.$decision->decided_at->format('d/m/Y H:i').($decision->notes ? '. Catatan: '.$decision->notes : '') : 'Belum diperiksa';
    }
}
