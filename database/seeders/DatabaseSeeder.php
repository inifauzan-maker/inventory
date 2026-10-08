<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            User::ROLE_SUPERADMIN => 'Super Admin',
            User::ROLE_DIREKSI => 'Direksi',
            User::ROLE_KACAB => 'Kacab',
            User::ROLE_OPERASIONAL => 'Operasional',
            User::ROLE_MARKETING => 'Marketing',
            User::ROLE_AKADEMIK => 'Akademik',
            User::ROLE_STAFF => 'Staff',
            User::ROLE_KEUANGAN => 'Keuangan',
        ];

        foreach ($roles as $role => $name) {
            User::firstOrCreate(
                ['email' => strtolower(str_replace(' ', '', $name)) . '@siip.test'],
                [
                    'name' => $name,
                    'role' => $role,
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
