<x-layouts.bare title="Set Your Password">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Set a permanent password</h1>
            <p class="mt-1 text-sm text-slate-500">You're signed in with a temporary password. Choose a new one to continue.</p>

            <form method="POST" action="{{ route('client.password.update') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700">Temporary password</label>
                    <x-ui.password-input id="current_password" name="current_password" required autofocus class="mt-1" />
                    @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
                    <x-ui.password-input id="password" name="password" required class="mt-1" />
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm new password</label>
                    <x-ui.password-input id="password_confirmation" name="password_confirmation" required class="mt-1" />
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Set Password & Continue
                </button>
            </form>
        </div>
    </div>
</x-layouts.bare>
