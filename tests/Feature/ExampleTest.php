<?php

namespace Tests\Feature;

use App\Models\User;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_authenticated_users_visiting_login_are_redirected_to_inventory(): void
    {
        $this->actingAs(User::factory()->make(['id' => 1]))
            ->get('/login')
            ->assertRedirect(route('inventaris.index'));
    }

    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_the_login_page_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
