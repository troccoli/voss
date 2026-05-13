<aside class="min-w-0">
    @if ($showPlayerPlaceholders)
        <div
            role="list"
            class="flex flex-nowrap items-center gap-2 overflow-x-auto pb-1"
        >
            @foreach (range(1, $placeholderCount) as $placeholderIndex)
                <flux:skeleton
                    data-team-roster-placeholder="{{ $placeholderIndex }}"
                    class="h-8 w-8 rounded-full border border-slate-300 bg-slate-300 shadow-sm"
                />
            @endforeach
        </div>
    @elseif ($players === [] && ! $hasRosterPlayers)
        <flux:text class="text-xs text-slate-500">No players available.</flux:text>
    @elseif ($players !== [])
        <div
            role="list"
            class="flex flex-nowrap items-center gap-2 overflow-x-auto pb-1"
        >
            @foreach (($reverseLayout ? array_reverse($players) : $players) as $player)
                <flux:badge
                    wire:key="{{ $keyPrefix }}-{{ $player['player_key'] }}"
                    data-team-roster-number="{{ $player['number'] }}"
                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow {{ $markerTone }}"
                >
                    {{ $player['number'] }}
                </flux:badge>
            @endforeach
        </div>
    @endif

    @if ($staffMarkers !== [])
        <div
            role="list"
            data-team-roster-staff-list
            @class([
                'mt-2 flex flex-nowrap items-center gap-2',
                'flex-row-reverse' => $reverseLayout,
            ])
        >
            @foreach ($staffMarkers as $staffMarker)
                <flux:badge
                    data-team-roster-staff-role="{{ $staffMarker['role_letter'] }}{{ $staffMarker['subscript'] ?? '' }}"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-semibold text-slate-800 shadow-sm"
                >
                    <span class="leading-none">
                        {{ $staffMarker['role_letter'] }}
                        @if (! is_null($staffMarker['subscript']))
                            <sub class="text-[9px] leading-none">{{ $staffMarker['subscript'] }}</sub>
                        @endif
                    </span>
                </flux:badge>
            @endforeach
        </div>
    @endif

    <div @class(['mt-4 flex gap-2', 'flex-row-reverse' => $reverseLayout])>
        @if ($canRequestTimeout)
            <div
                x-data="{
                    countdownOpen: false,
                    phase: 'countdown',
                    duration: {{ $timeoutDuration }},
                    seconds: {{ $timeoutDuration }},
                    timer: null,
                    start() {
                        this.phase = 'countdown';
                        this.seconds = this.duration;
                        this.timer = setInterval(() => {
                            if (--this.seconds <= 0) {
                                clearInterval(this.timer);
                                this.phase = 'timesup';
                            }
                        }, 1000);
                    },
                    dismiss() {
                        clearInterval(this.timer);
                        this.phase = 'countdown';
                        this.seconds = this.duration;
                        this.countdownOpen = false;
                        $flux.modal('request-timeout-{{ $team->value }}-countdown').close();
                    }
                }"
                x-on:timeout-recorded.window="
                    if ($event.detail.team === '{{ $team->value }}') {
                        $flux.modal('request-timeout-{{ $team->value }}-confirm').close();
                        $flux.modal('request-timeout-{{ $team->value }}-countdown').show();
                        countdownOpen = true;
                        $event.detail.hasTimeoutLeft ? start() : (phase = 'notimeout');
                    }
                "
                x-on:keydown.escape.window="if (countdownOpen) $event.preventDefault()"
            >
                <flux:modal.trigger name="request-timeout-{{ $team->value }}-confirm">
                    <flux:card
                        size="sm"
                        data-team-roster-timeouts
                        class="cursor-pointer p-1.5 content-center-safe! hover:bg-zinc-100 dark:hover:bg-zinc-700"
                    >
                        <flux:text>Timeouts</flux:text>
                        <flux:heading size="xl" class="text-center">{{ $timeoutsTaken }}/2</flux:heading>
                    </flux:card>
                </flux:modal.trigger>

                <flux:modal
                    name="request-timeout-{{ $team->value }}-confirm"
                    :dismissible="false"
                    :closable="false"
                    class="min-w-[20rem]"
                >
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Request Timeout</flux:heading>
                            <flux:text class="mt-2">Confirm that you want to request a timeout for this team.</flux:text>
                        </div>
                        @error('timeout')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                        <div class="mt-8 flex items-center gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="button" variant="primary" wire:click="requestTimeout">
                                Confirm
                            </flux:button>
                        </div>
                    </div>
                </flux:modal>

                <flux:modal
                    name="request-timeout-{{ $team->value }}-countdown"
                    :dismissible="false"
                    :closable="false"
                    class="min-w-[20rem]"
                >
                    <div x-show="phase === 'countdown'" class="space-y-4 text-center">
                        <flux:heading size="lg">Timeout in progress</flux:heading>
                        <flux:heading size="xl" x-text="seconds" class="text-5xl! tabular-nums"></flux:heading>
                        <flux:text>seconds remaining</flux:text>
                    </div>

                    <div x-show="phase === 'timesup'" class="space-y-6 text-center">
                        <flux:heading size="lg">Time's up!</flux:heading>
                        <div class="flex justify-center">
                            <flux:button type="button" variant="primary" @click="dismiss()">Return to play</flux:button>
                        </div>
                    </div>

                    <div x-show="phase === 'notimeout'" class="space-y-6 text-center">
                        <flux:heading size="lg">No timeout left</flux:heading>
                        <div class="flex justify-center">
                            <flux:button type="button" variant="primary" @click="dismiss()">Return to play</flux:button>
                        </div>
                    </div>
                </flux:modal>
            </div>
        @else
            <flux:card
                size="sm"
                data-team-roster-timeouts
                class="p-1.5 content-center-safe!"
            >
                <flux:text>Timeouts</flux:text>
                <flux:heading size="xl" class="text-center">{{ $timeoutsTaken }}/2</flux:heading>
            </flux:card>
        @endif

        @if ($canRequestSubstitution)
            <flux:card
                size="sm"
                data-team-roster-substitutions
                class="cursor-pointer p-1.5 content-center-safe! hover:bg-zinc-100 dark:hover:bg-zinc-700"
                @click="$flux.modal('substitution-{{ $team->value }}').show()"
            >
                <flux:text>Substitutions</flux:text>
                <flux:heading size="xl" class="text-center">{{ $substitutionsTaken }}/6</flux:heading>
            </flux:card>

            <flux:modal
                name="substitution-{{ $team->value }}"
                :dismissible="false"
                :closable="false"
                class="min-w-[20rem]"
                x-on:substitution-recorded.window="
                    if ($event.detail.team === '{{ $team->value }}') {
                        $flux.modal('substitution-{{ $team->value }}').close();
                    }
                "
            >
                <form wire:submit="submitSubstitution" class="space-y-5"
                    data-locked-numbers="{{ json_encode($lockedNumbers) }}"
                    data-partner-for="{{ json_encode($partnerFor) }}"
                    x-data="{
                        lockedNumbers: [],
                        partnerFor: {},
                        init() {
                            this.lockedNumbers = JSON.parse(this.$el.dataset.lockedNumbers);
                            this.partnerFor = JSON.parse(this.$el.dataset.partnerFor);
                        },
                        fillPlayerOut(number) {
                            if (this.lockedNumbers.includes(number)) return;
                            let input = $refs.playerOut;
                            input.value = number;
                            input.dispatchEvent(new Event('input'));
                            let partner = this.partnerFor[number];
                            if (partner !== undefined) {
                                let partnerInput = $refs.playerIn;
                                partnerInput.value = partner;
                                partnerInput.dispatchEvent(new Event('input'));
                            }
                        },
                        fillPlayerIn(number) {
                            if (this.lockedNumbers.includes(number)) return;
                            let input = $refs.playerIn;
                            input.value = number;
                            input.dispatchEvent(new Event('input'));
                            let partner = this.partnerFor[number];
                            if (partner !== undefined) {
                                let partnerInput = $refs.playerOut;
                                partnerInput.value = partner;
                                partnerInput.dispatchEvent(new Event('input'));
                            }
                        }
                    }"
                >
                    <flux:heading size="lg">Substitution</flux:heading>

                    @if ($onCourtNumbers !== [])
                        <div class="flex flex-wrap justify-center gap-2" data-substitution-on-court>
                            @foreach ($onCourtNumbers as $number)
                                @php $isLocked = in_array($number, $lockedNumbers); @endphp
                                <button
                                    type="button"
                                    data-substitution-on-court-number="{{ $number }}"
                                    @class([
                                        'flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow',
                                        $markerTone . ' cursor-pointer hover:opacity-80' => ! $isLocked,
                                        'cursor-not-allowed bg-slate-400 opacity-50' => $isLocked,
                                    ])
                                    @click="fillPlayerOut({{ $number }})"
                                    @disabled($isLocked)
                                >
                                    {{ $number }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <flux:input
                        label="Off"
                        label:class="mb-0!"
                        field:class="flex flex-col items-center"
                        wire:model="playerOut"
                        x-ref="playerOut"
                        name="playerOut"
                        class="h-12! w-12!"
                        data-substitution-player-out
                    />

                    <flux:input
                        label="On"
                        label:class="mb-0!"
                        field:class="flex flex-col items-center"
                        wire:model="playerIn"
                        x-ref="playerIn"
                        name="playerIn"
                        class="h-12! w-12!"
                        data-substitution-player-in
                    />

                    @if ($benchNumbers !== [])
                        <div class="flex flex-wrap justify-center gap-2" data-substitution-bench>
                            @foreach ($benchNumbers as $number)
                                @php $isLocked = in_array($number, $lockedNumbers); @endphp
                                <flux:badge
                                    data-substitution-bench-number="{{ $number }}"
                                    @class([
                                        'flex h-8 w-8 items-center justify-center rounded-full border text-sm font-semibold shadow-sm',
                                        'cursor-pointer border-slate-300 bg-white text-slate-800 hover:bg-slate-100' => ! $isLocked,
                                        'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-50' => $isLocked,
                                    ])
                                    @click="fillPlayerIn({{ $number }})"
                                >
                                    {{ $number }}
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    @error('substitution')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror

                    <div class="mt-8 flex items-center gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="submitSubstitution">
                            Confirm
                        </flux:button>
                    </div>
                </form>
            </flux:modal>
        @elseif ($canShowSubstitutionFullModal)
            <flux:card
                size="sm"
                data-team-roster-substitutions
                class="cursor-pointer p-1.5 content-center-safe! hover:bg-zinc-100 dark:hover:bg-zinc-700"
                @click="$flux.modal('substitution-full-confirm-{{ $team->value }}').show()"
            >
                <flux:text>Substitutions</flux:text>
                <flux:heading size="xl" class="text-center">{{ $substitutionsTaken }}/6</flux:heading>
            </flux:card>

            <flux:modal
                name="substitution-full-confirm-{{ $team->value }}"
                :dismissible="false"
                :closable="false"
                class="min-w-[20rem]"
            >
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Request Substitution</flux:heading>
                        <flux:text class="mt-2">Confirm that you want to request a substitution for this team.</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button
                            type="button"
                            variant="primary"
                            @click="$flux.modal('substitution-full-confirm-{{ $team->value }}').close(); $flux.modal('substitution-full-{{ $team->value }}').show()"
                        >
                            Confirm
                        </flux:button>
                    </div>
                </div>
            </flux:modal>

            <flux:modal
                name="substitution-full-{{ $team->value }}"
                :dismissible="false"
                :closable="false"
                class="min-w-[20rem]"
            >
                <div class="space-y-6 text-center">
                    <flux:heading size="lg">No substitutions left</flux:heading>
                    <div class="flex justify-center">
                        <flux:button
                            type="button"
                            variant="primary"
                            @click="$flux.modal('substitution-full-{{ $team->value }}').close()"
                        >
                            Return to play
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @else
            <flux:card
                size="sm"
                data-team-roster-substitutions
                class="p-1.5 content-center-safe!"
            >
                <flux:text>Substitutions</flux:text>
                <flux:heading size="xl" class="text-center">{{ $substitutionsTaken }}/6</flux:heading>
            </flux:card>
        @endif
    </div>
</aside>
