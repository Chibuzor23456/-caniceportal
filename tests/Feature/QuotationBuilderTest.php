<?php

namespace Tests\Feature;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Quotations\CreateQuotationAction;
use App\Actions\Quotations\SendQuotationAction;
use App\Enums\SectionType;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class QuotationBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'two_factor_confirmed_at' => now()]);
    }

    private function client(string $email = 'jamie@acme.test'): Client
    {
        Mail::fake();

        return (new CreateClientAction)->handle([
            'company_name' => 'Acme Co',
            'contact_person' => 'Jamie Doe',
            'email' => $email,
        ]);
    }

    public function test_adding_sections_and_a_pricing_table_then_saving_persists_them_in_order(): void
    {
        $this->actingAs($this->admin());
        $client = $this->client();
        $quotation = (new CreateQuotationAction)->handle($client);

        Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::ProjectOverview->value)
            ->call('addSection', SectionType::PricingTable->value)
            ->assertSet('sections.0.type', SectionType::ProjectOverview->value)
            ->assertSet('sections.1.type', SectionType::PricingTable->value)
            ->set('sections.0.title', 'Overview')
            ->set('sections.0.body', '<p>Hello</p>')
            ->set('lineItems.0.service_name', 'Design Work')
            ->set('lineItems.0.quantity', 2)
            ->set('lineItems.0.unit_price', 1000)
            ->call('save');

        $quotation->refresh();
        $this->assertSame(2, $quotation->sections()->count());
        $this->assertSame('Overview', $quotation->sections()->orderBy('order')->first()->title);
        $this->assertSame(1, $quotation->lineItems()->count());
        $this->assertEquals(2000.0, (float) $quotation->lineItems()->first()->line_total);
    }

    public function test_only_one_pricing_table_section_can_exist(): void
    {
        $this->actingAs($this->admin());
        $quotation = (new CreateQuotationAction)->handle($this->client());

        Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::PricingTable->value)
            ->call('addSection', SectionType::PricingTable->value)
            ->assertCount('sections', 1);
    }

    public function test_removing_and_duplicating_a_section(): void
    {
        $this->actingAs($this->admin());
        $quotation = (new CreateQuotationAction)->handle($this->client());

        $component = Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::Objectives->value);

        $uid = $component->get('sections')[0]['uid'];

        $component->call('duplicateSection', $uid)
            ->assertCount('sections', 2);

        $component->call('removeSection', $uid)
            ->assertCount('sections', 1);
    }

    public function test_reordering_moves_a_section_to_the_requested_position(): void
    {
        $this->actingAs($this->admin());
        $quotation = (new CreateQuotationAction)->handle($this->client());

        $component = Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::ProjectOverview->value)
            ->call('addSection', SectionType::Objectives->value)
            ->call('addSection', SectionType::ScopeOfWork->value);

        $firstUid = $component->get('sections')[0]['uid'];

        // Move the first section (Project Overview) to the last position.
        $component->call('reorderSection', $firstUid, 2);

        $types = collect($component->get('sections'))->pluck('type')->all();

        $this->assertSame([
            SectionType::Objectives->value,
            SectionType::ScopeOfWork->value,
            SectionType::ProjectOverview->value,
        ], $types);
    }

    public function test_adding_payment_phases_persists_them_in_order_and_totals_correctly(): void
    {
        $this->actingAs($this->admin());
        $quotation = (new CreateQuotationAction)->handle($this->client());

        $component = Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::PaymentSchedule->value)
            ->set('paymentPhases.0.description', 'Deposit')
            ->set('paymentPhases.0.amount', 200000)
            ->set('paymentPhases.0.due_condition', 'Due on signing')
            ->call('addPaymentPhase')
            ->set('paymentPhases.1.description', 'Balance')
            ->set('paymentPhases.1.amount', 220000)
            ->set('paymentPhases.1.due_condition', 'Due before go-live')
            ->call('save');

        $quotation->refresh();
        $this->assertSame(2, $quotation->paymentPhases()->count());
        $this->assertSame('Deposit', $quotation->paymentPhases()->orderBy('order')->first()->description);
        $this->assertEquals(220000.0, (float) $quotation->paymentPhases()->orderBy('order')->get()->last()->amount);
        $this->assertEquals(420000.0, $quotation->paymentPhases()->sum('amount'));
    }

    public function test_only_one_payment_schedule_section_can_exist(): void
    {
        $this->actingAs($this->admin());
        $quotation = (new CreateQuotationAction)->handle($this->client());

        Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::PaymentSchedule->value)
            ->call('addSection', SectionType::PaymentSchedule->value)
            ->assertCount('sections', 1);
    }

    public function test_saving_as_template_and_loading_it_carries_payment_phases_over(): void
    {
        $this->actingAs($this->admin());
        $client = $this->client();
        $quotation = (new CreateQuotationAction)->handle($client);

        Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::PaymentSchedule->value)
            ->set('paymentPhases.0.description', 'Deposit')
            ->set('paymentPhases.0.amount', 100000)
            ->set('paymentPhases.0.due_condition', 'Due on signing')
            ->set('templateName', 'Retainer Template')
            ->call('saveAsTemplate');

        $template = \App\Models\QuotationTemplate::where('name', 'Retainer Template')->first();
        $this->assertSame(1, $template->paymentPhases()->count());
        $this->assertSame('Deposit', $template->paymentPhases()->first()->description);

        $newQuotation = (new CreateQuotationAction)->handle($this->client('second@acme.test'), $template);

        $this->assertSame(1, $newQuotation->paymentPhases()->count());
        $this->assertEquals(100000.0, (float) $newQuotation->paymentPhases()->first()->amount);
    }

    public function test_saving_a_revision_to_an_already_sent_quotation_snapshots_a_version_and_bumps_the_version_number(): void
    {
        $this->actingAs($this->admin());
        $quotation = (new CreateQuotationAction)->handle($this->client());
        $quotation->sections()->create(['type' => SectionType::ProjectOverview, 'title' => 'Overview', 'body' => '<p>v1</p>', 'order' => 0]);
        (new SendQuotationAction)->handle($quotation->fresh());
        $quotation->refresh();

        $this->assertSame(1, $quotation->version);

        Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->set('sections.0.title', 'Overview (revised)')
            ->call('save');

        $quotation->refresh();
        $this->assertSame(2, $quotation->version);
        $this->assertSame(1, $quotation->versions()->count());
        $this->assertSame(1, $quotation->versions()->first()->version_number);
    }

    public function test_save_as_template_copies_sections_and_line_items_and_tags_it_with_the_clients_tags(): void
    {
        $this->actingAs($this->admin());
        $client = $this->client();
        $client->tags()->attach(\App\Models\Tag::firstOrCreate(['name' => 'healthcare'])->id);

        $quotation = (new CreateQuotationAction)->handle($client->fresh());

        Livewire::test('admin.quotations.builder', ['quotation' => $quotation])
            ->call('addSection', SectionType::ProjectOverview->value)
            ->set('sections.0.body', '<p>Template body</p>')
            ->set('templateName', 'Healthcare Starter')
            ->call('saveAsTemplate');

        $template = \App\Models\QuotationTemplate::where('name', 'Healthcare Starter')->first();

        $this->assertNotNull($template);
        $this->assertSame(1, $template->sections()->count());
        $this->assertSame(['healthcare'], $template->tags->pluck('name')->all());
    }
}
