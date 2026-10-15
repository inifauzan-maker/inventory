<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/bantuan')->assertRedirect(route('login'));
    }

    #[DataProvider('roles')]
    public function test_authenticated_users_can_read_help_and_find_the_navigation_link(string $role): void
    {
        $user = User::factory()->make(['id' => 1, 'role' => $role]);

        $response = $this->actingAs($user)->get(route('bantuan.index'));

        $response->assertOk()->assertViewIs('help.index')
            ->assertSee('Panduan Penggunaan SIIP')
            ->assertSee('Alur proses')->assertSee('Impor Excel')->assertSee('BAST')
            ->assertSee('Masalah umum')->assertSee('aria-current="page"', false)
            ->assertSee(route('bantuan.index'));
    }

    public static function roles(): array
    {
        return array_map(static fn (string $role): array => [$role], array_keys(User::roleLabels()));
    }
}
