<div>
    @if ($canSubmitLineup)
        <flux:modal.trigger :name="$this->modalName()">
            <flux:button variant="primary" icon="users">Submit Lineup</flux:button>
        </flux:modal.trigger>

        <flux:modal :name="$this->modalName()" class="min-w-[21rem]">
            <form wire:submit="submit" class="space-y-5">
                <flux:heading size="lg">{{ $this->modalHeading() }}</flux:heading>

                <div class="w-full grid grid-cols-3 gap-3 place-items-center">
                    @for ($position = 1; $position <= 6; $position++)
                        <flux:input
                            label="{{ $position }}"
                            label:class="mb-0!"
                            field:class="flex flex-col items-center"
                            wire:key="{{ $this->team->value }}-position-{{ $position }}"
                            name="lineup[{{ $position }}]"
                            wire:model="lineup.{{ $position }}"
                            class="h-12! w-12!"
                        />
                    @endfor
                </div>

                @error('submit')
                    <flux:text class="text-red-600">{{ $message }}</flux:text>
                @enderror

                <div class="flex items-center mt-8">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Submit</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
