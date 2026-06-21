<div class="min-h-screen bg-linear-to-br from-stone-50 via-white to-sky-50 px-4 py-8 text-slate-900 sm:px-6">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="space-y-5 px-1 py-2 sm:px-2">
            <div class="space-y-2">
                <flux:heading size="xl">Prepare the current match before play starts</flux:heading>
                <flux:text class="text-base">
                    Use this page to set up the match before it can begin.
                </flux:text>
                <flux:text class="text-base">
                Start by saving the competition, then fill in the match details, enter both team rosters, and assign all required officials. Until those sections are complete, you will stay on setup and the scoring screen will remain unavailable.
                </flux:text>
                <flux:text class="text-base">
                    Every section stays editable throughout setup. You can move back and forth between steps, adjust earlier information, and review what is still missing before the match becomes ready. Once setup is complete, you can open the match and begin recording tosses, lineups, rallies, and the rest of the live flow.
                </flux:text>
            </div>

            @error('setup')
                <flux:text class="mt-4">{{ $message }}</flux:text>
            @enderror
        </section>

        @if ($step === 'missing-match' || $step === 'competition')
            <flux:card class="border border-slate-200/80 bg-white/90 p-8 shadow-sm">
                @if (! $competitionConfigured)
                    <form wire:submit="saveCompetition" class="space-y-5">
                        <div class="space-y-2">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Competition</div>
                            <flux:heading size="lg">Competition details</flux:heading>
                            <flux:text class="max-w-2xl">
                                Save the current competition name before continuing with the match setup flow.
                            </flux:text>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <flux:field class="flex-1">
                                <flux:label>Competition name</flux:label>
                                <flux:input wire:model.live="competitionName" placeholder="e.g. Nations League Finals" />
                                <flux:error name="competitionName" />
                            </flux:field>

                            <flux:button type="submit" variant="primary" class="sm:self-end">
                                Save competition
                            </flux:button>
                        </div>
                    </form>
                @elseif ($editingCompetition)
                    <form wire:submit="saveCompetition" class="space-y-5">
                        <div class="space-y-2">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Competition</div>
                            <flux:input
                                wire:model.live="competitionName"
                                placeholder="e.g. Nations League Finals"
                                class="max-w-xl"
                            />
                            <flux:text class="max-w-2xl">
                                Update the competition name inline, then press Enter to save it.
                            </flux:text>
                            <flux:error name="competitionName" />
                        </div>
                    </form>
                @else
                    <div class="space-y-2">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Competition</div>
                        <div class="flex gap-4">
                            <flux:heading>{{ $currentCompetitionName }}</flux:heading>
                            <flux:button
                                variant="subtle"
                                size="xs"
                                icon="pencil-square"
                                inset
                                square
                                tooltip="Edit competition"
                                wire:click="editCompetition"
                            />
                        </div>
                        <flux:text class="max-w-2xl">
                            Competition details are set. You can still edit them before setup is completed.
                        </flux:text>
                    </div>
                @endif
            </flux:card>

            <flux:card class="border border-slate-200/80 bg-white/90 p-8 shadow-sm">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <flux:heading size="lg">Create the current match</flux:heading>
                        <flux:text>
                            @if ($competitionConfigured)
                                The database does not have a current match yet. Create the singleton match record, then complete the remaining setup flow.
                            @else
                                After saving the competition, create the singleton match record here and continue through the setup flow.
                            @endif
                        </flux:text>
                    </div>

                    <div class="flex justify-start">
                        <flux:button variant="primary" wire:click="createMatch" :disabled="! $competitionConfigured">
                            Create current match
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @elseif ($game !== null)
            @php
                $stepOrder = ['competition', 'match-details', 'rosters', 'officials', 'ready'];
            @endphp

            <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
                <flux:card class="border border-slate-200/80 bg-white/90 p-4 shadow-sm">
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
                                    'border-amber-300 bg-amber-50 text-slate-950' => $isCurrent,
                                    'border-emerald-200 bg-emerald-50 text-slate-900' => $isComplete && ! $isCurrent,
                                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => ! $isCurrent && ! $isComplete,
                                ])"
                            >
                                <span class="font-medium">{{ $stepLabel }}</span>
                                <span class="text-xs uppercase tracking-[0.18em] {{ $isComplete ? 'text-emerald-600' : ($isCurrent ? 'text-amber-700' : 'text-slate-500') }}">
                                    {{ $isComplete ? 'Done' : ($isCurrent ? 'Current' : 'Pending') }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Current Match</div>
                        <div class="mt-2 font-medium text-slate-950">
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
                        <flux:card class="border border-amber-200 bg-amber-50 p-5 shadow-sm">
                            <flux:heading size="sm">Setup is locked</flux:heading>
                            <flux:text class="mt-2">
                                Match details, rosters, and officials are read-only after the first match event is recorded. Review the summary below and continue scoring from the current match page.
                            </flux:text>
                        </flux:card>
                    @elseif ($step === 'competition')
                        <flux:card class="border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                            @if ($editingCompetition)
                                <form wire:submit="saveCompetition" class="space-y-5">
                                    <div class="space-y-2">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Competition</div>
                                        <flux:input
                                            wire:model.live="competitionName"
                                            placeholder="e.g. Nations League Finals"
                                            class="max-w-xl"
                                        />
                                        <flux:text>
                                            Update the competition name inline, then press Enter to save it.
                                        </flux:text>
                                        <flux:error name="competitionName" />
                                    </div>
                                </form>
                            @else
                                <div class="space-y-2">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Competition</div>
                                    <div class="flex items-end gap-2">
                                        <flux:heading size="lg">{{ $currentCompetitionName }}</flux:heading>
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            size="xs"
                                            square
                                            icon="pencil-square"
                                            class="shrink-0"
                                            tooltip="Edit competition"
                                            wire:click="editCompetition"
                                        />
                                    </div>
                                    <flux:text>
                                        Competition details are set. You can still edit them before setup is completed.
                                    </flux:text>
                                </div>
                            @endif
                        </flux:card>
                    @elseif ($step === 'match-details')
                        <flux:card class="border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                            <form wire:submit="saveMatchDetails" class="space-y-6">
                                <div class="space-y-2">
                                    <flux:heading size="lg">Match details</flux:heading>
                                    <flux:text>
                                        Enter the static metadata for the one current match, including the home and away team identity.
                                    </flux:text>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <flux:input wire:model="matchNumber" label="Match number" />
                                        @error('matchNumber')
                                            <flux:text class="mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="matchCountryCode" label="Host country code" />
                                        @error('matchCountryCode')
                                            <flux:text class="mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input type="datetime-local" wire:model="matchDateTime" label="Scheduled at" />
                                        @error('matchDateTime')
                                            <flux:text class="mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="city" label="City" />
                                        @error('city')
                                            <flux:text class="mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="hall" label="Hall" />
                                        @error('hall')
                                            <flux:text class="mt-1">{{ $message }}</flux:text>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input wire:model="pool" label="Pool" />
                                        @error('pool')
                                            <flux:text class="mt-1">{{ $message }}</flux:text>
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
                                    <flux:text>{{ $message }}</flux:text>
                                @enderror
                                @error('category')
                                    <flux:text>{{ $message }}</flux:text>
                                @enderror

                                <div class="grid gap-6 lg:grid-cols-2">
                                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                        <flux:heading size="sm">Home team</flux:heading>
                                        <div>
                                            <flux:input wire:model="homeTeamName" label="Team name" />
                                            @error('homeTeamName')
                                                <flux:text class="mt-1">{{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                        <div>
                                            <flux:input wire:model="homeTeamCountryCode" label="Country code" />
                                            @error('homeTeamCountryCode')
                                                <flux:text class="mt-1">{{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                        <flux:heading size="sm">Away team</flux:heading>
                                        <div>
                                            <flux:input wire:model="awayTeamName" label="Team name" />
                                            @error('awayTeamName')
                                                <flux:text class="mt-1">{{ $message }}</flux:text>
                                            @enderror
                                        </div>
                                        <div>
                                            <flux:input wire:model="awayTeamCountryCode" label="Country code" />
                                            @error('awayTeamCountryCode')
                                                <flux:text class="mt-1">{{ $message }}</flux:text>
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
                        <flux:card class="border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                            <form wire:submit="saveRosters" class="space-y-8">
                                <div class="space-y-2">
                                    <flux:heading size="lg">Home and away rosters</flux:heading>
                                    <flux:text>
                                        Enter the match roster directly for each team. Every listed player becomes part of the current match roster.
                                    </flux:text>
                                </div>

                                <div class="grid gap-8 xl:grid-cols-2">
                                    @foreach ([
                                        'home' => ['label' => $homeTeamName !== '' ? $homeTeamName : 'Home team', 'players' => $homePlayerRows, 'staff' => $homeStaffRows],
                                        'away' => ['label' => $awayTeamName !== '' ? $awayTeamName : 'Away team', 'players' => $awayPlayerRows, 'staff' => $awayStaffRows],
                                    ] as $side => $team)
                                        <section class="space-y-5 rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <flux:heading size="sm">{{ $team['label'] }}</flux:heading>
                                                    <flux:text>Players, captain, libero selection, and bench staff.</flux:text>
                                                </div>
                                                <flux:button type="button" variant="ghost" wire:click="addPlayerRow('{{ $side }}')">
                                                    Add player
                                                </flux:button>
                                            </div>

                                            @error($side.'PlayerRows')
                                                <flux:text>{{ $message }}</flux:text>
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
                                                                <flux:text class="mt-1">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}PlayerRows.{{ $index }}.last_name" />
                                                            @error($side.'PlayerRows.'.$index.'.last_name')
                                                                <flux:text class="mt-1">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}PlayerRows.{{ $index }}.number" />
                                                            @error($side.'PlayerRows.'.$index.'.number')
                                                                <flux:text class="mt-1">{{ $message }}</flux:text>
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
                                                <flux:heading size="xs">Bench staff</flux:heading>

                                                @foreach ($team['staff'] as $index => $staffRow)
                                                    <div class="grid gap-3 md:grid-cols-[10rem_minmax(0,1fr)_minmax(0,1fr)]">
                                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                                                            {{ $staffRow['role'] }}
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}StaffRows.{{ $index }}.first_name" placeholder="First name" />
                                                            @error($side.'StaffRows.'.$index.'.first_name')
                                                                <flux:text class="mt-1">{{ $message }}</flux:text>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="{{ $side }}StaffRows.{{ $index }}.last_name" placeholder="Last name" />
                                                            @error($side.'StaffRows.'.$index.'.last_name')
                                                                <flux:text class="mt-1">{{ $message }}</flux:text>
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
                        <flux:card class="border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                            <form wire:submit="saveOfficials" class="space-y-6">
                                <div class="space-y-2">
                                    <flux:heading size="lg">Officials</flux:heading>
                                    <flux:text>
                                        Enter the assigned officials for the scoresheet before moving into gameplay.
                                    </flux:text>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    @foreach ($officialRows as $index => $officialRow)
                                        <section class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <flux:heading size="xs">{{ $officialRow['role'] }}</flux:heading>

                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div>
                                                    <flux:input wire:model="officialRows.{{ $index }}.first_name" label="First name" />
                                                    @error('officialRows.'.$index.'.first_name')
                                                        <flux:text class="mt-1">{{ $message }}</flux:text>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <flux:input wire:model="officialRows.{{ $index }}.last_name" label="Last name" />
                                                    @error('officialRows.'.$index.'.last_name')
                                                        <flux:text class="mt-1">{{ $message }}</flux:text>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div>
                                                <flux:input wire:model="officialRows.{{ $index }}.country_code" label="Country code" />
                                                @error('officialRows.'.$index.'.country_code')
                                                    <flux:text class="mt-1">{{ $message }}</flux:text>
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
                        <flux:card class="border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <flux:heading size="lg">Match ready</flux:heading>
                                    <flux:text>
                                        Static setup is complete. Use the scoring screen for tosses, lineups, and all event recording.
                                    </flux:text>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Fixture</div>
                                        <div class="mt-2 font-medium text-slate-950">{{ $homeTeamName }} vs {{ $awayTeamName }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Venue</div>
                                        <div class="mt-2 font-medium text-slate-950">{{ $city }}, {{ $hall }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Rosters</div>
                                        <div class="mt-2 font-medium text-slate-950">{{ collect($homePlayerRows)->filter(fn ($row) => trim($row['number']) !== '')->count() }} / {{ collect($awayPlayerRows)->filter(fn ($row) => trim($row['number']) !== '')->count() }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Officials</div>
                                        <div class="mt-2 font-medium text-slate-950">{{ collect($officialRows)->filter(fn ($row) => trim($row['first_name']) !== '' && trim($row['last_name']) !== '')->count() }} assigned</div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
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
