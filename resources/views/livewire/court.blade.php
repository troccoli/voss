<div class="grid grid-cols-1">
    @php
        $leftCourtPositionClasses = [
            1 => 'left-[12%] bottom-[14%]',
            2 => 'left-[38%] bottom-[14%]',
            3 => 'left-[38%] top-1/2 -translate-y-1/2',
            4 => 'left-[38%] top-[14%]',
            5 => 'left-[12%] top-[14%]',
            6 => 'left-[12%] top-1/2 -translate-y-1/2',
        ];

        $rightCourtPositionClasses = [
            1 => 'right-[12%] top-[14%]',
            2 => 'right-[38%] top-[14%]',
            3 => 'right-[38%] top-1/2 -translate-y-1/2',
            4 => 'right-[38%] bottom-[14%]',
            5 => 'right-[12%] bottom-[14%]',
            6 => 'right-[12%] top-1/2 -translate-y-1/2',
        ];

        $leftMarkerTone = $leftTeam === \App\Enums\TeamAB::TeamA ? 'bg-blue-600' : 'bg-red-600';
        $rightMarkerTone = $rightTeam === \App\Enums\TeamAB::TeamA ? 'bg-blue-600' : 'bg-red-600';
    @endphp

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

            @foreach ($leftRotation as $position => $number)
                <div
                    data-court-marker="left-{{ $leftTeam->value }}-{{ $position }}"
                    data-court-side="left"
                    data-court-position="{{ $position }}"
                    data-court-team="{{ $leftTeam->value }}"
                    class="absolute {{ $leftCourtPositionClasses[$position] ?? '' }} flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow {{ $leftMarkerTone }}"
                >
                    {{ $number }}
                </div>
            @endforeach

            @foreach ($rightRotation as $position => $number)
                <div
                    data-court-marker="right-{{ $rightTeam->value }}-{{ $position }}"
                    data-court-side="right"
                    data-court-position="{{ $position }}"
                    data-court-team="{{ $rightTeam->value }}"
                    class="absolute {{ $rightCourtPositionClasses[$position] ?? '' }} flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow {{ $rightMarkerTone }}"
                >
                    {{ $number }}
                </div>
            @endforeach
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
