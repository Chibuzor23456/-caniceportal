@props([
    'title' => null,
    'workspaceName',
    'workspaceSubtitle',
    'navGroups',
    'logoutRoute',
    'pageTitle' => null,
    'freshness' => null,
])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('layouts.partials.head', ['title' => $title])
</head>
<body class="h-full bg-canvas font-sans text-slate-900 antialiased">
    <div
        x-data="{ sidebarCollapsed: false, mobileOpen: false, searchOpen: false }"
        @keydown.window="if (($event.metaKey || $event.ctrlKey) && $event.key === 'k') { $event.preventDefault(); searchOpen = true }"
        @keydown.window.escape="mobileOpen = false"
        class="flex h-full w-full max-w-full overflow-x-hidden"
    >
        {{-- Mobile/tablet backdrop: sidebar stays off-canvas until opened (Section 6.2) --}}
        <div x-show="mobileOpen" x-cloak x-transition.opacity @click="mobileOpen = false" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

        {{-- Sidebar (Section 6.2) --}}
        <aside
            :class="[
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
            class="fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 flex-col border-r border-slate-100 bg-white transition-all duration-200 lg:static lg:z-auto lg:translate-x-0"
        >
            <div class="flex items-center gap-3 px-5 py-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-navy p-1.5">
                    <img src="{{ asset('images/brand/logo-icon.png') }}" alt="Canice Technologies" class="h-full w-full object-contain">
                </div>
                <div x-show="!sidebarCollapsed" x-cloak class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $workspaceName }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $workspaceSubtitle }}</p>
                </div>
                <button type="button" @click="mobileOpen = false" class="shrink-0 rounded-lg p-1 text-slate-400 hover:bg-slate-50 lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-5 w-5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-5" x-show="!sidebarCollapsed" x-cloak>
                <button
                    type="button"
                    @click="searchOpen = true"
                    class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm text-slate-400 hover:border-slate-300"
                >
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                        Search&hellip;
                    </span>
                    <kbd class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400">&#8984;K</kbd>
                </button>
            </div>

            <nav class="mt-6 flex-1 space-y-6 overflow-y-auto px-3 pb-6">
                @foreach ($navGroups as $group)
                    <div>
                        <p x-show="!sidebarCollapsed" x-cloak class="px-2 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                            {{ $group['label'] }}
                        </p>
                        <div class="mt-2 space-y-1">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="{{ $item['url'] }}"
                                    class="group relative flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors {{ $item['active'] ? 'bg-brand text-white' : 'text-slate-600 hover:bg-slate-50' }}"
                                >
                                    <span class="flex items-center gap-3 truncate">
                                        <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                        <span x-show="!sidebarCollapsed" x-cloak class="truncate">{{ $item['label'] }}</span>
                                    </span>
                                    @if (!empty($item['badge']))
                                        <span x-show="!sidebarCollapsed" x-cloak class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $item['active'] ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $item['badge'] }}
                                        </span>
                                    @endif

                                    {{-- Collapsed-sidebar hover tooltip --}}
                                    <span
                                        x-show="sidebarCollapsed"
                                        x-cloak
                                        class="pointer-events-none absolute left-full top-1/2 z-30 ml-3 -translate-y-1/2 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-medium whitespace-nowrap text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100"
                                    >
                                        {{ $item['label'] }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="hidden border-t border-slate-100 p-3 lg:block">
                <button
                    type="button"
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="flex w-full items-center justify-center rounded-xl px-3 py-2 text-xs font-medium text-slate-400 hover:bg-slate-50"
                >
                    <span x-show="!sidebarCollapsed" x-cloak>Collapse</span>
                    <span x-show="sidebarCollapsed" x-cloak>&rarr;</span>
                </button>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between gap-3 border-b border-slate-100 bg-white px-4 py-4 sm:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" @click="mobileOpen = true" class="shrink-0 rounded-lg p-1.5 text-slate-500 hover:bg-slate-50 lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-5 w-5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold text-slate-900 sm:text-2xl">{{ $pageTitle }}</h1>
                        @if ($freshness)
                            <p class="mt-0.5 truncate text-xs text-slate-400">{{ $freshness }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <livewire:shared.notification-bell />

                    <form method="POST" action="{{ $logoutRoute }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            Log out
                        </button>
                    </form>
                </div>
            </header>

            <main class="w-full max-w-full flex-1 overflow-x-hidden overflow-y-auto px-4 py-6 sm:px-8">
                {{ $slot }}
            </main>
        </div>

        {{-- Global search overlay (Section 6.7) --}}
        <div x-show="searchOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/40 px-4 pt-24" @click.self="searchOpen = false">
            <div class="w-full max-w-xl" @click.outside="searchOpen = false">
                <livewire:shared.global-search />
            </div>
        </div>
    </div>
</body>
</html>
