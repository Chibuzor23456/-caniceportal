<x-dynamic-component :component="$layout" :page-title="$label">
    <div class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-24 text-center shadow-sm">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6">
                <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $label }} is coming in a later phase</h2>
        <p class="mt-1 max-w-sm text-sm text-slate-500">
            This area of the portal is part of the build roadmap and isn't active yet. It's here now so the navigation is complete and testable.
        </p>
    </div>
</x-dynamic-component>
