<div class="min-h-screen bg-slate-950 px-4 py-8 text-slate-100 sm:px-6">
    <div class="mx-auto max-w-7xl space-y-6">
        <flux:card class="border border-white/10 bg-white/6 p-6 backdrop-blur sm:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <flux:badge color="amber" inset="top bottom">Match Setup</flux:badge>
                    <div class="space-y-2">
                        <flux:heading size="xl" class="text-white">Prepare the current match before play starts</flux:heading>
                        <flux:text class="max-w-3xl text-slate-300">
                            Competition metadata comes from app config. Enter the match details, both team rosters, and all officials here before recording tosses, lineups, or rallies.
                        </flux:text>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-slate-300">
                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Competition</div>
                    <div class="mt-1 font-medium text-white">{{ $competitionName }}</div>
                </div>
            </div>

            @error('setup')
                <flux:text class="mt-4 text-red-300">{{ $message }}</flux:text>
            @enderror
        </flux:card>

        @if ($step === 'missing-match')
            <flux:card class="border border-white/10 bg-slate-900/70 p-8">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <flux:heading size="lg" class="text-white">Create the current match</flux:heading>
                        <flux:text class="max-w-2xl text-slate-300">
                            The database does not have a current match yet. Create the singleton match record, then complete the setup flow.
                        </flux:text>
                    </div>

                    <div class="flex justify-start">
                        <flux:button variant="primary" wire:click="createMatch">
                            Create current match
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @elseif ($game !== null)
            @php
                $stepOrder = ['match-details', 'rosters', 'officials', 'ready'];
            @endphp

            <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
                <flux:card class="border border-white/10 bg-slate-900/70 p-4">
                    <div class="space-y-3">
                        @foreach ($setupSteps as $stepKey => $stepLabel)
                            @php
                                $requiredIndex = array_search($currentRequiredStep, $stepOrder, true);
                                $stepIndex = array_search($stepKey, $stepOrder, true);
                                $isComplete = $isSetupComplete ? $stepKey !== 'ready' : ($requiredIndex !== false && $stepIndex !== false && $stepIndex < $requiredIndex);
                                $isCurrent = $step === $stepKey;
                            @endphp

                            <button
                                type="button"
                                wire:click="openStep('{{ $stepKey }}')"
                                class="@class([
                                    'flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition',
                                    'border-amber-400/60 bg-amber-500/10 text-white' => $isCurrent,
                                    'border-emerald-400/40 bg-emerald-500/10 text-slate-100' => $isComplete && ! $isCurrent,
                                    'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10' => ! $isCurrent && ! $isComplete,
                                ])"
                            >
                                <span class="font-medium">{{ $stepLabel }}</span>
                                <span class="text-xs uppercase tracking-[0.18em] {{ $isComplete ? 'text-emerald-300' : ($isCurrent ? 'text-amber-200' : 'text-slate-500') }}">
                                    {{ $isComplete ? 'Done' : ($isCurrent ? 'Current' : 'Pending') }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-6 rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-300">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Current Match</div>
                        <div class="mt-2 font-medium text-white">
                            {{ $homeTeamName !== '' ? $homeTeamName : 'Home team' }}
                            <span class="text-slate-500">vs</span>
                            {{ $awayTeamName !== '' ? $awayTeamName : 'Away team' }}
                        </div>
                        <div class="mt-1">
                            {{ $city !== '' ? $city : 'City' }}{{ $hall !== '' ? ', '.$hall : '' }}
                        </div>
                    </div>
                </flux:card>

                <div class="space-y-6">
                    @if ($isSetupLocked)
                        <flux:card class="border border-amber-400/20 bg-amber-500/10 p-5">
                            <flux:heading size="sm" class="text-amber-100">Setup is locked</flux:heading>
                            <flux:text class="mt-2 text-amber-50/90">
                                Match details, rosters, and officials are read-only after the first match event is recorded. Review the summary below and continue scoring from the current match page.
                            </flux:text>
                        </flux:card>
                    @elseif ($step === 'match-details')
                        <flux:card class="border border-white/10 bg-slate-900/70 p-6">
                            <form wire:submit="saveMatchDetails" class="space-y-6">
                                <div class="space-y-2">
                                    <flux:heading size="lg" class="text-white">Match details</flux:heading>
                                    <flux:text class="text-slate-300">
                                        Enter the static metadata for the one current match, including the home and away team identity.
                                    </flux:text>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <flux:input wire:model="matchNumber" label="Match number" />
                                        @error('matchNumber')
                                            <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="matchCountryCode" label="Host country code" />
                                        @error('matchCountryCode')
                                            <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input type="datetime-local" wire:model="matchDateTime" label="Scheduled at" />
                                        @error('matchDateTime')
                                            <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="city" label="City" />
                                        @error('city')
                                            <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="hall" label="Hall" />
                                        @error('hall')
                                            <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="pool" label="Pool" />
                                        @error('pool')
                                            <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid gap-6 lg:grid-cols-2">
                                    <flux:radio.group wire:model="division" label="Division" variant="segmented" :invalid="$errors->has('division')">
                                        <flux:radio value="Men" label="Men" />
                                        <flux:radio value="Women" label="Women" />
                                    </flux:radio.group>

                                    <flux:radio.group wire:model="category" label="Category" variant="segmented" :invalid="$errors->has('category')">
                                        <flux:radio value="Senior" label="Senior" />
                                        <flux:radio value="Junior" label="Junior" />
                                        <flux:radio value="Youth" label="Youth" />
                                    </flux:radio.group>
                                </div>

                                @error('division')
                                    <flux:text class="text-red-300">{{ $message }}</flux:text>
                                @enderror
                                @error('category')
                                    <flux:text class="text-red-300">{{ $message }}</flux:text>
                                @enderror

                                <div class="grid gap-6 lg:grid-cols-2">
                                    <div class="space-y-4 rounded-2xl border border-white/10 bg-white/5 p-5">
                                        <flux:heading size="sm" class="text-white">Home team</flux:heading>
                                        <div>
                                            <flux:input wire:model="homeTeamName" label="Team name" />
                                            @error('homeTeamName')
                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                        <div>
                                            <flux:input wire:model="homeTeamCountryCode" label="Country code" />
                                            @error('homeTeamCountryCode')
                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="space-y-4 rounded-2xl border border-white/10 bg-white/5 p-5">
                                        <flux:heading size="sm" class="text-white">Away team</flux:heading>
                                        <div>
                                            <flux:input wire:model="awayTeamName" label="Team name" />
                                            @error('awayTeamName')
                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                        <div>
                                            <flux:input wire:model="awayTeamCountryCode" label="Country code" />
                                            @error('awayTeamCountryCode')
                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <flux:button type="submit" variant="primary">Save match details</flux:button>
                                </div>
                            </form>
                        </flux:card>
                    @elseif ($step === 'rosters')
                        <flux:card class="border border-white/10 bg-slate-900/70 p-6">
                            <form wire:submit="saveRosters" class="space-y-8">
                                <div class="space-y-2">
                                    <flux:heading size="lg" class="text-white">Home and away rosters</flux:heading>
                                    <flux:text class="text-slate-300">
                                        Enter the match roster directly for each team. Every listed player becomes part of the current match roster.
                                    </flux:text>
                                </div>

                                <div class="grid gap-8 xl:grid-cols-2">
                                    @foreach ([
                                        'home' => ['label' => $homeTeamName !== '' ? $homeTeamName : 'Home team', 'players' => $homePlayerRows, 'staff' => $homeStaffRows],
                                        'away' => ['label' => $awayTeamName !== '' ? $awayTeamName : 'Away team', 'players' => $awayPlayerRows, 'staff' => $awayStaffRows],
                                    ] as $side => $team)
                                        <section class="space-y-5 rounded-3xl border border-white/10 bg-white/5 p-5">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <flux:heading size="sm" class="text-white">{{ $team['label'] }}</flux:heading>
                                                    <flux:text class="text-slate-400">Players, captain, libero selection, and bench staff.</flux:text>
                                                </div>
                                                <flux:button type="button" variant="ghost" wire:click="addPlayerRow('{{ $side }}')">
                                                    Add player
                                                </flux:button>
                                            </div>

                                            @error($side.'PlayerRows')
                                                <flux:text class="text-red-300">{{ $message }}</flux:text>
                                            @enderror

                                            <div class="space-y-3">
                                                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_4.5rem_5rem_4.5rem_4.5rem] gap-3 text-xs uppercase tracking-[0.18em] text-slate-500">
                                                    <span>First name</span>
                                                    <span>Last name</span>
                                                    <span>Number</span>
                                                    <span class="text-center">Captain</span>
                                                    <span class="text-center">Libero</span>
                                                    <span class="text-right">Row</span>
                                                </div>

                                                @foreach ($team['players'] as $index => $playerRow)
                                                    <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_4.5rem_5rem_4.5rem_4.5rem] items-center gap-3">
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}PlayerRows.{{ $index }}.first_name" />
                                                            @error($side.'PlayerRows.'.$index.'.first_name')
                                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}PlayerRows.{{ $index }}.last_name" />
                                                            @error($side.'PlayerRows.'.$index.'.last_name')
                                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}PlayerRows.{{ $index }}.number" />
                                                            @error($side.'PlayerRows.'.$index.'.number')
                                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                        <div class="flex justify-center">
                                                            <input
                                                                type="radio"
                                                                wire:model="{{ $side }}CaptainSelection"
                                                                value="{{ $index }}"
                                                                name="{{ $side }}-captain-selection"
                                                                class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-500"
                                                            />
                                                        </div>
                                                        <div class="flex justify-center">
                                                            <flux:checkbox wire:model="{{ $side }}PlayerRows.{{ $index }}.is_libero" />
                                                        </div>
                                                        <div class="flex justify-end">
                                                            <flux:button type="button" variant="ghost" wire:click="removePlayerRow('{{ $side }}', {{ $index }})">
                                                                Remove
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="space-y-3">
                                                <flux:heading size="xs" class="text-white">Bench staff</flux:heading>

                                                @foreach ($team['staff'] as $index => $staffRow)
                                                    <div class="grid gap-3 md:grid-cols-[10rem_minmax(0,1fr)_minmax(0,1fr)]">
                                                        <div class="rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm font-medium text-slate-200">
                                                            {{ $staffRow['role'] }}
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}StaffRows.{{ $index }}.first_name" placeholder="First name" />
                                                            @error($side.'StaffRows.'.$index.'.first_name')
                                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}StaffRows.{{ $index }}.last_name" placeholder="Last name" />
                                                            @error($side.'StaffRows.'.$index.'.last_name')
                                                                <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach
                                </div>

                                <div class="flex justify-end">
                                    <flux:button type="submit" variant="primary">Save rosters</flux:button>
                                </div>
                            </form>
                        </flux:card>
                    @elseif ($step === 'officials')
                        <flux:card class="border border-white/10 bg-slate-900/70 p-6">
                            <form wire:submit="saveOfficials" class="space-y-6">
                                <div class="space-y-2">
                                    <flux:heading size="lg" class="text-white">Officials</flux:heading>
                                    <flux:text class="text-slate-300">
                                        Enter the assigned officials for the scoresheet before moving into gameplay.
                                    </flux:text>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    @foreach ($officialRows as $index => $officialRow)
                                        <section class="space-y-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                                            <flux:heading size="xs" class="text-white">{{ $officialRow['role'] }}</flux:heading>

                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div>
                                                    <flux:input wire:model="officialRows.{{ $index }}.first_name" label="First name" />
                                                    @error('officialRows.'.$index.'.first_name')
                                                        <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <flux:input wire:model="officialRows.{{ $index }}.last_name" label="Last name" />
                                                    @error('officialRows.'.$index.'.last_name')
                                                        <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div>
                                                <flux:input wire:model="officialRows.{{ $index }}.country_code" label="Country code" />
                                                @error('officialRows.'.$index.'.country_code')
                                                    <flux:text class="mt-1 text-red-300">{{ $message }}</flux:text>
                                                @enderror
                                            </div>
                                        </section>
                                    @endforeach
                                </div>

                                <div class="flex justify-end">
                                    <flux:button type="submit" variant="primary">Save officials</flux:button>
                                </div>
                            </form>
                        </flux:card>
                    @endif

                    @if ($step === 'ready' || $isSetupLocked)
                        <flux:card class="border border-white/10 bg-slate-900/70 p-6">
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <flux:heading size="lg" class="text-white">Match ready</flux:heading>
                                    <flux:text class="text-slate-300">
                                        Static setup is complete. Use the scoring screen for tosses, lineups, and all event recording.
                                    </flux:text>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Fixture</div>
                                        <div class="mt-2 font-medium text-white">{{ $homeTeamName }} vs {{ $awayTeamName }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Venue</div>
                                        <div class="mt-2 font-medium text-white">{{ $city }}, {{ $hall }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Rosters</div>
                                        <div class="mt-2 font-medium text-white">{{ collect($homePlayerRows)->filter(fn ($row) => trim($row['number']) !== '')->count() }} / {{ collect($awayPlayerRows)->filter(fn ($row) => trim($row['number']) !== '')->count() }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Officials</div>
                                        <div class="mt-2 font-medium text-white">{{ collect($officialRows)->filter(fn ($row) => trim($row['first_name']) !== '' && trim($row['last_name']) !== '')->count() }} assigned</div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-300">
                                    @if ($isSetupLocked)
                                        Setup changes are disabled because match events already exist.
                                    @else
                                        Setup remains editable here until the first match event is recorded. After that point, rosters and officials are locked for the rest of the match.
                                    @endif
                                </div>

                                <div class="flex justify-end">
                                    <a href="{{ route('game') }}" wire:navigate>
                                        <flux:button variant="primary">Open current match</flux:button>
                                    </a>
                                </div>
                            </div>
                        </flux:card>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
