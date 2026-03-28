<section class="flex min-h-screen items-center justify-center px-3 py-4 sm:px-6 sm:py-8">
    <div
        id="game-canvas"
        class="relative flex min-h-[680px] w-full max-w-[1536px] items-center justify-center overflow-hidden rounded-2xl border border-accent bg-sky-100 sm:min-h-[760px] md:min-h-[840px] 2xl:min-h-[998px]"
    >
        <livewire:scoreboard :game-id="$gameId" :game-state="$gameState" />
        <livewire:start-set-submission :game-id="$gameId" :game-state="$gameState" />
        <livewire:court :game-id="$gameId" :game-state="$gameState" />
        <livewire:toss-result-submission :game-id="$gameId" :game-state="$gameState" />
    </div>
</section>
