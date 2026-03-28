<section class="flex min-h-screen items-center justify-center">
    <div id="game-canvas" class="relative flex h-[998px] w-[1536px] items-center justify-center bg-sky-100 border border-accent">
        <livewire:scoreboard :game-id="$gameId" :game-state="$gameState" />
        <livewire:start-set-submission :game-id="$gameId" :game-state="$gameState" />
        <livewire:court :game-id="$gameId" :game-state="$gameState" />
        <livewire:toss-result-submission :game-id="$gameId" :game-state="$gameState" />
    </div>
</section>
