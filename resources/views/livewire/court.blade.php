<div class="grid h-full w-full grid-cols-1 content-center justify-items-center">
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

        $leftOutsidePositionClasses = [
            1 => '-left-10 bottom-[14%]',
        ];

        $rightOutsidePositionClasses = [
            1 => '-right-10 top-[14%]',
        ];

        $leftMarkerTone = $leftTeam === \App\Enums\TeamAB::TeamA ? 'bg-blue-600' : 'bg-red-600';
        $rightMarkerTone = $rightTeam === \App\Enums\TeamAB::TeamA ? 'bg-blue-600' : 'bg-red-600';
    @endphp

    <div class="flex items-start gap-16">
        <livewire:team-roster
            :game-id="$gameId"
            :team="$leftTeam"
            :left-side="true"
            :key="'team-roster-left'"
        />

        <div class="flex flex-col items-center">
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
                    @php
                        $isServingPlayer = $position === 1 && $servingTeam === $leftTeam;
                        $markerPositionClass = $isServingPlayer
                            ? ($leftOutsidePositionClasses[$position] ?? $leftCourtPositionClasses[$position] ?? '')
                            : ($leftCourtPositionClasses[$position] ?? '');
                    @endphp
                    <div
                        data-court-marker="left-{{ $leftTeam->value }}-{{ $position }}"
                        data-court-side="left"
                        data-court-position="{{ $position }}"
                        data-court-team="{{ $leftTeam->value }}"
                        data-court-serving-player="{{ (int) $isServingPlayer }}"
                        class="absolute {{ $markerPositionClass }} flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow {{ $leftMarkerTone }}"
                    >
                        {{ $number }}
                    </div>
                @endforeach

                @foreach ($rightRotation as $position => $number)
                    @php
                        $isServingPlayer = $position === 1 && $servingTeam === $rightTeam;
                        $markerPositionClass = $isServingPlayer
                            ? ($rightOutsidePositionClasses[$position] ?? $rightCourtPositionClasses[$position] ?? '')
                            : ($rightCourtPositionClasses[$position] ?? '');
                    @endphp
                    <div
                        data-court-marker="right-{{ $rightTeam->value }}-{{ $position }}"
                        data-court-side="right"
                        data-court-position="{{ $position }}"
                        data-court-team="{{ $rightTeam->value }}"
                        data-court-serving-player="{{ (int) $isServingPlayer }}"
                        class="absolute {{ $markerPositionClass }} flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow {{ $rightMarkerTone }}"
                    >
                        {{ $number }}
                    </div>
                @endforeach
            </section>

            @if ($canRecordRallyWinner)
                <div class="mt-2 flex w-full justify-between px-2">
                    <button
                        type="button"
                        data-rally-winner-button="team_a"
                        wire:click="recordRallyWinner('{{ \App\Enums\TeamAB::TeamA->value }}')"
                        wire:loading.attr="disabled"
                        wire:target="recordRallyWinner"
                        class="rounded-md bg-blue-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        Winner
                    </button>

                    <button
                        type="button"
                        data-rally-winner-button="team_b"
                        wire:click="recordRallyWinner('{{ \App\Enums\TeamAB::TeamB->value }}')"
                        wire:loading.attr="disabled"
                        wire:target="recordRallyWinner"
                        class="rounded-md bg-red-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
                    >
                        Winner
                    </button>
                </div>
            @endif

            @error('rallyWinner')
                <flux:text class="mt-2 text-center text-red-600">{{ $message }}</flux:text>
            @enderror
        </div>

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
            :key="'lineup-submission-team-b'"
        />
    </div>
</div>
