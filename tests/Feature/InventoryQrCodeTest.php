<?php

namespace Tests\Feature;

use App\InventoryQrCode;
use Tests\TestCase;

class InventoryQrCodeTest extends TestCase
{
    public function test_qr_matches_independent_reference_for_actual_long_and_unicode_numbers(): void
    {
        $cases = [
            ['VM-IT-LAP-2026-001', 'a1ce595fb8dcaad9eebcd26197f36f5f9e8e8de5279447622856f59ece906186'],
            [str_repeat('A', 100), 'efad83b7cb814caa049a9e3444e6b65d966f73234f35753b37faaa35afd2fbf4'],
            [str_repeat('é', 100), '1f887438874c206aa4abf57e4cb9dbc2fe593947318d63b900f3c69bff2faba7'],
        ];
        foreach ($cases as [$number, $expected]) {
            $matrix = (new InventoryQrCode)->matrix($number);
            $bits = '';
            foreach ($matrix as $row) {
                foreach ($row as $dark) {
                    $bits .= $dark ? '1' : '0';
                }
            }
            $this->assertSame($expected, hash('sha256', $bits));
        }
    }
}
