<x-layouts.bare title="Verify Email">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Verify your email</h1>
            <p class="mt-2 text-sm text-slate-500">
                We sent a verification link to your email address. Click it to confirm your account.
            </p>

            @if (session('status') === 'verification-link-sent')
                <div class="mt-4 rounded-xl bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
                    A new verification link has been sent.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-xs font-medium text-slate-400 hover:text-slate-600">Log out</button>
            </form>
        </div>
    </div>
</x-layouts.bare>
