<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_log_in_via_the_client_login_route(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Client,
            'password' => 'correct-password',
            'must_change_password' => false,
        ]);
        Client::factory()->create(['user_id' => $user->id]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('client.dashboard'));
    }

    public function test_client_credentials_are_rejected_on_the_admin_login_route(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::Client,
            'password' => 'correct-password',
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $client->email,
            'password' => 'correct-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_client_with_a_temporary_password_is_forced_to_change_it_before_reaching_the_dashboard(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::Client,
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($client)->get(route('client.dashboard'));

        $response->assertRedirect(route('client.password.change'));
    }

    public function test_admin_cannot_reach_any_client_app_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('client.dashboard'));

        $response->assertForbidden();
    }
}
