<x-layouts.client page-title="Files" title="Files">
    @forelse ($filesByCategory as $category => $files)
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">{{ \App\Enums\FileCategory::from($category)->label() }}</h3>
            <div class="mt-3 space-y-2">
                @foreach ($files as $file)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $file->original_filename }}</p>
                            <p class="text-xs text-slate-400">{{ $file->created_at->format('M j, Y') }}@if ($file->description) &middot; {{ $file->description }} @endif</p>
                        </div>
                        <a href="{{ route('client.files.download', $file) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200">Download</a>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white p-12 text-center shadow-sm">
            <p class="text-sm text-slate-400">No files have been shared with you yet.</p>
        </div>
    @endforelse
</x-layouts.client>
