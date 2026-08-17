<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminClientManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_admin_can_create_a_client_through_the_form_component(): void
    {
        Mail::fake();

        $this->actingAs($this->admin());

        Livewire::test('admin.clients.form')
            ->set('company_name', 'Acme Co')
            ->set('contact_person', 'Jamie Doe')
            ->set('email', 'jamie@acme.test')
            ->set('tagsInput', 'healthcare, retail')
            ->call('save');

        $this->assertDatabaseHas('clients', ['company_name' => 'Acme Co', 'email' => 'jamie@acme.test']);
    }

    public function test_admin_can_edit_an_existing_client(): void
    {
        $this->actingAs($this->admin());

        $client = Client::factory()->create(['company_name' => 'Old Name']);

        Livewire::test('admin.clients.form', ['client' => $client])
            ->set('company_name', 'New Name')
            ->call('save');

        $this->assertSame('New Name', $client->fresh()->company_name);
    }

    public function test_admin_can_suspend_and_reactivate_a_client_from_the_table(): void
    {
        $this->actingAs($this->admin());

        $client = Client::factory()->create(['status' => ClientStatus::Active]);

        Livewire::test('admin.clients.table')->call('suspend', $client->id);
        $this->assertSame(ClientStatus::Suspended, $client->fresh()->status);

        Livewire::test('admin.clients.table')->call('reactivate', $client->id);
        $this->assertSame(ClientStatus::Active, $client->fresh()->status);
    }

    public function test_suspended_client_cannot_log_in(): void
    {
        $client = Client::factory()->create(['status' => ClientStatus::Suspended]);
        $user = User::factory()->create(['role' => UserRole::Client, 'password' => 'correct-password']);
        $client->update(['user_id' => $user->id]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_soft_delete_a_client_from_the_table(): void
    {
        $this->actingAs($this->admin());

        $client = Client::factory()->create();

        Livewire::test('admin.clients.table')->call('delete', $client->id);

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }
}
