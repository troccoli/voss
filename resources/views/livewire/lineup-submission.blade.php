<div>
    @if ($canSubmitLineup)
        <flux:modal.trigger :name="$this->modalName()">
            <flux:button variant="primary">Submit Lineup</flux:button>
        </flux:modal.trigger>

        <flux:modal :name="$this->modalName()" class="min-w-84">
            <form wire:submit="submit" class="space-y-5"
                x-data="{
                    focusedSlot: 1,
                    addPlayer(number) {
                        let wrapper = this.$refs['slot' + this.focusedSlot];
                        let input = wrapper ? wrapper.querySelector('input') : null;
                        if (!input) return;
                        input.value = number;
                        input.dispatchEvent(new Event('input'));
                        let next = this.focusedSlot < 6 ? this.focusedSlot + 1 : 1;
                        this.focusedSlot = next;
                        this.$nextTick(() => {
                            let nextWrapper = this.$refs['slot' + next];
                            let nextInput = nextWrapper ? nextWrapper.querySelector('input') : null;
                            if (nextInput) nextInput.focus();
                        });
                    }
                }"
            >
                <flux:heading size="lg">{{ $this->modalHeading() }}</flux:heading>

                <div class="w-full grid grid-cols-3 gap-3 place-items-center">
                    @for ($position = 1; $position <= 6; $position++)
                        <div x-ref="slot{{ $position }}" @focusin="focusedSlot = {{ $position }}"
                            @if ($position === 6)
                                @keydown.tab.prevent="focusedSlot = 1; $nextTick(() => { let w = $refs.slot1.querySelector('input'); if (w) w.focus(); })"
                            @endif
                        >
                            <flux:input
                                label="{{ $position }}"
                                label:class="mb-0!"
                                field:class="flex flex-col items-center"
                                wire:key="{{ $this->team->value }}-position-{{ $position }}"
                                name="lineup[{{ $position }}]"
                                wire:model="lineup.{{ $position }}"
                                :autofocus="$position === 1"
                                class="h-12! w-12!"
                            />
                        </div>
                    @endfor
                </div>

                @error('submit')
                    <flux:text class="text-red-600">{{ $message }}</flux:text>
                @enderror

                @if ($rosterNumbers !== [])
                    <div role="list" class="flex flex-wrap items-center justify-center gap-2" data-lineup-roster-numbers>
                        @foreach ($rosterNumbers as $rosterNumber)
                            <flux:badge
                                data-lineup-roster-number="{{ $rosterNumber }}"
                                class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-100"
                                @click="addPlayer({{ $rosterNumber }})"
                            >
                                {{ $rosterNumber }}
                            </flux:badge>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center mt-8">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Submit</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
