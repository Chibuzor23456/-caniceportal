<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\GenericNotification;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'two_factor_confirmed_at' => now()]);
    }

    public function test_admin_can_update_company_settings_including_timezone(): void
    {
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.settings.company.update'), [
            'signatory_name' => 'Jane Doe',
            'signatory_position' => 'CEO',
            'default_currency' => 'USD',
            'timezone' => 'Europe/London',
            'bank_details' => 'Test Bank',
        ]);

        $response->assertRedirect(route('admin.settings.company'));
        $this->assertSame('Europe/London', CompanySetting::current()->timezone);
    }

    public function test_smtp_test_connection_fails_gracefully_against_an_unreachable_host(): void
    {
        $this->actingAs($this->admin());

        $response = $this->postJson(route('admin.settings.smtp.test'), [
            'host' => '127.0.0.1',
            'port' => '1',
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => false]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_admin_can_view_smtp_and_notification_settings_pages(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.settings.smtp'))->assertOk();
        $this->get(route('admin.settings.notifications'))->assertOk()->assertSee('Quotations');
    }

    public function test_disabling_a_notification_category_suppresses_only_that_categorys_bell_notification(): void
    {
        $admin = $this->admin();
        $admin->update(['notification_preferences' => ['client' => false]]);

        $disabled = new GenericNotification(title: 'New client', body: 'x', url: null, type: 'client');
        $this->assertSame([], $disabled->via($admin));

        $unaffected = new GenericNotification(title: 'New quotation', body: 'x', url: null, type: 'quotation');
        $this->assertSame(['database'], $unaffected->via($admin));

        $untyped = new GenericNotification(title: 'Deploy finished', body: 'x');
        $this->assertSame(['database'], $untyped->via($admin));
    }

    public function test_seeder_populates_a_row_per_mailable_type_with_null_body(): void
    {
        (new EmailTemplateSeeder)->run();

        $this->assertSame(22, EmailTemplate::count());
        $this->assertTrue(EmailTemplate::where('body', '!=', null)->doesntExist());
    }

    public function test_an_unedited_template_sends_the_exact_same_subject_as_before_the_retrofit(): void
    {
        (new EmailTemplateSeeder)->run();

        $client = Client::factory()->create();
        $mail = new ClientWelcomeMail($client, 'temp-pass-123');
        $mail->render();

        $this->assertSame('Welcome to the Canice Technologies Client Portal', $mail->subject);
    }

    public function test_editing_a_templates_body_switches_that_email_to_the_generic_wrapper(): void
    {
        (new EmailTemplateSeeder)->run();

        $template = EmailTemplate::where('type', 'client_welcome')->firstOrFail();

        $this->actingAs($this->admin());

        $this->patch(route('admin.settings.email-templates.update', $template), [
            'subject' => 'Hey {{ client_name }}, welcome aboard!',
            'body' => 'Hi **{{ client_name }}**, your temporary password is {{ temporary_password }}.',
        ])->assertRedirect();

        $client = Client::factory()->create(['contact_person' => 'Jamie Doe']);
        $mail = new ClientWelcomeMail($client, 'temp-pass-123');
        $rendered = $mail->render();

        $this->assertSame('Hey Jamie Doe, welcome aboard!', $mail->subject);
        $this->assertStringContainsString('temp-pass-123', $rendered);
    }
}
