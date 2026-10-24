<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sso.enabled' => true, 'sso.server_url' => 'https://sso.example.test', 'sso.client_id' => 'inventory', 'sso.client_secret' => 'test-secret', 'sso.redirect_uri' => 'https://inventory.example.test/sso/callback']);
        Http::preventStrayRequests();
    }

    public function test_login_redirect_uses_state_and_pkce_without_exposing_secret(): void
    {
        $response = $this->get(route('sso.login'))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('inventory', $query['client_id']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(session('sso.attempt.state'), $query['state']);
        $this->assertArrayNotHasKey('client_secret', $query);
    }

    public function test_callback_provisions_verified_sso_identity_as_staff(): void
    {
        $this->fakeIdentity();
        $this->withSession($this->attempt())->get($this->callbackUrl())->assertRedirect(route('inventaris.index'));
        $user = User::where('sso_id', '123')->firstOrFail();
        $this->assertSame(User::ROLE_STAFF, $user->role);
        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://sso.example.test/oauth/token' && $request['code_verifier'] === 'verifier' && $request['client_secret'] === 'test-secret');
        $this->get($this->callbackUrl())->assertRedirect(route('inventaris.index'));
    }

    public function test_existing_account_is_linked_without_changing_its_role(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test', 'role' => User::ROLE_KEUANGAN]);
        $this->fakeIdentity();
        $this->withSession($this->attempt())->get($this->callbackUrl())->assertRedirect(route('inventaris.index'));
        $this->assertSame('123', $user->fresh()->sso_id);
        $this->assertSame(User::ROLE_KEUANGAN, $user->fresh()->role);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_wrong_or_expired_state_is_rejected_without_network_calls(): void
    {
        $this->withSession($this->attempt())->get('/sso/callback?state=wrong&code=code')->assertRedirect(route('login'))->assertSessionHasErrors('sso');
        $this->withSession(['sso.attempt' => ['state' => 'state', 'verifier' => 'verifier', 'started_at' => now()->timestamp - 301]])->get($this->callbackUrl())->assertRedirect(route('login'));
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_failed_code_exchange_does_not_create_or_log_in_user(): void
    {
        Http::fake(['https://sso.example.test/oauth/token' => Http::response(['error' => 'invalid_grant'], 400)]);
        $this->withSession($this->attempt())->get($this->callbackUrl())->assertRedirect(route('login'));
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
        Http::assertSentCount(1);
    }

    public function test_mismatched_linked_identity_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'person@example.test']);
        $user->sso_id = 'other-id';
        $user->save();
        $this->fakeIdentity();
        $this->withSession($this->attempt())->get($this->callbackUrl())->assertSessionHasErrors('sso');
        $this->assertGuest();
        $this->assertSame('other-id', $user->fresh()->sso_id);
    }

    public function test_missing_configuration_does_not_redirect_to_sso(): void
    {
        config(['sso.enabled' => false]);
        $this->get(route('sso.login'))->assertRedirect(route('login'))->assertSessionHasErrors('sso');
        Http::assertNothingSent();
    }

    private function fakeIdentity(): void
    {
        Http::fake([
            'https://sso.example.test/oauth/token' => Http::response(['access_token' => 'test-access-token']),
            'https://sso.example.test/api/sso/user' => Http::response(['id' => '123', 'name' => 'SSO User', 'email' => 'person@example.test', 'role' => 'superadmin']),
        ]);
    }

    private function attempt(): array
    {
        return ['sso.attempt' => ['state' => 'state', 'verifier' => 'verifier', 'started_at' => now()->timestamp]];
    }

    private function callbackUrl(): string
    {
        return '/sso/callback?state=state&code=code';
    }
}
