<div>
    <section class="flex min-h-screen items-center justify-center px-3 py-4 sm:px-6 sm:py-8">
        <div
            id="game-canvas"
            class="relative flex min-h-[680px] w-full max-w-[1536px] items-center justify-center overflow-hidden rounded-2xl border border-accent bg-sky-100 sm:min-h-[760px] md:min-h-[840px] 2xl:min-h-[998px]"
        >
            <livewire:scoreboard :game-id="$gameId" :game-state="$gameState" />
            <livewire:start-set-submission :game-id="$gameId" :game-state="$gameState" />
            <livewire:rally-winner-controls :game-id="$gameId" :game-state="$gameState" />
            <livewire:court :game-id="$gameId" :game-state="$gameState" />
            <livewire:toss-result-submission :game-id="$gameId" :game-state="$gameState" />
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
</div>
