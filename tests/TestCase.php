<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function loginAsSuperAdmin(): User
    {
        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner@admst.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $this->actingAs($user);

        return $user;
    }

    protected function loginAsAdminOperasional(): User
    {
        $user = User::create([
            'name' => 'Admin Operasional',
            'email' => 'admin@adms.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        return $user;
    }
}