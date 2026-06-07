<div>
    @if ($isBeforeInitialToss && ! $hasSubmittedRosters)
        <div class="absolute bottom-4 right-4 z-20 sm:bottom-6 sm:right-6">
            <flux:button type="button" variant="primary" wire:click="openRosterModal" data-submit-roster-button>
                Submit rosters
            </flux:button>
        </div>

        <flux:modal name="submit-rosters" class="w-full max-w-5xl">
            <form wire:submit="submitRosters" class="space-y-6">
                <div>
                    <flux:heading size="lg">Submit rosters</flux:heading>
                    <flux:text class="mt-2">
                        Enter each selected player's roster number, choose one captain, and tick the libero checkbox where needed. Check the staff who will take part in the game.
                    </flux:text>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <section class="space-y-4">
                        <div class="space-y-1">
                            <flux:heading size="sm">Home Team</flux:heading>
                            <flux:text>{{ $homeTeamCode }}</flux:text>
                        </div>

                        <div class="space-y-3">
                            <flux:heading size="xs">Players</flux:heading>
                            <div class="grid grid-cols-[minmax(0,1fr)_5.5rem_3.75rem_4.5rem] items-center gap-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                <span>Name</span>
                                <span class="text-center">Captain?</span>
                                <span>Number</span>
                                <span class="text-center">Libero?</span>
                            </div>
                            <div class="space-y-3" data-home-roster-players>
                                @forelse ($homePlayers as $player)
                                    <div class="grid grid-cols-[minmax(0,1fr)_5.5rem_3.75rem_4.5rem] items-center gap-3" data-home-roster-player="{{ $player->getKey() }}">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-zinc-900">{{ $player->first_name }} {{ $player->last_name }}</div>
                                        </div>
                                        <div class="flex justify-center">
                                            <input
                                                type="radio"
                                                wire:model="homeCaptainSelection"
                                                value="{{ $player->getKey() }}"
                                                name="home-captain-selection"
                                                class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-500"
                                            />
                                        </div>
                                        <div>
                                            <flux:input
                                                wire:model="homeRosterInputs.{{ $player->getKey() }}"
                                                :invalid="$errors->has('homeRosterInputs.'.$player->getKey())"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="flex justify-center">
                                            <flux:checkbox wire:model="homeLiberoSelection.{{ $player->getKey() }}" />
                                        </div>
                                    </div>
                                    @error('homeRosterInputs.'.$player->getKey())
                                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                                    @enderror
                                @empty
                                    <flux:text>No players available.</flux:text>
                                @endforelse
                            </div>
                            @error('homeRosterInputs')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                            @error('homeCaptainSelection')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        <div class="space-y-3">
                            <flux:heading size="xs">Staff</flux:heading>
                            <div class="space-y-3" data-home-roster-staff>
                                @forelse ($homeStaff as $staffMember)
                                    <label class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2" data-home-roster-staff-member="{{ $staffMember->getKey() }}">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-zinc-900">{{ $staffMember->first_name }} {{ $staffMember->last_name }}</div>
                                            <div class="text-xs text-zinc-500">{{ $staffMember->role->value }}</div>
                                        </div>
                                        <flux:checkbox wire:model="homeStaffSelection.{{ $staffMember->getKey() }}" />
                                    </label>
                                @empty
                                    <flux:text>No staff available.</flux:text>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="space-y-1">
                            <flux:heading size="sm">Away Team</flux:heading>
                            <flux:text>{{ $awayTeamCode }}</flux:text>
                        </div>

                        <div class="space-y-3">
                            <flux:heading size="xs">Players</flux:heading>
                            <div class="grid grid-cols-[minmax(0,1fr)_5.5rem_3.75rem_4.5rem] items-center gap-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                <span>Name</span>
                                <span class="text-center">Captain?</span>
                                <span>Number</span>
                                <span class="text-center">Libero?</span>
                            </div>
                            <div class="space-y-3" data-away-roster-players>
                                @forelse ($awayPlayers as $player)
                                    <div class="grid grid-cols-[minmax(0,1fr)_5.5rem_3.75rem_4.5rem] items-center gap-3" data-away-roster-player="{{ $player->getKey() }}">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-zinc-900">{{ $player->first_name }} {{ $player->last_name }}</div>
                                        </div>
                                        <div class="flex justify-center">
                                            <input
                                                type="radio"
                                                wire:model="awayCaptainSelection"
                                                value="{{ $player->getKey() }}"
                                                name="away-captain-selection"
                                                class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-500"
                                            />
                                        </div>
                                        <div>
                                            <flux:input
                                                wire:model="awayRosterInputs.{{ $player->getKey() }}"
                                                :invalid="$errors->has('awayRosterInputs.'.$player->getKey())"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="flex justify-center">
                                            <flux:checkbox wire:model="awayLiberoSelection.{{ $player->getKey() }}" />
                                        </div>
                                    </div>
                                    @error('awayRosterInputs.'.$player->getKey())
                                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                                    @enderror
                                @empty
                                    <flux:text>No players available.</flux:text>
                                @endforelse
                            </div>
                            @error('awayRosterInputs')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                            @error('awayCaptainSelection')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>

                        <div class="space-y-3">
                            <flux:heading size="xs">Staff</flux:heading>
                            <div class="space-y-3" data-away-roster-staff>
                                @forelse ($awayStaff as $staffMember)
                                    <label class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2" data-away-roster-staff-member="{{ $staffMember->getKey() }}">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-zinc-900">{{ $staffMember->first_name }} {{ $staffMember->last_name }}</div>
                                            <div class="text-xs text-zinc-500">{{ $staffMember->role->value }}</div>
                                        </div>
                                        <flux:checkbox wire:model="awayStaffSelection.{{ $staffMember->getKey() }}" />
                                    </label>
                                @empty
                                    <flux:text>No staff available.</flux:text>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>

                @error('rosters')
                    <flux:text class="text-red-600">{{ $message }}</flux:text>
                @enderror

                <div class="flex items-center gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        Submit
                    </flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="confirm-rosters" class="w-full max-w-5xl">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Confirm rosters</flux:heading>
                    <flux:text class="mt-2">
                        Review the submitted roster details for both teams before saving them permanently.
                    </flux:text>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section>
                        <flux:heading class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $homeTeamCode }}</flux:heading>
                    </section>
                    <section>
                        <flux:heading class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $awayTeamCode }}</flux:heading>
                    </section>
                </div>

                <div class="space-y-6">
                    <section>
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            @foreach ([$homeRosterConfirmation, $awayRosterConfirmation] as $teamConfirmation)
                                <div class="space-y-3">
                                    <flux:heading size="xs">Players</flux:heading>
                                    @if ($teamConfirmation['players']->isEmpty())
                                        <flux:text>No rostered players.</flux:text>
                                    @else
                                        <div class="space-y-0.5">
                                            @foreach ($teamConfirmation['players'] as $player)
                                                <div class="flex items-center gap-3 px-1 py-1">
                                                    <span class="inline-flex h-8 w-10 shrink-0 items-center justify-center">
                                                        @if ($player['is_captain'])
                                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-zinc-900 text-sm font-semibold text-zinc-900">
                                                                {{ $player['number'] }}
                                                            </span>
                                                        @else
                                                            <span class="text-sm font-semibold text-zinc-700">{{ $player['number'] }}</span>
                                                        @endif
                                                    </span>
                                                    <span class="text-sm font-medium text-zinc-900">{{ $player['name'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section>
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            @foreach ([$homeRosterConfirmation, $awayRosterConfirmation] as $teamConfirmation)
                                <div class="space-y-3">
                                    <flux:heading size="xs">Liberos</flux:heading>
                                    @if ($teamConfirmation['liberos']->isEmpty())
                                        <flux:text>No liberos selected.</flux:text>
                                    @else
                                        <div class="space-y-0.5">
                                            @foreach ($teamConfirmation['liberos'] as $libero)
                                                <div class="flex items-center gap-3 px-1 py-1">
                                                    <span class="inline-flex h-8 w-10 shrink-0 items-center justify-center text-sm font-semibold text-zinc-700">
                                                        {{ $libero['number'] }}
                                                    </span>
                                                    <span class="text-sm font-medium text-zinc-900">{{ $libero['name'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section>
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            @foreach ([$homeRosterConfirmation, $awayRosterConfirmation] as $teamConfirmation)
                                <div class="space-y-3">
                                    <flux:heading size="xs">Bench</flux:heading>
                                    @if ($teamConfirmation['bench']->isEmpty())
                                        <flux:text>No bench staff selected.</flux:text>
                                    @else
                                        <div class="space-y-0.5">
                                            @foreach ($teamConfirmation['bench'] as $staffMember)
                                                <div class="flex items-center gap-3 px-1 py-1">
                                                    <span class="inline-flex h-8 w-10 shrink-0 items-center justify-center text-sm font-semibold text-zinc-700">
                                                        {{ $staffMember['role'] }}
                                                    </span>
                                                    <span class="text-sm font-medium text-zinc-900">{{ $staffMember['name'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                @error('rosters')
                    <flux:text class="text-red-600">{{ $message }}</flux:text>
                @enderror

                <div class="flex items-center gap-2">
                    <flux:spacer />
                    <flux:button type="button" variant="ghost" wire:click="returnToRoster">
                        Return to roster
                    </flux:button>
                    <flux:button type="button" variant="primary" wire:click="confirmRosters">
                        Submit
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
