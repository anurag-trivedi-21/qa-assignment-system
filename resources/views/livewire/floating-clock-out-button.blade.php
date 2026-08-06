@php($user = auth()->user())

<div>
    @if ($user?->is_tester && $user->isClockedIn())
        <button
            type="button"
            wire:click="clockOut"
            wire:loading.attr="disabled"
            class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 rounded-full bg-danger-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-danger-500 focus:outline-none focus:ring-2 focus:ring-danger-500 focus:ring-offset-2 disabled:opacity-75"
        >
            <x-heroicon-o-arrow-left-end-on-rectangle class="h-5 w-5" />
            Clock Out
        </button>
    @endif
</div>
