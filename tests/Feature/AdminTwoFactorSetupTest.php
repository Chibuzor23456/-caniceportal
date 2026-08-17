<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminTwoFactorSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enable_and_confirm_two_factor_authentication(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        Livewire::test('admin.security.two-factor-setup')->call('enable');

        $admin->refresh();
        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);

        $validCode = (new Google2FA)->getCurrentOtp(
            Fortify::currentEncrypter()->decrypt($admin->two_factor_secret)
        );

        Livewire::test('admin.security.two-factor-setup')
            ->set('code', $validCode)
            ->call('confirm')
            ->assertHasNoErrors();

        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
    }
}
