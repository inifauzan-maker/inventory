<?php

namespace Database\Factories;

use App\Models\ProcurementRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcurementRequestItemFactory extends Factory
{
    public function definition(): array
    {
        return ['procurement_request_id' => ProcurementRequest::factory(), 'name' => 'Laptop', 'category' => 'Elektronik', 'quantity' => 1, 'unit' => 'Unit', 'specification' => 'RAM 16 GB', 'estimated_unit_price' => '10000000.00', 'subtotal' => '10000000.00'];
    }
}
