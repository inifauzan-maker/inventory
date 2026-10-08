<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_required_roles_are_defined(): void
    {
        $this->assertSame([
            'superadmin',
            'direksi',
            'kacab',
            'operasional',
            'marketing',
            'akademik',
            'staff',
            'keuangan',
        ], array_keys(User::roleLabels()));
    }

    public function test_user_role_helpers_work_as_expected(): void
    {
        $user = new User(['role' => 'operasional']);

        $this->assertTrue($user->hasRole('operasional'));
        $this->assertTrue($user->hasAnyRole(['staff', 'operasional']));
        $this->assertFalse($user->hasRole('marketing'));
    }

    public function test_roles_are_available_in_gates(): void
    {
        $this->assertTrue(
            Gate::forUser(new User(['role' => 'superadmin']))->allows('superadmin')
        );
        $this->assertFalse(
            Gate::forUser(new User(['role' => 'staff']))->allows('direksi')
        );
    }
}
