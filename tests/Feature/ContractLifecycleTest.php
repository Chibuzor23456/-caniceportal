<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Contracts\AcceptContractAction;
use App\Actions\Contracts\CreateContractAction;
use App\Actions\Contracts\RejectContractAction;
use App\Actions\Contracts\SendContractAction;
use App\Enums\ContractStatus;
use App\Enums\SignatureType;
use App\Enums\UserRole;
use App\Mail\ContractAcceptedMail;
use App\Mail\ContractSentMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'two_factor_confirmed_at' => now()]);
    }

    private function client(string $email = 'jamie@acme.test'): Client
    {
        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => $email,
        ]);

        $client->user->forceFill(['must_change_password' => false])->save();

        return $client;
    }

    public function test_creating_a_written_contract_generates_a_unique_reference_and_slug(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $client = $this->client();

        $contract = (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms...</p>');

        $this->assertMatchesRegularExpression('/^C-\d{4}-\d{4}$/', $contract->reference);
        $this->assertSame('acme-co-contract', $contract->slug);
        $this->assertSame(ContractStatus::Draft, $contract->status);
    }

    public function test_creating_a_contract_requires_exactly_one_of_body_or_file(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $client = $this->client();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        (new CreateContractAction)->handle($client, 'Bad Contract');
    }

    public function test_uploading_a_contract_file_skips_pdf_generation(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->actingAs($this->admin());
        $client = $this->client();

        $file = UploadedFile::fake()->create('contract.pdf', 200);
        $contract = (new CreateContractAction)->handle($client, 'Uploaded Agreement', file: $file);

        $this->assertTrue($contract->isUploaded());
        Storage::disk('local')->assertExists($contract->uploaded_file_path);
    }

    public function test_sending_a_contract_generates_a_secure_token_and_queues_the_email(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $client = $this->client();
        $contract = (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms...</p>');

        app(SendContractAction::class)->handle($contract);
        $contract->refresh();

        $this->assertSame(ContractStatus::Sent, $contract->status);
        $this->assertNotNull($contract->secure_token);
        Mail::assertQueued(ContractSentMail::class);
    }

    public function test_accepting_locks_the_contract_and_creates_a_signature(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $client = $this->client();
        $contract = (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms...</p>');
        app(SendContractAction::class)->handle($contract);
        $contract->refresh();

        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);
        $accepted = app(AcceptContractAction::class)->handle($contract, $request, 'Jamie Doe', SignatureType::Typed);

        $this->assertTrue($accepted);
        $contract->refresh();
        $this->assertSame(ContractStatus::Accepted, $contract->status);
        $this->assertSame(1, $contract->signatures()->count());
        Mail::assertQueued(ContractAcceptedMail::class, fn ($m) => ! $m->forAdmin);
        Mail::assertQueued(ContractAcceptedMail::class, fn ($m) => $m->forAdmin);
    }

    public function test_a_second_near_simultaneous_accept_is_blocked(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $client = $this->client();
        $contract = (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms...</p>');
        app(SendContractAction::class)->handle($contract);
        $contract->refresh();

        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);

        $first = app(AcceptContractAction::class)->handle($contract, $request, 'Jamie Doe', SignatureType::Typed);
        $second = app(AcceptContractAction::class)->handle($contract->fresh(), $request, 'Someone Else', SignatureType::Typed);

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(1, $contract->signatures()->count());
    }

    public function test_an_accepted_contracts_secure_link_never_renders_the_signature_form_again(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $client = $this->client();
        $contract = (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms...</p>');
        app(SendContractAction::class)->handle($contract);
        $contract->refresh();

        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);
        app(AcceptContractAction::class)->handle($contract, $request, 'Jamie Doe', SignatureType::Typed);

        auth()->logout();

        $response = $this->get(route('contract.secure', $contract->fresh()->secure_token));

        $response->assertOk();
        $response->assertDontSee('Draw your signature');
        $response->assertDontSee('signature_data', escape: false);
    }

    public function test_rejecting_captures_a_reason(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $client = $this->client();
        $contract = (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms...</p>');
        app(SendContractAction::class)->handle($contract);
        $contract->refresh();

        $rejected = (new RejectContractAction)->handle($contract, 'Terms need revision.');

        $this->assertTrue($rejected);
        $contract->refresh();
        $this->assertSame(ContractStatus::Rejected, $contract->status);
        $this->assertSame('Terms need revision.', $contract->rejection_reason);
    }

    public function test_a_client_cannot_view_another_clients_contract(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->actingAs($admin);

        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');

        $contractA = (new CreateContractAction)->handle($clientA, 'Service Agreement', body: '<p>Terms...</p>');
        app(SendContractAction::class)->handle($contractA);

        $this->actingAs($clientB->user);

        $response = $this->get(route('client.contracts.show', $contractA->fresh()));

        $response->assertNotFound();
    }
}
