<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Enums\UserRole;
use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_client_generates_credentials_queues_a_welcome_email_and_logs_the_event(): void
    {
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Bright Path Consulting',
            'contact_person' => 'Jordan Blake',
            'email' => 'jordan@brightpath.test',
            'tags' => ['healthcare', 'retail'],
        ]);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertNotNull($client->user_id);
        $this->assertSame(UserRole::Client, $client->user->role);
        $this->assertTrue($client->user->must_change_password);
        $this->assertEqualsCanonicalizing(['healthcare', 'retail'], $client->tags->pluck('name')->all());

        Mail::assertQueued(ClientWelcomeMail::class, fn ($mail) => $mail->client->is($client));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'client.created',
            'subject_type' => Client::class,
            'subject_id' => $client->id,
        ]);
    }

    public function test_the_generated_temporary_password_actually_logs_the_client_in(): void
    {
        Mail::fake();

        // Capture the temporary password by intercepting the queued mail.
        $client = (new CreateClientAction)->handle([
            'company_name' => 'Bright Path Consulting',
            'contact_person' => 'Jordan Blake',
            'email' => 'jordan@brightpath.test',
        ]);

        Mail::assertQueued(ClientWelcomeMail::class, function (ClientWelcomeMail $mail) {
            $response = $this->post(route('login.store'), [
                'email' => $mail->client->email,
                'password' => $mail->temporaryPassword,
            ]);

            $response->assertRedirect(route('client.password.change'));

            return true;
        });
    }
}
