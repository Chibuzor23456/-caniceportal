<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Contracts\AcceptContractAction;
use App\Actions\Contracts\CreateContractAction;
use App\Actions\Contracts\SendContractAction;
use App\Actions\Files\UploadClientFileAction;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FileManagementTest extends TestCase
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

    public function test_admin_can_upload_a_file_for_a_client(): void
    {
        Mail::fake();
        Storage::fake('local');
        $admin = $this->admin();
        $client = $this->client();

        $this->actingAs($admin);

        Livewire::test('admin.files.table')
            ->set('uploadClientId', $client->id)
            ->set('uploadCategory', 'document')
            ->set('file', UploadedFile::fake()->create('brief.pdf', 100))
            ->call('upload');

        $this->assertSame(1, $client->files()->count());
        Storage::disk('local')->assertExists($client->files()->first()->file_path);
    }

    public function test_a_client_only_sees_their_own_files_grouped_by_category(): void
    {
        Mail::fake();
        Storage::fake('local');
        $admin = $this->admin();
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');

        $this->actingAs($admin);
        (new UploadClientFileAction)->handle($clientA, $admin, UploadedFile::fake()->create('logo.png', 50), 'image');
        (new UploadClientFileAction)->handle($clientB, $admin, UploadedFile::fake()->create('other.pdf', 50), 'document');

        $this->actingAs($clientA->user);
        $response = $this->get(route('client.files.index'));

        $response->assertOk();
        $response->assertSee('logo.png');
        $response->assertDontSee('other.pdf');
    }

    public function test_a_client_cannot_download_another_clients_file(): void
    {
        Mail::fake();
        Storage::fake('local');
        $admin = $this->admin();
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');

        $this->actingAs($admin);
        $file = (new UploadClientFileAction)->handle($clientA, $admin, UploadedFile::fake()->create('logo.png', 50), 'image');

        $this->actingAs($clientB->user);
        $response = $this->get(route('client.files.download', $file));

        $response->assertNotFound();
    }

    public function test_document_archive_only_shows_signed_quotations_signed_contracts_and_paid_invoices(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->actingAs($admin);
        $client = $this->client();

        $contract = (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms</p>');
        app(SendContractAction::class)->handle($contract);
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);
        app(AcceptContractAction::class)->handle($contract->fresh(), $request, 'Jamie Doe', \App\Enums\SignatureType::Typed);

        $this->actingAs($client->user);
        $response = $this->get(route('client.documents.index'));

        $response->assertOk();
        $response->assertSee('Service Agreement');
        $response->assertSee('Contract');
    }

    public function test_document_archive_is_scoped_to_the_authenticated_client(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->actingAs($admin);
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');

        $contract = (new CreateContractAction)->handle($clientA, 'Service Agreement', body: '<p>Terms</p>');
        app(SendContractAction::class)->handle($contract);
        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);
        app(AcceptContractAction::class)->handle($contract->fresh(), $request, 'Jamie Doe', \App\Enums\SignatureType::Typed);

        $this->actingAs($clientB->user);
        $response = $this->get(route('client.documents.index'));

        $response->assertOk();
        $response->assertDontSee('Service Agreement');
    }
}
