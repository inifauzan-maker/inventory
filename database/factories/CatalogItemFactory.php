<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CatalogItemFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => fake()->unique()->bothify('BRG-######'), 'name' => 'Laptop', 'asset_category_id' => fn () => AssetCategory::create(['code' => fake()->unique()->bothify('CAT-######'), 'name' => 'Elektronik'])->id, 'unit' => 'Unit'];
    }
}
