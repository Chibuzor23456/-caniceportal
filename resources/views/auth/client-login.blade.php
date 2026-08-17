<x-layouts.bare title="Client Login">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="mb-8">
            <img src="{{ asset('images/brand/logo-full.png') }}" alt="Canice Technologies" class="h-9 w-auto">
        </div>

        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Client Login</h1>
            <p class="mt-1 text-sm text-slate-500">Sign in to view your quotations, projects, and invoices.</p>

            @if (session('status'))
                <div class="mt-4 rounded-xl bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <x-ui.password-input id="password" name="password" required class="mt-1" />
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-500">
                        <input type="checkbox" name="remember" class="rounded border-slate-300">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="font-medium text-brand hover:text-brand-emphasis">Forgot password?</a>
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Log In
                </button>
            </form>
        </div>

        <a href="{{ route('admin.login') }}" class="mt-6 text-xs text-slate-400 hover:text-slate-600">Administrator login &rarr;</a>
    </div>
</x-layouts.bare>
