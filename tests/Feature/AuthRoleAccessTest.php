<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_seeded_role(): void
    {
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@siip.test',
            'role' => User::ROLE_SUPERADMIN,
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'superadmin@siip.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/inventaris');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_page_is_available_for_guest_users(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }
}
