<x-layouts.bare title="Forgot Password">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Forgot your password?</h1>
            <p class="mt-1 text-sm text-slate-500">We'll email you a link to reset it.</p>

            @if (session('status'))
                <div class="mt-4 rounded-xl bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Send Reset Link
                </button>
            </form>
        </div>
    </div>
</x-layouts.bare>
