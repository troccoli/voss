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
        <span class="justify-self-start text-4xl font-bold tabular-nums text-slate-900">{{ $scoreboardState->setsWonTeamA }}</span>
        <span class="justify-self-center text-slate-400">:</span>
        <span class="justify-self-end text-4xl font-bold tabular-nums text-slate-900">{{ $scoreboardState->setsWonTeamB }}</span>
    </div>

    <div class="mt-3 grid grid-cols-3 items-center border-t border-slate-200 pt-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">
        <span class="justify-self-start">Team A</span>
        <span class="justify-self-center">Points</span>
        <span class="justify-self-end">Team B</span>
    </div>
    <div class="mt-1 grid grid-cols-3 items-center">
        <span class="justify-self-start text-3xl font-semibold tabular-nums text-slate-900">{{ $scoreboardState->scoreTeamA }}</span>
        <span class="justify-self-center text-slate-400">:</span>
        <span class="justify-self-end text-3xl font-semibold tabular-nums text-slate-900">{{ $scoreboardState->scoreTeamB }}</span>
    </div>
</section>
