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
            <div class="hidden w-40 flex-col gap-2 md:flex" data-misconduct-controls="left" data-misconduct-team="{{ $leftTeam->value }}">
                <flux:heading size="sm" class="text-center">Misconduct</flux:heading>

                <div class="grid w-fit grid-cols-2 gap-4 self-center">
                    @foreach ($misconductControls as $misconductControl)
                        @if ($misconductControl['sanction'] === 'warning' && $leftMinorMisconductDisabled)
                            <flux:button
                                type="button"
                                variant="outline"
                                square
                                disabled
                                aria-label="{{ $misconductControl['label'] }}"
                                data-misconduct-button="{{ $misconductControl['sanction'] }}"
                                data-misconduct-side="left"
                                data-misconduct-side-team="left-{{ $leftTeam->value }}"
                                class="relative size-12 opacity-60"
                            >
                                <img src="{{ $misconductControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                <span
                                    class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                    data-minor-misconduct-recorded-indicator="left-{{ $leftTeam->value }}"
                                    aria-hidden="true"
                                >
                                    <flux:icon.check class="size-3" />
                                </span>
                            </flux:button>
                        @else
                            <flux:button
                                type="button"
                                variant="outline"
                                square
                                aria-label="{{ $misconductControl['label'] }}"
                                wire:click="requestMisconduct('{{ $leftTeam->value }}', '{{ $misconductControl['sanction'] }}')"
                                data-misconduct-button="{{ $misconductControl['sanction'] }}"
                                data-misconduct-side="left"
                                data-misconduct-side-team="left-{{ $leftTeam->value }}"
                                class="size-12"
                            >
                                <img src="{{ $misconductControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                            </flux:button>
                        @endif
                    @endforeach
                </div>

                <div class="mt-10 flex flex-col gap-2" data-delay-controls="left" data-delay-team="{{ $leftTeam->value }}">
                    <flux:heading size="sm" class="text-center">Delay</flux:heading>

                    <div class="flex justify-center gap-4">
                        @foreach ($delayControls as $delayControl)
                            @if ($delayControl['sanction'] === 'delay-warning')
                                @if ($leftDelayWarningDisabled)
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        square
                                        disabled
                                        aria-label="{{ $delayControl['label'] }}"
                                        data-delay-button="{{ $delayControl['sanction'] }}"
                                        data-delay-side="left"
                                        data-delay-side-team="left-{{ $leftTeam->value }}"
                                        class="relative size-12 opacity-60"
                                    >
                                        <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                        <span
                                            class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                            data-delay-warning-recorded-indicator="left-{{ $leftTeam->value }}"
                                            aria-hidden="true"
                                        >
                                            <flux:icon.check class="size-3" />
                                        </span>
                                    </flux:button>
                                @else
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        square
                                        aria-label="{{ $delayControl['label'] }}"
                                        wire:click="requestDelayWarning('{{ $leftTeam->value }}')"
                                        data-delay-button="{{ $delayControl['sanction'] }}"
                                        data-delay-side="left"
                                        data-delay-side-team="left-{{ $leftTeam->value }}"
                                        class="size-12"
                                    >
                                        <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                    </flux:button>
                                @endif
                            @elseif ($leftDelayPenaltyDisabled)
                                <flux:button
                                    type="button"
                                    variant="outline"
                                    square
                                    disabled
                                    aria-label="{{ $delayControl['label'] }}"
                                    data-delay-button="{{ $delayControl['sanction'] }}"
                                    data-delay-side="left"
                                    data-delay-side-team="left-{{ $leftTeam->value }}"
                                    class="relative size-12"
                                >
                                    <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                    <span
                                        class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                        data-delay-penalty-locked-indicator="left-{{ $leftTeam->value }}"
                                        aria-hidden="true"
                                    >
                                        <flux:icon.lock-closed class="size-3" data-delay-penalty-locked-icon />
                                    </span>
                                </flux:button>
                            @else
                                <flux:button
                                    type="button"
                                    variant="outline"
                                    square
                                    aria-label="{{ $delayControl['label'] }}"
                                    wire:click="requestDelayPenalty('{{ $leftTeam->value }}')"
                                    data-delay-button="{{ $delayControl['sanction'] }}"
                                    data-delay-side="left"
                                    data-delay-side-team="left-{{ $leftTeam->value }}"
                                    class="size-12"
                                >
                                    <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                </flux:button>
                            @endif
                        @endforeach
                    </div>
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

            <div class="hidden w-40 flex-col gap-2 md:flex" data-misconduct-controls="right" data-misconduct-team="{{ $rightTeam->value }}">
                <flux:heading size="sm" class="text-center">Misconduct</flux:heading>

                <div class="grid w-fit grid-cols-2 gap-4 self-center">
                    @foreach ($misconductControls as $misconductControl)
                        @if ($misconductControl['sanction'] === 'warning' && $rightMinorMisconductDisabled)
                            <flux:button
                                type="button"
                                variant="outline"
                                square
                                disabled
                                aria-label="{{ $misconductControl['label'] }}"
                                data-misconduct-button="{{ $misconductControl['sanction'] }}"
                                data-misconduct-side="right"
                                data-misconduct-side-team="right-{{ $rightTeam->value }}"
                                class="relative size-12 opacity-60"
                            >
                                <img src="{{ $misconductControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                <span
                                    class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                    data-minor-misconduct-recorded-indicator="right-{{ $rightTeam->value }}"
                                    aria-hidden="true"
                                >
                                    <flux:icon.check class="size-3" />
                                </span>
                            </flux:button>
                        @else
                            <flux:button
                                type="button"
                                variant="outline"
                                square
                                aria-label="{{ $misconductControl['label'] }}"
                                wire:click="requestMisconduct('{{ $rightTeam->value }}', '{{ $misconductControl['sanction'] }}')"
                                data-misconduct-button="{{ $misconductControl['sanction'] }}"
                                data-misconduct-side="right"
                                data-misconduct-side-team="right-{{ $rightTeam->value }}"
                                class="size-12"
                            >
                                <img src="{{ $misconductControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                            </flux:button>
                        @endif
                    @endforeach
                </div>

                <div class="mt-10 flex flex-col gap-2" data-delay-controls="right" data-delay-team="{{ $rightTeam->value }}">
                    <flux:heading size="sm" class="text-center">Delay</flux:heading>

                    <div class="flex justify-center gap-4">
                        @foreach ($delayControls as $delayControl)
                            @if ($delayControl['sanction'] === 'delay-warning')
                                @if ($rightDelayWarningDisabled)
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        square
                                        disabled
                                        aria-label="{{ $delayControl['label'] }}"
                                        data-delay-button="{{ $delayControl['sanction'] }}"
                                        data-delay-side="right"
                                        data-delay-side-team="right-{{ $rightTeam->value }}"
                                        class="relative size-12 opacity-60"
                                    >
                                        <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                        <span
                                            class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                            data-delay-warning-recorded-indicator="right-{{ $rightTeam->value }}"
                                            aria-hidden="true"
                                        >
                                            <flux:icon.check class="size-3" />
                                        </span>
                                    </flux:button>
                                @else
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        square
                                        aria-label="{{ $delayControl['label'] }}"
                                        wire:click="requestDelayWarning('{{ $rightTeam->value }}')"
                                        data-delay-button="{{ $delayControl['sanction'] }}"
                                        data-delay-side="right"
                                        data-delay-side-team="right-{{ $rightTeam->value }}"
                                        class="size-12"
                                    >
                                        <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                    </flux:button>
                                @endif
                            @elseif ($rightDelayPenaltyDisabled)
                                <flux:button
                                    type="button"
                                    variant="outline"
                                    square
                                    disabled
                                    aria-label="{{ $delayControl['label'] }}"
                                    data-delay-button="{{ $delayControl['sanction'] }}"
                                    data-delay-side="right"
                                    data-delay-side-team="right-{{ $rightTeam->value }}"
                                    class="relative size-12"
                                >
                                    <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                    <span
                                        class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                        data-delay-penalty-locked-indicator="right-{{ $rightTeam->value }}"
                                        aria-hidden="true"
                                    >
                                        <flux:icon.lock-closed class="size-3" data-delay-penalty-locked-icon />
                                    </span>
                                </flux:button>
                            @else
                                <flux:button
                                    type="button"
                                    variant="outline"
                                    square
                                    aria-label="{{ $delayControl['label'] }}"
                                    wire:click="requestDelayPenalty('{{ $rightTeam->value }}')"
                                    data-delay-button="{{ $delayControl['sanction'] }}"
                                    data-delay-side="right"
                                    data-delay-side-team="right-{{ $rightTeam->value }}"
                                    class="size-12"
                                >
                                    <img src="{{ $delayControl['icon'] }}" alt="" class="h-8 max-w-8 object-contain" />
                                </flux:button>
                            @endif
                        @endforeach
                    </div>
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

    <flux:modal name="record-delay-warning-confirm" :dismissible="false" :closable="false" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Record delay warning?</flux:heading>
                <flux:text class="mt-2">
                    Confirm that you want to record a delay warning for this team.
                </flux:text>
            </div>

            <div class="mt-8 flex items-center gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="primary" wire:click="recordPendingDelayWarning">
                    Confirm
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="record-delay-penalty-confirm" :dismissible="false" :closable="false" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Record delay penalty?</flux:heading>
                <flux:text class="mt-2">
                    Confirm that you want to record a delay penalty for this team.
                </flux:text>
            </div>

            <div class="mt-8 flex items-center gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="primary" wire:click="recordPendingDelayPenalty">
                    Confirm
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="select-misconduct-subject" :dismissible="false" :closable="false" class="min-w-[26rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Select person</flux:heading>
                <flux:text class="mt-2">
                    Choose who should receive {{ $pendingMisconductSanctionLabel ? strtolower($pendingMisconductSanctionLabel) : 'this sanction' }}.
                </flux:text>
            </div>

            <div class="space-y-5">
                <div class="space-y-2">
                    <flux:heading size="sm">Players</flux:heading>
                    <div class="grid grid-cols-6 gap-2">
                        @forelse ($misconductSubjects['players'] as $subject)
                            <button
                                type="button"
                                aria-label="{{ $subject['marker'] }}"
                                @disabled($subject['unavailable'])
                                wire:click="selectMisconductSubject('{{ $subject['subject_type'] }}', {{ $subject['subject_id'] }})"
                                data-misconduct-subject-button="{{ $subject['subject_type'] }}-{{ $subject['subject_id'] }}"
                                class="relative flex size-10 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow disabled:cursor-default disabled:opacity-60 {{ $pendingMisconductTeam === \App\Enums\TeamAB::TeamA->value ? 'bg-blue-600' : 'bg-red-600' }}"
                            >
                                {{ $subject['marker'] }}
                                @if ($subject['unavailable'])
                                    <span
                                        class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                        data-misconduct-subject-unavailable-indicator="{{ $subject['subject_type'] }}-{{ $subject['subject_id'] }}"
                                        aria-hidden="true"
                                    >
                                        <img src="{{ $subject['unavailable_icon'] }}" alt="" class="h-4 max-w-4 object-contain" />
                                    </span>
                                @endif
                            </button>
                        @empty
                            <flux:text class="col-span-6">No players are rostered for this team.</flux:text>
                        @endforelse
                    </div>
                </div>

                <div class="space-y-2">
                    <flux:heading size="sm">Staff</flux:heading>
                    <div class="grid grid-cols-6 gap-2">
                        @forelse ($misconductSubjects['staff'] as $subject)
                            <flux:button
                                type="button"
                                variant="outline"
                                square
                                :disabled="$subject['unavailable']"
                                aria-label="{{ $subject['marker'] }}"
                                wire:click="selectMisconductSubject('{{ $subject['subject_type'] }}', {{ $subject['subject_id'] }})"
                                data-misconduct-subject-button="{{ $subject['subject_type'] }}-{{ $subject['subject_id'] }}"
                                class="relative size-10"
                            >
                                {{ $subject['marker'] }}
                                @if ($subject['unavailable'])
                                    <span
                                        class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full border border-white bg-zinc-900 text-white shadow"
                                        data-misconduct-subject-unavailable-indicator="{{ $subject['subject_type'] }}-{{ $subject['subject_id'] }}"
                                        aria-hidden="true"
                                    >
                                        <img src="{{ $subject['unavailable_icon'] }}" alt="" class="h-4 max-w-4 object-contain" />
                                    </span>
                                @endif
                            </flux:button>
                        @empty
                            <flux:text class="col-span-6">No staff are rostered for this team.</flux:text>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-8 flex items-center gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="record-misconduct-confirm" :dismissible="false" :closable="false" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Record misconduct?</flux:heading>
                <flux:text class="mt-2">
                    Confirm {{ $pendingMisconductSanctionLabel ? strtolower($pendingMisconductSanctionLabel) : 'this sanction' }} for {{ $pendingMisconductSubjectLabel ?? 'this person' }}.
                </flux:text>
            </div>

            <div class="mt-8 flex items-center gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="primary" wire:click="recordPendingMisconduct">
                    Confirm
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delay-sanction-recorded" :dismissible="false" :closable="false" class="min-w-[22rem] text-center">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $delaySanctionRecordedTitle }}</flux:heading>
                <flux:text class="mt-2">{{ $delaySanctionRecordedMessage }}</flux:text>
            </div>

            <div class="flex justify-center">
                <flux:button type="button" variant="primary" wire:click="dismissDelaySanctionRecordedMessage">
                    Dismiss
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
