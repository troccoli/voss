<section class="flex min-h-screen items-center justify-center">
    <div id="game-canvas" class="relative flex h-[998px] w-[1536px] items-center justify-center bg-sky-100 border border-accent">
        <div class="flex h-full w-full flex-col items-center justify-center gap-6">
            <livewire:court :game-id="$gameId" :game-state="$gameState" />
        </div>
        <livewire:toss-result-submission :game-id="$gameId" :game-state="$gameState" />
    </div>
</section>
