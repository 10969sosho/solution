<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    private function makeUser(): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => 'owner@admst.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
    }

    public function test_root_and_dashboard_alias_return_ok_for_authenticated_user(): void
    {
        $this->loginAsSuperAdmin();

        $this->get('/')->assertOk();
        $this->get('/dashboard')->assertOk();
    }

    public function test_login_with_ajax_intended_url_redirects_to_dashboard(): void
    {
        $user = $this->makeUser();

        $response = $this->withSession(['url.intended' => '/dashboard/data'])
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('url.intended'));
    }

    public function test_normal_login_redirects_to_dashboard(): void
    {
        $user = $this->makeUser();

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
