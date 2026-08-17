<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Quotations\CreateQuotationAction;
use App\Actions\Quotations\SendQuotationAction;
use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Mail\MessageReceivedMail;
use App\Mail\NewClientAdminMail;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingNotificationsActivityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'two_factor_confirmed_at' => now()]);
    }

    private function client(string $email = 'jamie@acme.test'): Client
    {
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => $email,
        ]);

        $client->user->forceFill(['must_change_password' => false])->save();

        return $client;
    }

    public function test_creating_a_client_notifies_admins_by_email_and_in_app(): void
    {
        Mail::fake();

        $admin = $this->admin();

        (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);

        Mail::assertQueued(NewClientAdminMail::class);
        $this->assertSame(1, $admin->fresh()->notifications()->count());
        $this->assertSame(1, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_admin_sending_a_message_notifies_the_client_and_logs_activity_with_client_scope(): void
    {
        Storage::fake('r2');
        $admin = $this->admin();
        $client = $this->client();
        Mail::fake();

        $this->actingAs($admin);

        Livewire::test('messages.thread', ['client' => $client, 'role' => 'admin'])
            ->set('body', 'Hello from Canice')
            ->set('files', [UploadedFile::fake()->create('brief.pdf', 100)])
            ->call('send');

        $this->assertSame(1, $client->messages()->count());
        $message = $client->messages()->first();
        $this->assertSame('Hello from Canice', $message->body);
        $this->assertSame(1, $message->attachments()->count());

        Mail::assertQueued(MessageReceivedMail::class, fn ($m) => ! $m->forAdmin);
        $this->assertSame(1, $client->user->fresh()->unreadNotifications()->count());

        $activity = \App\Models\ActivityLog::where('action', 'message.sent')->first();
        $this->assertNotNull($activity);
        $this->assertSame($client->id, $activity->client_id);
    }

    public function test_opening_the_thread_marks_the_other_partys_messages_read(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $client = $this->client();

        $client->messages()->create(['sender_id' => $admin->id, 'body' => 'Hi there']);

        $this->actingAs($client->user);

        Livewire::test('messages.thread', ['client' => $client, 'role' => 'client']);

        $this->assertNotNull($client->messages()->first()->fresh()->read_at);
    }

    public function test_a_client_cannot_view_another_clients_message_thread(): void
    {
        Mail::fake();
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');

        $this->actingAs($clientB->user);

        $response = $this->get(route('client.messages.index'));
        $response->assertOk();

        // The client controller always resolves the authenticated client's
        // own thread - there's no route parameter to swap in another
        // client's id, so cross-client access has nothing to guess at.
        Livewire::test('messages.thread', ['client' => $clientA, 'role' => 'client'])
            ->assertForbidden();
    }

    public function test_admin_and_client_activity_feeds_are_correctly_scoped(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');

        $this->actingAs($admin);
        $quotation = (new CreateQuotationAction)->handle($clientA);
        (new SendQuotationAction)->handle($quotation->fresh());

        $adminResponse = $this->get(route('admin.activity.index'));
        $adminResponse->assertOk();
        $adminResponse->assertSee($quotation->reference);

        $this->actingAs($clientB->user);
        $clientBResponse = $this->get(route('client.activity.index'));
        $clientBResponse->assertOk();
        $clientBResponse->assertDontSee($quotation->reference);
    }

    public function test_sending_an_email_creates_an_email_log_entry(): void
    {
        // Exercises PHPMailerTransport::doSend() directly - the one
        // chokepoint every outgoing email passes through - rather than
        // routing through Laravel's Mail config layer, which is what
        // Section 12's Email Log is actually built into. Pointed at a
        // guaranteed-unreachable local port (never the real
        // smtp.hostinger.com from .env) so this fails fast with no real
        // network call, exercising the failure-logging branch.
        $transport = new \App\Mail\Transport\PHPMailerTransport([
            'host' => '127.0.0.1',
            'port' => 2525,
        ]);

        $email = (new \Symfony\Component\Mime\Email)
            ->from('noreply@canicetechnologies.com')
            ->to('client@example.test')
            ->subject('Test subject')
            ->text('Test body');

        try {
            $transport->send($email);
        } catch (\Throwable) {
            // Expected - nothing is listening on that port.
        }

        $this->assertSame(1, EmailLog::count());
        $this->assertSame(EmailStatus::Failed, EmailLog::first()->status);
        $this->assertSame('client@example.test', EmailLog::first()->recipient);
    }

    public function test_poll_bounces_action_no_ops_cleanly_without_imap_credentials(): void
    {
        config(['services.imap.host' => null]);

        app(\App\Actions\Email\PollBouncesAction::class)->handle();

        $this->assertTrue(true);
    }

    public function test_global_search_scopes_results_by_role(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');

        $this->actingAs($admin);
        $quotationA = (new CreateQuotationAction)->handle($clientA);

        $this->actingAs($admin);
        Livewire::test('shared.global-search')
            ->set('query', 'Acme')
            ->assertSee('Acme Co');

        $this->actingAs($clientB->user);

        // Not assertDontSee($quotationA->reference): the "No results for
        // ..." empty-state text legitimately echoes the raw search term
        // back regardless of whether anything matched, so assert the
        // empty-state text itself renders instead.
        Livewire::test('shared.global-search')
            ->set('query', $quotationA->reference)
            ->assertSee('No results for');
    }
}
