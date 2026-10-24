<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SsoUserService
{
    /** @param array{id: string, name: string, email: string} $identity */
    public function resolve(array $identity): User
    {
        return DB::transaction(function () use ($identity): User {
            $user = User::query()->where('sso_id', $identity['id'])->lockForUpdate()->first();
            $user ??= User::query()->where('email', $identity['email'])->lockForUpdate()->first();

            if ($user && $user->sso_id !== null && $user->sso_id !== $identity['id']) {
                throw ValidationException::withMessages(['sso' => 'Akun Inventory terhubung ke identitas SSO lain. Hubungi superadmin.']);
            }

            if (! $user) {
                $user = new User;
                $user->name = $identity['name'];
                $user->email = $identity['email'];
                $user->password = Str::random(64);
                $user->role = User::ROLE_STAFF;
            }

            $user->sso_id = $identity['id'];
            $user->save();

            return $user;
        });
    }
}
