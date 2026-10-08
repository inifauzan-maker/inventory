<?php

namespace App;

use InvalidArgumentException;

class InventoryQrCode
{
    /** @return list<list<bool>> */
    public function matrix(string $text): array
    {
        $length = strlen($text);
        if ($length > 520) {
            throw new InvalidArgumentException('Nomor inventaris terlalu panjang untuk label QR.');
        }
        $version = $length <= 134 ? 6 : 15;
        $sizes = $version === 6 ? [68, 68] : [87, 87, 87, 87, 87, 88];
        $eccLength = $version === 6 ? 18 : 22;
        $capacity = array_sum($sizes);
        $bits = [];
        $append = static function (int $value, int $count) use (&$bits): void {
            for ($i = $count - 1; $i >= 0; $i--) {
                $bits[] = ($value >> $i) & 1;
            }
        };
        $append(4, 4);
        $append($length, $version < 10 ? 8 : 16);
        foreach (unpack('C*', $text) as $byte) {
            $append($byte, 8);
        }
        $append(0, min(4, $capacity * 8 - count($bits)));
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        $bytes = [];
        foreach (array_chunk($bits, 8) as $chunk) {
            $bytes[] = bindec(implode('', $chunk));
        }
        for ($i = 0; count($bytes) < $capacity; $i++) {
            $bytes[] = $i % 2 === 0 ? 0xEC : 0x11;
        }
        $blocks = [];
        $checks = [];
        $offset = 0;
        foreach ($sizes as $size) {
            $blocks[] = array_slice($bytes, $offset, $size);
            $checks[] = $this->remainder(end($blocks), $eccLength);
            $offset += $size;
        }
        $codewords = [];
        for ($i = 0; $i < max($sizes); $i++) {
            foreach ($blocks as $block) {
                if (isset($block[$i])) {
                    $codewords[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $eccLength; $i++) {
            foreach ($checks as $check) {
                $codewords[] = $check[$i];
            }
        }
        $dimension = $version * 4 + 17;
        $matrix = array_fill(0, $dimension, array_fill(0, $dimension, false));
        $reserved = $matrix;
        $set = static function (int $x, int $y, bool $dark) use (&$matrix, &$reserved, $dimension): void {
            if ($x >= 0 && $y >= 0 && $x < $dimension && $y < $dimension) {
                $matrix[$y][$x] = $dark;
                $reserved[$y][$x] = true;
            }
        };
        for ($i = 0; $i < $dimension; $i++) {
            $set(6, $i, $i % 2 === 0);
            $set($i, 6, $i % 2 === 0);
        }
        foreach ([[3, 3], [$dimension - 4, 3], [3, $dimension - 4]] as [$cx, $cy]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $distance = max(abs($dx), abs($dy));
                    $set($cx + $dx, $cy + $dy, $distance !== 2 && $distance !== 4);
                }
            }
        }
        $positions = $version === 6 ? [6, 34] : [6, 26, 48, 70];
        $last = count($positions) - 1;
        foreach ($positions as $i => $x) {
            foreach ($positions as $j => $y) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $last) || ($i === $last && $j === 0)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $set($x + $dx, $y + $dy, max(abs($dx), abs($dy)) !== 1);
                    }
                }
            }
        }
        $format = 0x77C4;
        $bit = static fn (int $value, int $i): bool => (($value >> $i) & 1) !== 0;
        for ($i = 0; $i <= 5; $i++) {
            $set(8, $i, $bit($format, $i));
        }
        $set(8, 7, $bit($format, 6));
        $set(8, 8, $bit($format, 7));
        $set(7, 8, $bit($format, 8));
        for ($i = 9; $i < 15; $i++) {
            $set(14 - $i, 8, $bit($format, $i));
        }
        for ($i = 0; $i < 8; $i++) {
            $set($dimension - 1 - $i, 8, $bit($format, $i));
        }
        for ($i = 8; $i < 15; $i++) {
            $set(8, $dimension - 15 + $i, $bit($format, $i));
        }
        $set(8, $dimension - 8, true);
        if ($version >= 7) {
            $remainder = $version;
            for ($i = 0; $i < 12; $i++) {
                $remainder = ($remainder << 1) ^ (($remainder >> 11) * 0x1F25);
            }
            $versionBits = ($version << 12) | $remainder;
            for ($i = 0; $i < 18; $i++) {
                $a = $dimension - 11 + $i % 3;
                $b = intdiv($i, 3);
                $set($a, $b, $bit($versionBits, $i));
                $set($b, $a, $bit($versionBits, $i));
            }
        }
        $index = 0;
        for ($right = $dimension - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vertical = 0; $vertical < $dimension; $vertical++) {
                $y = (($right + 1) & 2) === 0 ? $dimension - 1 - $vertical : $vertical;
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    if (! $reserved[$y][$x]) {
                        $dark = $index < count($codewords) * 8 && $bit($codewords[intdiv($index, 8)], 7 - $index % 8);
                        $matrix[$y][$x] = $dark !== (($x + $y) % 2 === 0);
                        $index++;
                    }
                }
            }
        }

        return $matrix;
    }

    public function dataUri(string $text): string
    {
        $matrix = $this->matrix($text);
        $size = count($matrix) + 8;
        $path = '';
        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $dark) {
                if ($dark) {
                    $path .= 'M'.($x + 4).','.($y + 4).'h1v1h-1z';
                }
            }
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$size.' '.$size.'" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="white"/><path d="'.$path.'" fill="black"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function multiply(int $a, int $b): int
    {
        $result = 0;
        for ($i = 7; $i >= 0; $i--) {
            $result = ($result << 1) ^ (($result >> 7) * 0x11D);
            $result ^= (($b >> $i) & 1) * $a;
        }

        return $result;
    }

    /** @param list<int> $data
     * @return list<int>
     */
    private function remainder(array $data, int $degree): array
    {
        $divisor = array_fill(0, $degree, 0);
        $divisor[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $divisor[$j] = $this->multiply($divisor[$j], $root) ^ ($divisor[$j + 1] ?? 0);
            }
            $root = $this->multiply($root, 2);
        }
        $result = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coefficient) {
                $result[$i] ^= $this->multiply($coefficient, $factor);
            }
        }

        return $result;
    }
}
