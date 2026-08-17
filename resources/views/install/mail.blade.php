<x-layouts.bare title="Setup - Email">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="mb-8">
            <img src="{{ asset('images/brand/logo-full.png') }}" alt="Canice Technologies" class="h-9 w-auto">
        </div>

        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold tracking-wide text-brand uppercase">Step 2 of 3</p>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Email (SMTP)</h1>
            <p class="mt-1 text-sm text-slate-500">Used for quotations, invoices, and every other notification.</p>

            <form method="POST" action="{{ route('install.mail.save') }}" id="mail-form" class="mt-6 space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">SMTP Host</label>
                        <input type="text" name="host" value="{{ old('host', 'smtp.hostinger.com') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                        @error('host') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Port</label>
                        <input type="text" name="port" value="{{ old('port', '587') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                        @error('port') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Encryption</label>
                    <select name="encryption" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="tls" @selected(old('encryption', 'tls') === 'tls')>STARTTLS (587)</option>
                        <option value="ssl" @selected(old('encryption') === 'ssl')>SSL (465)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <x-ui.password-input id="mail-password" name="password" class="mt-1" />
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">From Address</label>
                        <input type="email" name="from_address" value="{{ old('from_address', 'noreply@canicetechnologies.com') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                        @error('from_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">From Name</label>
                        <input type="text" name="from_name" value="{{ old('from_name', 'Canice Technologies') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                        @error('from_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div id="test-result" class="hidden rounded-xl px-4 py-2 text-sm"></div>

                <div class="flex gap-2">
                    <button type="button" id="test-btn" class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Test Connection
                    </button>
                    <button type="submit" class="flex-1 rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                        Save &amp; Continue
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('test-btn').addEventListener('click', async function () {
            const btn = this;
            const result = document.getElementById('test-result');
            const form = document.getElementById('mail-form');
            const data = Object.fromEntries(new FormData(form).entries());

            btn.disabled = true;
            btn.textContent = 'Testing...';
            result.classList.add('hidden');

            try {
                const response = await fetch('{{ route('install.mail.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });
                const json = await response.json();

                result.textContent = json.message;
                result.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'bg-red-50', 'text-red-700');
                result.classList.add(...(json.success ? ['bg-emerald-50', 'text-emerald-700'] : ['bg-red-50', 'text-red-700']));
            } catch (e) {
                result.textContent = 'Request failed. Check your network connection.';
                result.classList.remove('hidden');
                result.classList.add('bg-red-50', 'text-red-700');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Test Connection';
            }
        });
    </script>
</x-layouts.bare>
