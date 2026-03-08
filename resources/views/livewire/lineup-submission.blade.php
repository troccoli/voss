<div>
    <flux:modal.trigger :name="$this->modalName()">
        <flux:button variant="primary" icon="users">
            {{ $this->buttonLabel() }}
        </flux:button>
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
                        wire:key="{{ $this->team }}-position-{{ $position }}"
                        name="lineup[{{ $position }}]"
                        wire:model="lineup.{{ $position }}"
                        class="h-12! w-12!"
                    />
                @endfor
            </div>

            <div class="flex items-center mt-8">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Submit</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
