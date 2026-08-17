<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Component;

new class extends Component
{
    public string $code = '';

    public bool $justConfirmed = false;

    public function enable(EnableTwoFactorAuthentication $enable): void
    {
        $enable(Auth::user());
    }

    public function confirm(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->validate(['code' => ['required', 'string']]);

        $confirm(Auth::user(), $this->code);

        $this->code = '';
        $this->justConfirmed = true;
    }

    public function finish(): void
    {
        $this->redirect(route('admin.dashboard'), navigate: false);
    }

    public function with(): array
    {
        /** @var User $user */
        $user = Auth::user()->fresh();

        return [
            'enabled' => ! is_null($user->two_factor_secret),
            'confirmed' => ! is_null($user->two_factor_confirmed_at),
            'qrCodeSvg' => $user->two_factor_secret ? $user->twoFactorQrCodeSvg() : null,
            'recoveryCodes' => $user->two_factor_confirmed_at ? $user->recoveryCodes() : [],
        ];
    }
};
?>

<div class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-6 py-12">
    <div class="rounded-2xl bg-white p-8 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">Set up two-factor authentication</h1>
        <p class="mt-2 text-sm text-slate-500">
            Required for every admin account: this portal holds signed legal agreements and client data, so a password alone isn't enough.
        </p>

        @if ($justConfirmed)
            <div class="mt-6 rounded-xl bg-emerald-50 p-4">
                <p class="text-sm font-medium text-emerald-800">Two-factor authentication is enabled.</p>
                <p class="mt-1 text-sm text-emerald-700">Save these recovery codes somewhere safe. Each one can be used once if you lose access to your authenticator app.</p>
            </div>

            <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm text-slate-700">
                @foreach ($recoveryCodes as $recoveryCode)
                    <li class="rounded-lg bg-slate-50 px-3 py-2">{{ $recoveryCode }}</li>
                @endforeach
            </ul>

            <button
                type="button"
                wire:click="finish"
                class="mt-6 w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis"
            >
                Continue to Dashboard
            </button>
        @elseif ($enabled)
            <div class="mt-6 flex justify-center rounded-xl bg-slate-50 p-6">
                {!! $qrCodeSvg !!}
            </div>

            <p class="mt-4 text-sm text-slate-500">
                Scan this with an authenticator app (Google Authenticator, Authy, 1Password, etc.), then enter the 6-digit code it shows.
            </p>

            <form wire:submit="confirm" class="mt-4">
                <label for="code" class="sr-only">Authentication code</label>
                <input
                    id="code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    wire:model="code"
                    placeholder="123456"
                    class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-center text-lg tracking-widest focus:border-slate-400 focus:outline-none"
                >
                @error('code') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                <button
                    type="submit"
                    class="mt-4 w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis"
                >
                    Confirm & Enable
                </button>
            </form>
        @else
            <button
                type="button"
                wire:click="enable"
                class="mt-6 w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis"
            >
                Start Setup
            </button>
        @endif
    </div>
</div>
