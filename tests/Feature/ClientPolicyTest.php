<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_can_view_their_own_record_but_not_another_clients(): void
    {
        Mail::fake();

        $clientA = (new CreateClientAction)->handle([
            'company_name' => 'Client A',
            'contact_person' => 'A Person',
            'email' => 'a@example.test',
        ]);

        $clientB = (new CreateClientAction)->handle([
            'company_name' => 'Client B',
            'contact_person' => 'B Person',
            'email' => 'b@example.test',
        ]);

        $this->assertTrue($clientA->user->can('view', $clientA));
        $this->assertFalse($clientA->user->can('view', $clientB));
    }

    public function test_admin_can_view_and_manage_any_client(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = Client::factory()->create();

        $this->assertTrue($admin->can('view', $client));
        $this->assertTrue($admin->can('update', $client));
        $this->assertTrue($admin->can('delete', $client));
    }
}
