<x-layouts.bare title="Reset Password">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Set a new password</h1>

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->query('email')) }}" required autofocus
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
                    <x-ui.password-input id="password" name="password" required class="mt-1" />
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
                    <x-ui.password-input id="password_confirmation" name="password_confirmation" required class="mt-1" />
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</x-layouts.bare>
