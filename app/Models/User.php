<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_DIREKSI = 'direksi';
    public const ROLE_KACAB = 'kacab';
    public const ROLE_OPERASIONAL = 'operasional';
    public const ROLE_MARKETING = 'marketing';
    public const ROLE_AKADEMIK = 'akademik';
    public const ROLE_STAFF = 'staff';
    public const ROLE_KEUANGAN = 'keuangan';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public static function roleLabels(): array
    {
        return [
            self::ROLE_SUPERADMIN => 'Superadmin',
            self::ROLE_DIREKSI => 'Direksi',
            self::ROLE_KACAB => 'Kacab',
            self::ROLE_OPERASIONAL => 'Operasional',
            self::ROLE_MARKETING => 'Marketing',
            self::ROLE_AKADEMIK => 'Akademik',
            self::ROLE_STAFF => 'Staff',
            self::ROLE_KEUANGAN => 'Keuangan',
        ];
    }

    public function hasRole(string $role): bool
    {
        return strtolower((string) $this->role) === strtolower($role);
    }

    public function roleLabel(): string
    {
        return self::roleLabels()[$this->role] ?? ucfirst((string) $this->role);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole((string) $role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPERADMIN);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }
}
