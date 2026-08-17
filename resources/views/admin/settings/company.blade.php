<x-layouts.admin page-title="Company Settings" title="Company Settings">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="w-full rounded-2xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">
            This signature is applied automatically to every generated quotation PDF (Section 10). The full Settings module (SMTP, email templates, notification preferences) is part of a later phase; this page covers only what the Quotation module needs today.
        </p>

        <form method="POST" action="{{ route('admin.settings.company.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Signatory Name</label>
                    <input type="text" name="signatory_name" value="{{ old('signatory_name', $settings->signatory_name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('signatory_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Position</label>
                    <input type="text" name="signatory_position" value="{{ old('signatory_position', $settings->signatory_position) }}" placeholder="Founder & CEO" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    @error('signatory_position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Default Currency</label>
                    <select name="default_currency" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        @foreach (['NGN', 'USD', 'GBP', 'EUR'] as $currency)
                            <option value="{{ $currency }}" @selected(old('default_currency', $settings->default_currency) === $currency)>{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Payment / Bank Details</label>
                <textarea name="bank_details" rows="3" placeholder="Bank name, account name, account number..." class="mt-1 w-full max-w-2xl rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">{{ old('bank_details', $settings->bank_details) }}</textarea>
                <p class="mt-1 text-xs text-slate-400">Applied automatically to every generated invoice PDF (Section 14).</p>
                @error('bank_details') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Signature Image</label>
                @if ($signatureUrl)
                    <img src="{{ $signatureUrl }}" alt="Current signature" class="mt-2 h-16">
                @elseif ($settings->signature_image_path)
                    <p class="mt-2 text-xs text-orange-500">A signature is saved, but couldn't be loaded from storage right now.</p>
                @endif
                <input type="file" name="signature_image" accept="image/*" class="mt-2 w-full max-w-sm rounded-xl border border-slate-200 px-3 py-2 text-sm">
                @error('signature_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                Save Settings
            </button>
        </form>
    </div>
</x-layouts.admin>
