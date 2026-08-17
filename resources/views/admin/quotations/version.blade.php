<x-layouts.admin :page-title="$quotation->reference.' - Version '.$version->version_number" :title="'Version '.$version->version_number">
    <div class="mb-4">
        <a href="{{ route('admin.quotations.show', $quotation) }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">&larr; Back to current version</a>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Snapshot captured {{ $version->created_at->format('M j, Y \a\t g:i A') }}</p>

        @foreach ($version->snapshot['sections'] ?? [] as $section)
            <h3 class="mt-6 text-sm font-semibold tracking-wide text-slate-900 uppercase first:mt-0">{{ $section['title'] }}</h3>
            @if ($section['type'] === 'pricing_table')
                <p class="mt-2 text-xs text-slate-400">Pricing table (see line items below)</p>
            @else
                <div class="prose prose-sm mt-3 max-w-none text-slate-700">{!! $section['body'] !!}</div>
            @endif
        @endforeach

        @if (! empty($version->snapshot['lineItems']))
            <h3 class="mt-6 text-sm font-semibold tracking-wide text-slate-900 uppercase">Pricing (at this version)</h3>
            <table class="mt-3 w-full text-left text-sm">
                <thead class="border-b border-slate-100 text-xs text-slate-400 uppercase">
                    <tr><th class="py-2">Service</th><th class="py-2">Qty</th><th class="py-2">Unit Price</th><th class="py-2">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($version->snapshot['lineItems'] as $item)
                        <tr>
                            <td class="py-2">{{ $item['service_name'] }}</td>
                            <td class="py-2">{{ $item['quantity'] }}</td>
                            <td class="py-2">{{ number_format($item['unit_price'], 2) }}</td>
                            <td class="py-2">{{ number_format($item['line_total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-layouts.admin>
