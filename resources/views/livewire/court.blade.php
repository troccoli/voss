<div data-court-layout="anchored" class="absolute inset-x-0 top-[300px] z-10 grid w-full grid-cols-1 justify-items-center">
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

        $misconductControls = [
            ['sanction' => 'warning', 'label' => 'Minor misconduct', 'icon' => asset('icons/yellow-card.svg')],
            ['sanction' => 'penalty', 'label' => 'Penalty', 'icon' => asset('icons/red-card.svg')],
            ['sanction' => 'expulsion', 'label' => 'Expulsion', 'icon' => asset('icons/yellow-red-card.svg')],
            ['sanction' => 'disqualification', 'label' => 'Disqualification', 'icon' => asset('icons/yellow-red-side-by-side-card.svg')],
        ];

        $delayControls = [
            ['sanction' => 'delay-warning', 'label' => 'Delay warning', 'icon' => asset('icons/yellow-card.svg')],
            ['sanction' => 'delay-penalty', 'label' => 'Delay penalty', 'icon' => asset('icons/red-card.svg')],
        ];
    @endphp

    <div class="flex flex-col items-center">
        <div class="flex items-start justify-center gap-[12rem] lg:gap-[14rem]">
            <div class="hidden w-40 flex-col gap-1 md:flex" data-misconduct-controls="left" data-misconduct-team="{{ $leftTeam->value }}">
                @foreach ($misconductControls as $misconductControl)
                    <flux:button
                        type="button"
                        variant="ghost"
                        data-misconduct-button="{{ $misconductControl['sanction'] }}"
                        data-misconduct-side="left"
                        data-misconduct-side-team="left-{{ $leftTeam->value }}"
                        class="h-auto justify-start px-3 py-2"
                    >
                        <span class="grid w-full grid-cols-[2rem_1fr] items-center gap-2">
                            <img src="{{ $misconductControl['icon'] }}" alt="" class="mx-auto h-8 max-w-8 object-contain" />
                            <span class="text-left text-xs leading-tight">{{ $misconductControl['label'] }}</span>
                        </span>
                    </flux:button>
                @endforeach

                <div class="mt-10 flex flex-col gap-1" data-delay-controls="left" data-delay-team="{{ $leftTeam->value }}">
                    @foreach ($delayControls as $delayControl)
                        <flux:button
                            type="button"
                            variant="ghost"
                            data-delay-button="{{ $delayControl['sanction'] }}"
                            data-delay-side="left"
                            data-delay-side-team="left-{{ $leftTeam->value }}"
                            class="h-auto justify-start px-3 py-2"
                        >
                            <span class="grid w-full grid-cols-[2rem_1fr] items-center gap-2">
                                <img src="{{ $delayControl['icon'] }}" alt="" class="mx-auto h-8 max-w-8 object-contain" />
                                <span class="text-left text-xs leading-tight">{{ $delayControl['label'] }}</span>
                            </span>
                        </flux:button>
                    @endforeach
                </div>
            </div>

            <section
                id="volleyball-court"
                aria-label="Volleyball court"
                class="relative h-[220px] w-[360px] bg-orange-300 sm:h-[280px] sm:w-[480px] md:h-[347px] md:w-[600px]"
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

            <div class="hidden w-40 flex-col gap-1 md:flex" data-misconduct-controls="right" data-misconduct-team="{{ $rightTeam->value }}">
                @foreach ($misconductControls as $misconductControl)
                    <flux:button
                        type="button"
                        variant="ghost"
                        data-misconduct-button="{{ $misconductControl['sanction'] }}"
                        data-misconduct-side="right"
                        data-misconduct-side-team="right-{{ $rightTeam->value }}"
                        class="h-auto justify-start px-3 py-2"
                    >
                        <span class="grid w-full grid-cols-[2rem_1fr] items-center gap-2">
                            <img src="{{ $misconductControl['icon'] }}" alt="" class="mx-auto h-8 max-w-8 object-contain" />
                            <span class="text-left text-xs leading-tight">{{ $misconductControl['label'] }}</span>
                        </span>
                    </flux:button>
                @endforeach

                <div class="mt-10 flex flex-col gap-1" data-delay-controls="right" data-delay-team="{{ $rightTeam->value }}">
                    @foreach ($delayControls as $delayControl)
                        <flux:button
                            type="button"
                            variant="ghost"
                            data-delay-button="{{ $delayControl['sanction'] }}"
                            data-delay-side="right"
                            data-delay-side-team="right-{{ $rightTeam->value }}"
                            class="h-auto justify-start px-3 py-2"
                        >
                            <span class="grid w-full grid-cols-[2rem_1fr] items-center gap-2">
                                <img src="{{ $delayControl['icon'] }}" alt="" class="mx-auto h-8 max-w-8 object-contain" />
                                <span class="text-left text-xs leading-tight">{{ $delayControl['label'] }}</span>
                            </span>
                        </flux:button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-start gap-4">
            <div class="flex w-24 shrink-0 items-start justify-end sm:w-28">
                <livewire:lineup-submission
                    :team="$leftTeam"
                    :game-id="$gameId"
                    :game-state="$gameState"
                    court-side="left"
                    :key="'lineup-submission-left-'.$leftTeam->value"
                />
                <livewire:rally-winner-controls
                    :game-id="$gameId"
                    :game-state="$gameState"
                    side="left"
                    :key="'rally-winner-left'"
                />
            </div>

            @if ($showRosters)
                <div class="flex w-[360px] shrink-0 gap-4 sm:w-[480px] sm:gap-10 md:w-[600px] md:gap-16">
                    <div class="flex min-w-0 flex-1 justify-end">
                        <livewire:team-roster
                            :game-id="$gameId"
                            :game-state="$gameState"
                            :team="$leftTeam"
                            :left-side="true"
                            :key="'team-roster-left'"
                        />
                    </div>

                    <div class="flex min-w-0 flex-1 justify-start">
                        <livewire:team-roster
                            :game-id="$gameId"
                            :game-state="$gameState"
                            :team="$rightTeam"
                            :left-side="false"
                            :key="'team-roster-right'"
                        />
                    </div>
                </div>
            @else
                <div class="w-[360px] shrink-0 sm:w-[480px] md:w-[600px]"></div>
            @endif

            <div class="flex w-24 shrink-0 items-start justify-start sm:w-28">
                <livewire:lineup-submission
                    :team="$rightTeam"
                    :game-id="$gameId"
                    :game-state="$gameState"
                    court-side="right"
                    :key="'lineup-submission-right-'.$rightTeam->value"
                />
                <livewire:rally-winner-controls
                    :game-id="$gameId"
                    :game-state="$gameState"
                    side="right"
                    :key="'rally-winner-right'"
                />
            </div>
        </div>
    </div>
</div>
