<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Contracts\CreateContractAction;
use App\Actions\Files\UploadClientFileAction;
use App\Actions\Invoices\CreateInvoiceAction;
use App\Actions\Quotations\CreateQuotationAction;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtendedAnalyticsTest extends TestCase
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

    private function projectWithPaidInvoice(Client $client, User $admin): Project
    {
        $this->actingAs($admin);

        $quotation = (new CreateQuotationAction)->handle($client);
        $quotation->forceFill(['status' => QuotationStatus::Accepted])->save();

        $project = Project::create([
            'client_id' => $client->id,
            'quotation_id' => $quotation->id,
            'title' => 'Acme Website',
            'status' => ProjectStatus::Completed,
        ]);

        $invoice = (new CreateInvoiceAction)->handle($project);
        $invoice->forceFill(['status' => InvoiceStatus::Paid, 'amount' => 100000, 'paid_at' => now()])->save();

        return $project;
    }

    public function test_admin_dashboard_shows_extended_analytics_widgets(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $this->projectWithPaidInvoice($client, $admin);

        $this->actingAs($admin);
        (new UploadClientFileAction)->handle($client, $admin, UploadedFile::fake()->create('brief.pdf', 50), 'document');

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Completed Projects');
        $response->assertSee('Files Uploaded');
        $response->assertSee('Conversion Rate');
        $response->assertSee('Revenue Overview');
        $response->assertSee('Top Clients by Revenue');
        $response->assertSee('Acme Co');
        $response->assertSee('Projects by Status');
        $response->assertSee('Client Growth');
        $response->assertSee('Payment Collection Rate');
        $response->assertSee('Smart Insights');
        $response->assertSee('Recent Client Messages');
        $response->assertSee('Email Delivery Health');
    }

    public function test_conversion_rate_and_payment_collection_rate_compute_correctly(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = $this->client();
        $this->actingAs($admin);

        $quotation = (new CreateQuotationAction)->handle($client);
        $quotation->forceFill(['status' => QuotationStatus::Accepted, 'sent_at' => now()])->save();

        $notAccepted = (new CreateQuotationAction)->handle($this->client('other@example.test'));
        $notAccepted->forceFill(['status' => QuotationStatus::Sent, 'sent_at' => now()])->save();

        $project = Project::create([
            'client_id' => $client->id,
            'quotation_id' => $quotation->id,
            'title' => 'Acme Website',
            'status' => ProjectStatus::Active,
        ]);

        $paidInvoice = (new CreateInvoiceAction)->handle($project);
        $paidInvoice->forceFill(['status' => InvoiceStatus::Paid, 'amount' => 80000, 'paid_at' => now()])->save();

        $sentInvoice = Invoice::create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'reference' => 'INV-2026-9999',
            'status' => InvoiceStatus::Sent,
            'description' => 'Balance',
            'amount' => 20000,
            'currency' => 'NGN',
            'issue_date' => now()->toDateString(),
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        // 1 of 2 sent quotations accepted -> 50%; 80000 of 100000 invoiced paid -> 80%.
        $response->assertSee('50%');
        $response->assertSee('80%');
    }

    public function test_client_dashboard_shows_payment_and_quotation_history_contract_status_and_files(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $this->projectWithPaidInvoice($client, $admin);

        $this->actingAs($admin);
        (new UploadClientFileAction)->handle($client, $admin, UploadedFile::fake()->create('logo.png', 20), 'image');
        (new CreateContractAction)->handle($client, 'Service Agreement', body: '<p>Terms</p>');

        $this->actingAs($client->user);
        $response = $this->get(route('client.dashboard'));

        $response->assertOk();
        $response->assertSee('Payment History');
        $response->assertSee('Quotation History');
        $response->assertSee('Contract Status');
        $response->assertSee('Service Agreement');
        $response->assertSee('Shared Files by Category');
        $response->assertSee('Image');
        $response->assertSee('Smart Insights');
    }
}
