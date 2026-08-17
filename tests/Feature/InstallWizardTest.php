<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        @unlink(storage_path('app/installed.lock'));

        parent::tearDown();
    }

    public function test_install_is_blocked_once_an_admin_account_already_exists(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);

        $this->get(route('install.database'))->assertRedirect(route('admin.login'));
    }

    public function test_install_is_blocked_by_the_lock_file_even_with_no_admin_account(): void
    {
        file_put_contents(storage_path('app/installed.lock'), now()->toDateTimeString());

        $this->get(route('install.database'))->assertRedirect(route('admin.login'));
    }

    public function test_install_is_reachable_with_no_admin_and_no_lock_file(): void
    {
        $this->get(route('install.database'))->assertOk()->assertSee('Step 1 of 3');
    }

    public function test_mail_and_admin_steps_redirect_back_when_earlier_steps_are_skipped(): void
    {
        $this->get(route('install.mail'))->assertRedirect(route('install.database'));
        $this->get(route('install.admin'))->assertRedirect(route('install.mail'));
    }

    public function test_database_test_connection_fails_gracefully_against_an_unreachable_host(): void
    {
        $response = $this->postJson(route('install.database.test'), [
            'host' => '127.0.0.1',
            'port' => '1',
            'database' => 'nonexistent',
            'username' => 'nobody',
            'password' => '',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => false]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_mail_test_connection_fails_gracefully_against_an_unreachable_host(): void
    {
        $response = $this->postJson(route('install.mail.test'), [
            'host' => '127.0.0.1',
            'port' => '1',
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'from_address' => 'noreply@example.test',
            'from_name' => 'Test',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => false]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_the_mail_and_admin_steps_complete_the_wizard_and_lock_it(): void
    {
        // The Database step itself is mysql-specific (the real deployment
        // target per PRD Section 3) - simulating it as already-done here
        // lets the rest of the flow exercise fully against the test
        // environment's already-migrated SQLite database, which is what
        // the later steps actually touch.
        $this->withSession(['install.db_done' => true]);

        $this->post(route('install.mail.save'), [
            'host' => 'smtp.example.test',
            'port' => '587',
            'encryption' => 'tls',
            'username' => 'user@example.test',
            'password' => 'secret',
            'from_address' => 'noreply@example.test',
            'from_name' => 'Test Co',
        ])->assertRedirect(route('install.admin'));

        // ForceInstallerRuntimeConfig pins the session driver to 'file' for
        // every /install/* request, so each sequential test call needs its
        // session state made explicit again rather than relying on cookie
        // continuity between separate $this->post() calls.
        $this->withSession(['install.db_done' => true, 'install.mail_done' => true]);

        $response = $this->post(route('install.admin.save'), [
            'name' => 'New Admin',
            'email' => 'newadmin@example.test',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ]);

        $response->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.test',
            'role' => UserRole::Admin->value,
        ]);
        $this->assertFileExists(storage_path('app/installed.lock'));

        // Now that it's installed, the wizard is unreachable again.
        $this->get(route('install.database'))->assertRedirect(route('admin.login'));
    }
}
