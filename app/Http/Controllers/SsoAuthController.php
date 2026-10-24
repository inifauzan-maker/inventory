<?php

namespace App\Http\Controllers;

use App\Http\Requests\SsoCallbackRequest;
use App\Services\SsoUserService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SsoAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! config('sso.enabled') || ! config('sso.client_id') || ! config('sso.client_secret')) {
            return to_route('login')->withErrors(['sso' => 'SSO belum dikonfigurasi. Hubungi superadmin.']);
        }

        $state = Str::random(64);
        $verifier = Str::random(64);
        $request->session()->put('sso.attempt', ['state' => $state, 'verifier' => $verifier, 'started_at' => now()->timestamp]);

        return redirect()->away(rtrim(config('sso.server_url'), '/').'/oauth/authorize?'.http_build_query([
            'client_id' => config('sso.client_id'), 'redirect_uri' => config('sso.redirect_uri'),
            'response_type' => 'code', 'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]));
    }

    public function callback(SsoCallbackRequest $request, SsoUserService $users): RedirectResponse
    {
        $data = $request->validated();
        $attempt = $request->session()->pull('sso.attempt');
        $failure = fn (): RedirectResponse => to_route('login')->withErrors(['sso' => 'Login SSO gagal atau kedaluwarsa. Silakan coba kembali.']);

        if (! config('sso.enabled') || ! is_array($attempt) || ! hash_equals($attempt['state'], $data['state']) || $attempt['started_at'] < now()->timestamp - 300) {
            return $failure();
        }

        try {
            $server = rtrim(config('sso.server_url'), '/');
            $token = Http::asForm()->acceptJson()->withoutRedirecting()->timeout(15)->post($server.'/oauth/token', [
                'grant_type' => 'authorization_code', 'client_id' => config('sso.client_id'),
                'client_secret' => config('sso.client_secret'), 'redirect_uri' => config('sso.redirect_uri'),
                'code' => $data['code'], 'code_verifier' => $attempt['verifier'],
            ])->throw()->json('access_token');

            if (! is_string($token) || $token === '') {
                return $failure();
            }

            $identity = Http::withToken($token)->acceptJson()->withoutRedirecting()->timeout(15)->get($server.'/api/sso/user')->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            return $failure();
        }

        if (! is_array($identity) || ! isset($identity['id']) || (! is_string($identity['id']) && ! is_int($identity['id']))) {
            return $failure();
        }
        $identity['id'] = (string) $identity['id'];
        $validator = Validator::make($identity, ['id' => ['required', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255']]);
        if ($validator->fails()) {
            return $failure();
        }

        $user = $users->resolve($validator->validated());
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('inventaris.index'));
    }
}
