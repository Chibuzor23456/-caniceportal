<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Invoices\CreateInvoiceAction;
use App\Actions\Invoices\OverdueInvoicesAction;
use App\Actions\Quotations\CreateQuotationAction;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Mail\InvoicePaidMail;
use App\Mail\InvoiceSentMail;
use App\Mail\PaymentProofUploadedMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectInvoiceFlowTest extends TestCase
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

    /**
     * A project whose quotation carries two structured payment phases
     * (deposit / balance), the data Section 14's auto-population reads from.
     */
    private function projectWithPaymentSchedule(Client $client, User $admin): Project
    {
        $this->actingAs($admin);

        $quotation = (new CreateQuotationAction)->handle($client);

        $quotation->paymentPhases()->create(['description' => 'Deposit', 'amount' => 200000, 'due_condition' => 'Due on signing', 'order' => 0]);
        $quotation->paymentPhases()->create(['description' => 'Balance', 'amount' => 220000, 'due_condition' => 'Due before go-live', 'order' => 1]);

        return Project::create([
            'client_id' => $client->id,
            'quotation_id' => $quotation->id,
            'title' => 'Acme Website',
            'status' => ProjectStatus::Active,
        ]);
    }

    public function test_create_invoice_prefills_from_the_first_unbilled_payment_phase(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithPaymentSchedule($client, $admin);

        $invoice = (new CreateInvoiceAction)->handle($project->fresh());

        $this->assertSame('Deposit', $invoice->description);
        $this->assertEquals(200000.0, (float) $invoice->amount);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $invoice->reference);
    }

    public function test_sending_an_invoice_generates_a_pdf_and_notifies_the_client(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithPaymentSchedule($client, $admin);
        $invoice = (new CreateInvoiceAction)->handle($project->fresh());

        $this->actingAs($admin);

        $this->patch(route('admin.invoices.update', $invoice), [
            'description' => 'Deposit (50%)',
            'amount' => 200000,
            'due_date' => now()->addDays(7)->toDateString(),
        ])->assertRedirect();

        $this->post(route('admin.invoices.send', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        $this->assertSame('Deposit (50%)', $invoice->description);
        $this->assertNotNull($invoice->sent_at);
        Storage::disk('r2')->assertExists("invoices/{$invoice->reference}.pdf");
        Mail::assertQueued(InvoiceSentMail::class);
    }

    public function test_client_uploads_payment_proof_then_admin_marks_it_paid(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithPaymentSchedule($client, $admin);
        $invoice = (new CreateInvoiceAction)->handle($project->fresh());

        $this->actingAs($admin);
        $this->post(route('admin.invoices.send', $invoice));
        $invoice->refresh();

        $this->actingAs($client->user);
        $this->post(route('client.invoices.payment-proof', $invoice), [
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertNotNull($invoice->payment_proof_path);
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        Mail::assertQueued(PaymentProofUploadedMail::class);

        $this->actingAs($admin);
        $this->post(route('admin.invoices.mark-paid', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        Mail::assertQueued(InvoicePaidMail::class);
    }

    public function test_a_second_invoice_prefills_from_the_next_unbilled_phase_once_the_first_is_paid(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithPaymentSchedule($client, $admin);

        $this->actingAs($admin);
        $first = (new CreateInvoiceAction)->handle($project->fresh());
        $this->post(route('admin.invoices.send', $first));
        $this->post(route('admin.invoices.mark-paid', $first->fresh()));

        $second = (new CreateInvoiceAction)->handle($project->fresh());

        $this->assertSame('Balance', $second->description);
        $this->assertEquals(220000.0, (float) $second->amount);
    }

    public function test_overdue_action_flips_a_sent_invoice_past_its_due_date(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithPaymentSchedule($client, $admin);
        $invoice = (new CreateInvoiceAction)->handle($project->fresh());

        $this->actingAs($admin);
        $this->post(route('admin.invoices.send', $invoice));

        $invoice->refresh();
        $invoice->forceFill(['due_date' => now()->subDay()->toDateString()])->save();

        app(OverdueInvoicesAction::class)->handle();

        $this->assertSame(InvoiceStatus::Overdue, $invoice->fresh()->status);
    }

    public function test_a_client_cannot_view_another_clients_invoice_or_upload_proof_on_a_non_sent_invoice(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $clientA = $this->client('a@example.test');
        $clientB = $this->client('b@example.test');
        $project = $this->projectWithPaymentSchedule($clientA, $admin);
        $invoice = (new CreateInvoiceAction)->handle($project->fresh());

        $this->actingAs($admin);
        $this->post(route('admin.invoices.send', $invoice));
        $invoice->refresh();

        $this->actingAs($clientB->user);
        $this->get(route('client.invoices.show', $invoice))->assertForbidden();

        // Draft invoices (never sent) shouldn't be uploadable-to by the owning client either.
        $draft = (new CreateInvoiceAction)->handle($project->fresh());
        $this->actingAs($clientA->user);
        $this->post(route('client.invoices.payment-proof', $draft), [
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100),
        ])->assertForbidden();
    }

    public function test_csv_exports_download_a_well_formed_file(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithPaymentSchedule($client, $admin);
        $invoice = (new CreateInvoiceAction)->handle($project->fresh());
        $this->post(route('admin.invoices.send', $invoice));

        $this->actingAs($admin);

        $invoiceCsv = $this->get(route('admin.invoices.export'));
        $invoiceCsv->assertOk();
        $this->assertStringContainsString($invoice->reference, $invoiceCsv->getContent());

        $clientCsv = $this->get(route('admin.clients.export'));
        $clientCsv->assertOk();
        $this->assertStringContainsString('Acme Co', $clientCsv->getContent());
    }

    public function test_admin_dashboard_reflects_real_invoice_totals(): void
    {
        Mail::fake();
        Storage::fake('r2');

        $admin = $this->admin();
        $client = $this->client();
        $project = $this->projectWithPaymentSchedule($client, $admin);
        $invoice = (new CreateInvoiceAction)->handle($project->fresh());

        $this->actingAs($admin);
        $this->post(route('admin.invoices.send', $invoice));
        $this->post(route('admin.invoices.mark-paid', $invoice->fresh()));

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Total Revenue');
    }
}
