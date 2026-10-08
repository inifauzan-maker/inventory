<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcurementRequestFactory extends Factory
{
    public function definition(): array
    {
        return ['submission_key' => fake()->uuid(), 'letter_number' => 'PGB/'.fake()->unique()->numerify('########'), 'requester_id' => User::factory(), 'requester_name' => 'Pengaju Uji', 'division' => 'Operasional', 'subject' => 'Pengajuan Barang Inventaris', 'request_date' => '2026-10-06', 'purpose' => 'Kebutuhan operasional kantor.', 'selection_mode' => 'all', 'status' => 'draft', 'version' => 1, 'estimated_total' => null];
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => ['status' => 'pending_finance', 'submitted_at' => now()]);
    }
}
