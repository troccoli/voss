<div>
    @if ($canStartSet)
        <div class="absolute left-1/2 top-[calc(50%-285px)] z-20 -translate-x-1/2">
            <flux:button
                variant="primary"
                icon="play"
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
