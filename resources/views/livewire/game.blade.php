<section class="flex min-h-screen items-center justify-center">
    @php
        $setsWonTeamA = is_numeric($gameState['sets_won_team_a'] ?? null) ? (int) $gameState['sets_won_team_a'] : 0;
        $setsWonTeamB = is_numeric($gameState['sets_won_team_b'] ?? null) ? (int) $gameState['sets_won_team_b'] : 0;
        $scoreTeamA = is_numeric($gameState['score_team_a'] ?? null) ? (int) $gameState['score_team_a'] : 0;
        $scoreTeamB = is_numeric($gameState['score_team_b'] ?? null) ? (int) $gameState['score_team_b'] : 0;
    @endphp

    <div id="game-canvas" class="relative flex h-[998px] w-[1536px] items-center justify-center bg-sky-100 border border-accent">
        <section
            data-scoreboard
            aria-label="Match score"
            class="absolute top-6 left-1/2 z-20 w-[300px] -translate-x-1/2 rounded-xl border border-slate-200 bg-white/95 px-6 py-4 shadow-sm backdrop-blur"
        >
            <div class="grid grid-cols-3 items-center text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">
                <span class="justify-self-start">Team A</span>
                <span class="justify-self-center">Sets</span>
                <span class="justify-self-end">Team B</span>
            </div>
            <div class="mt-1 grid grid-cols-3 items-center">
                <span data-scoreboard-sets-team-a class="justify-self-start text-4xl font-bold tabular-nums text-slate-900">{{ $setsWonTeamA }}</span>
                <span class="justify-self-center text-slate-400">:</span>
                <span data-scoreboard-sets-team-b class="justify-self-end text-4xl font-bold tabular-nums text-slate-900">{{ $setsWonTeamB }}</span>
            </div>

            <div class="mt-3 grid grid-cols-3 items-center border-t border-slate-200 pt-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">
                <span class="justify-self-start">Team A</span>
                <span class="justify-self-center">Points</span>
                <span class="justify-self-end">Team B</span>
            </div>
            <div class="mt-1 grid grid-cols-3 items-center">
                <span data-scoreboard-points-team-a class="justify-self-start text-3xl font-semibold tabular-nums text-slate-900">{{ $scoreTeamA }}</span>
                <span class="justify-self-center text-slate-400">:</span>
                <span data-scoreboard-points-team-b class="justify-self-end text-3xl font-semibold tabular-nums text-slate-900">{{ $scoreTeamB }}</span>
            </div>
        </section>

        <div class="flex h-full w-full flex-col items-center justify-center gap-6">
            <livewire:court :game-id="$gameId" :game-state="$gameState" />
        </div>
        <livewire:toss-result-submission :game-id="$gameId" :game-state="$gameState" />
    </div>
</section>
