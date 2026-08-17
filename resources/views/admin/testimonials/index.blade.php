<x-layouts.admin page-title="Testimonials" title="Testimonials">
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[680px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Client</th>
                    <th class="px-5 py-3">Project</th>
                    <th class="px-5 py-3">Rating</th>
                    <th class="px-5 py-3">Comment</th>
                    <th class="px-5 py-3">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($testimonials as $testimonial)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $testimonial->client->company_name }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $testimonial->project->title }}</td>
                        <td class="px-5 py-3 text-amber-500">{{ str_repeat('★', $testimonial->rating).str_repeat('☆', 5 - $testimonial->rating) }}</td>
                        <td class="px-5 py-3 max-w-xs truncate text-slate-500">{{ $testimonial->comment ?: '-' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $testimonial->submitted_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No testimonials submitted yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $testimonials->links() }}
    </div>
</x-layouts.admin>
