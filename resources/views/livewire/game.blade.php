<div>
    <section class="flex min-h-screen items-center justify-center px-3 py-4 sm:px-6 sm:py-8">
        <div
            id="game-canvas"
            class="relative flex min-h-[680px] w-full max-w-[1536px] items-center justify-center overflow-hidden rounded-2xl border border-accent bg-sky-100 sm:min-h-[760px] md:min-h-[840px] 2xl:min-h-[998px]"
        >
            @unless ($isBeforeInitialToss)
                <livewire:scoreboard :game-state="$gameState" />
            @endunless
            <livewire:start-set-submission :game-state="$gameState" />
            <livewire:court :game-state="$gameState" />
            <livewire:toss-result-submission :game-state="$gameState" />
        </div>
    </section>

    <flux:modal name="set-ended" class="min-w-80 text-center">
        <div class="space-y-4">
            <flux:heading size="xl">Set {{ $justEndedSetNumber }} finished</flux:heading>
            <flux:text class="text-lg font-semibold">
                {{ $setWinnerCode }} won
                {{ $finalScoreWinner }} &ndash; {{ $finalScoreLoser }}
            </flux:text>
            <div class="pt-2">
                <flux:button variant="primary" wire:click="acknowledgeSetEnd">Continue to next set</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal
        name="fifth-set-side-change"
        class="min-w-80 text-center"
        :dismissible="false"
        :closable="false"
        x-on:keydown.escape.window.capture.prevent.stop=""
        x-on:cancel.prevent.stop=""
    >
        <div class="space-y-4">
            <flux:heading size="xl">Teams to change court</flux:heading>
            <div class="pt-2">
                <flux:button variant="primary" wire:click="acknowledgeFifthSetSideChange">Dismiss</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
