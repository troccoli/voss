<div>
    @if ($canStartSet)
        <div class="absolute left-1/2 top-24 z-20 -translate-x-1/2 sm:top-[calc(50%-185px)]">
            <flux:button
                variant="primary"
                icon="play"
                aria-label="Start set {{ $upcomingSetNumber }}"
                wire:click="startSet"
                wire:loading.attr="disabled"
                wire:target="startSet"
            >
                Start Set {{ $upcomingSetNumber }}
            </flux:button>

            @error('startSet')
                <flux:text class="mt-2 text-center text-red-600">{{ $message }}</flux:text>
            @enderror
        </div>
    @endif
</div>
