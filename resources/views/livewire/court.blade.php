<div class="grid grid-cols-1">
    <div class="flex w-[920px] items-start justify-between gap-4">
        <livewire:team-roster
            :game-id="$gameId"
            :team="$leftTeam"
            :left-side="true"
            :key="'team-roster-left'"
        />

        <section
            id="volleyball-court"
            aria-label="Volleyball court"
            class="relative h-[347px] w-[600px] bg-orange-300"
        >
            <div class="absolute inset-0 border-[4px] border-white"></div>
            <div class="absolute inset-y-[4px] left-1/2 w-[3px] -translate-x-1/2 bg-white"></div>
            <div class="absolute inset-y-[4px] left-1/3 w-[2px] -translate-x-1/2 bg-white"></div>
            <div class="absolute inset-y-[4px] left-2/3 w-[2px] -translate-x-1/2 bg-white"></div>
        </section>

        <livewire:team-roster
            :game-id="$gameId"
            :team="$rightTeam"
            :left-side="false"
            :key="'team-roster-right'"
        />
    </div>
    <div class="flex w-[530px] mt-4 mx-auto justify-between">
        <livewire:lineup-submission
            :team="\App\Enums\TeamAB::TeamA"
            :game-id="$gameId"
            :game-state="$gameState"
            :key="'lineup-submission-team-a'" />
        <livewire:lineup-submission
            :team="\App\Enums\TeamAB::TeamB"
            :game-id="$gameId"
            :game-state="$gameState"
            :key="'lineup-submission-team-b'" />
    </div>
</div>
