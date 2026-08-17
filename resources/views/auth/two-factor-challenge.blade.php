<x-layouts.bare title="Two-Factor Authentication">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12" x-data="{ useRecovery: false }">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Two-Factor Authentication</h1>
            <p class="mt-1 text-sm text-slate-500" x-show="!useRecovery" x-cloak>Enter the code from your authenticator app.</p>
            <p class="mt-1 text-sm text-slate-500" x-show="useRecovery" x-cloak>Enter one of your recovery codes.</p>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6 space-y-4">
                @csrf

                <div x-show="!useRecovery" x-cloak>
                    <label for="code" class="block text-sm font-medium text-slate-700">Code</label>
                    <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-center text-lg tracking-widest focus:border-slate-400 focus:outline-none">
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="useRecovery" x-cloak>
                    <label for="recovery_code" class="block text-sm font-medium text-slate-700">Recovery code</label>
                    <input id="recovery_code" type="text" name="recovery_code"
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('recovery_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Verify
                </button>
            </form>

            <button type="button" @click="useRecovery = !useRecovery" class="mt-4 text-xs font-medium text-slate-400 hover:text-slate-600">
                <span x-show="!useRecovery" x-cloak>Use a recovery code instead</span>
                <span x-show="useRecovery" x-cloak>Use an authentication code instead</span>
            </button>
        </div>
    </div>
</x-layouts.bare>
