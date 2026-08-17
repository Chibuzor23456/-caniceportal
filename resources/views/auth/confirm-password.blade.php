<x-layouts.bare title="Confirm Password">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Confirm your password</h1>
            <p class="mt-1 text-sm text-slate-500">This is a sensitive action, please re-enter your password to continue.</p>

            <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <x-ui.password-input id="password" name="password" required autofocus class="mt-1" />
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Confirm
                </button>
            </form>
        </div>
    </div>
</x-layouts.bare>
