<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_log_in_via_the_admin_login_route(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => 'correct-password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.two-factor.setup'));
    }

    public function test_admin_credentials_are_rejected_on_the_client_login_route(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => 'correct-password',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'correct-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_without_confirmed_two_factor_is_redirected_to_setup_on_every_admin_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.two-factor.setup'));
    }

    public function test_admin_with_confirmed_two_factor_reaches_the_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_client_cannot_reach_any_admin_route(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $response = $this->actingAs($client)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }
}
