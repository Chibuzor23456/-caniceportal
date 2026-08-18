<x-layouts.bare title="Setup - Admin Account">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="mb-8">
            <img src="{{ asset('images/brand/logo-full.png') }}" alt="Canice Technologies" class="h-9 w-auto">
        </div>

        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold tracking-wide text-brand uppercase">Step 3 of 3</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Admin Account</h1>
            <p class="mt-1 text-sm text-slate-500">This account manages the whole portal.</p>

            <form method="POST" action="{{ route('install.admin.save') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <x-ui.password-input id="admin-password" name="password" required class="mt-1" />
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                    <x-ui.password-input id="admin-password-confirmation" name="password_confirmation" required class="mt-1" />
                </div>

                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Finish Setup
                </button>
            </form>
        </div>
    </div>
</x-layouts.bare>
