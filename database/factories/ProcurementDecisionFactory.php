<?php

namespace Database\Factories;

use App\Models\ProcurementRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcurementDecisionFactory extends Factory
{
    public function definition(): array
    {
        return ['procurement_request_id' => ProcurementRequest::factory()->submitted(), 'reviewer_id' => User::factory()->state(['role' => User::ROLE_KEUANGAN]), 'reviewer_name' => 'Keuangan Uji', 'level' => 'finance', 'decision' => 'approved', 'version' => 1, 'decided_at' => now()];
    }
}
