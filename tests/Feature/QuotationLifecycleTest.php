<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Quotations\AcceptQuotationAction;
use App\Actions\Quotations\CreateQuotationAction;
use App\Actions\Quotations\RejectQuotationAction;
use App\Actions\Quotations\SendQuotationAction;
use App\Enums\QuotationStatus;
use App\Enums\SectionType;
use App\Enums\SignatureType;
use App\Enums\UserRole;
use App\Mail\QuotationAcceptedMail;
use App\Mail\QuotationSentMail;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'two_factor_confirmed_at' => now()]);
    }

    private function quotationWithPricing(Client $client): Quotation
    {
        $quotation = (new CreateQuotationAction)->handle($client);

        $quotation->sections()->create([
            'type' => SectionType::PricingTable,
            'title' => 'Investment',
            'order' => 0,
        ]);

        $quotation->lineItems()->create([
            'service_name' => 'Website Design',
            'quantity' => 1,
            'unit_price' => 500000,
            'discount' => 0,
            'tax' => 0,
            'line_total' => 500000,
            'order' => 0,
        ]);

        return $quotation->fresh();
    }

    public function test_creating_a_quotation_generates_a_unique_reference_and_slug(): void
    {
        $this->actingAs($this->admin());
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);

        $quotation = (new CreateQuotationAction)->handle($client);

        $this->assertMatchesRegularExpression('/^Q-\d{4}-\d{4}$/', $quotation->reference);
        $this->assertSame('acme-co', $quotation->slug);
        $this->assertSame(QuotationStatus::Draft, $quotation->status);
    }

    public function test_sending_a_quotation_generates_a_secure_token_and_queues_the_email(): void
    {
        $this->actingAs($this->admin());
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);

        $quotation = $this->quotationWithPricing($client);
        (new SendQuotationAction)->handle($quotation);
        $quotation->refresh();

        $this->assertSame(QuotationStatus::Sent, $quotation->status);
        $this->assertNotNull($quotation->secure_token);
        $this->assertNotNull($quotation->expiry_date);

        Mail::assertQueued(QuotationSentMail::class);
    }

    public function test_the_public_secure_link_requires_no_login_and_records_a_view(): void
    {
        $this->actingAs($this->admin());
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);
        $quotation = $this->quotationWithPricing($client);
        (new SendQuotationAction)->handle($quotation);
        $quotation->refresh();

        auth()->logout();

        $response = $this->get(route('quotation.secure', $quotation->secure_token));

        $response->assertOk();
        $this->assertSame(QuotationStatus::Viewed, $quotation->fresh()->status);
        $this->assertNotNull($quotation->fresh()->viewed_at);
    }

    public function test_accepting_locks_the_quotation_creates_a_signature_and_a_project_stub(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);
        $quotation = $this->quotationWithPricing($client);
        (new SendQuotationAction)->handle($quotation);
        $quotation->refresh();

        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);
        $accepted = app(AcceptQuotationAction::class)->handle($quotation, $request, 'Jamie Doe', SignatureType::Typed);

        $this->assertTrue($accepted);

        $quotation->refresh();
        $this->assertSame(QuotationStatus::Accepted, $quotation->status);
        $this->assertNotNull($quotation->accepted_at);
        $this->assertSame(1, $quotation->signatures()->count());
        $this->assertNotNull($quotation->project);
        $this->assertSame($client->id, $quotation->project->client_id);

        Mail::assertQueued(QuotationAcceptedMail::class, fn ($m) => ! $m->forAdmin);
        Mail::assertQueued(QuotationAcceptedMail::class, fn ($m) => $m->forAdmin);
    }

    public function test_a_second_near_simultaneous_accept_is_blocked_and_never_creates_a_duplicate_signature(): void
    {
        $this->actingAs($this->admin());
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);
        $quotation = $this->quotationWithPricing($client);
        (new SendQuotationAction)->handle($quotation);
        $quotation->refresh();

        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);

        $first = app(AcceptQuotationAction::class)->handle($quotation, $request, 'Jamie Doe', SignatureType::Typed);
        $second = app(AcceptQuotationAction::class)->handle($quotation->fresh(), $request, 'Someone Else', SignatureType::Typed);

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(1, $quotation->signatures()->count());
        $this->assertSame('Jamie Doe', $quotation->signatures()->first()->signer_name);
    }

    public function test_an_accepted_quotations_secure_link_never_renders_the_signature_form_again(): void
    {
        $this->actingAs($this->admin());
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);
        $quotation = $this->quotationWithPricing($client);
        (new SendQuotationAction)->handle($quotation);
        $quotation->refresh();

        $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);
        app(AcceptQuotationAction::class)->handle($quotation, $request, 'Jamie Doe', SignatureType::Typed);

        auth()->logout();

        $response = $this->get(route('quotation.secure', $quotation->secure_token));

        $response->assertOk();
        $response->assertDontSee('Draw your signature');
        $response->assertDontSee('signature_data', escape: false);
    }

    public function test_rejecting_captures_a_reason_and_notifies_the_admin(): void
    {
        $this->actingAs($this->admin());
        Mail::fake();

        $client = (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => 'jamie@acme.test',
        ]);
        $quotation = $this->quotationWithPricing($client);
        (new SendQuotationAction)->handle($quotation);
        $quotation->refresh();

        $rejected = (new RejectQuotationAction)->handle($quotation, 'Budget does not fit right now.');

        $this->assertTrue($rejected);
        $quotation->refresh();
        $this->assertSame(QuotationStatus::Rejected, $quotation->status);
        $this->assertSame('Budget does not fit right now.', $quotation->rejection_reason);
    }

    public function test_a_client_cannot_view_another_clients_canonical_quotation_page(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->actingAs($admin);

        $clientA = (new CreateClientAction)->handle([
            'company_name' => 'Client A', 'contact_person' => 'A', 'email' => 'a@example.test',
        ]);
        $clientB = (new CreateClientAction)->handle([
            'company_name' => 'Client B', 'contact_person' => 'B', 'email' => 'b@example.test',
        ]);

        $quotationA = $this->quotationWithPricing($clientA);
        (new SendQuotationAction)->handle($quotationA);
        $quotationA->refresh();

        $this->actingAs($clientB->user);

        $response = $this->get(route('quotation.canonical', $quotationA->slug));

        $response->assertForbidden();
    }
}
