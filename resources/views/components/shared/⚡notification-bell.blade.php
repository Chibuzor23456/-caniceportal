<?php

use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $open = false;

    public function markRead(string $notificationId): void
    {
        auth()->user()->notifications()->where('id', $notificationId)->first()?->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function with(): array
    {
        return [
            'notifications' => auth()->user()->notifications()->latest()->limit(8)->get(),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ];
    }
};
?>

<div x-data="{ open: @entangle('open') }" class="relative" wire:poll.30s>
    <button type="button" @click="open = !open" class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-400 hover:bg-slate-50">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        @if ($unreadCount)
            <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-100 bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Notifications</p>
            @if ($unreadCount)
                <button type="button" wire:click="markAllRead" class="text-xs font-medium text-brand hover:text-brand-emphasis">Mark all read</button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse ($notifications as $notification)
                <a
                    href="{{ $notification->data['url'] ?? '#' }}"
                    wire:click="markRead('{{ $notification->id }}')"
                    class="block border-b border-slate-50 px-4 py-3 text-sm last:border-b-0 hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-blue-50/50' }}"
                >
                    <p class="font-medium text-slate-900">{{ $notification->data['title'] ?? '' }}</p>
                    <p class="mt-0.5 text-slate-500">{{ $notification->data['body'] ?? '' }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-slate-400">No notifications yet.</p>
            @endforelse
        </div>
    </div>
</div>
