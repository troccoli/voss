<div>
    @if ($showSetBreakCountdown)
        <div
            class="absolute left-1/2 top-24 z-20 -translate-x-1/2 sm:top-[calc(50%-185px)]"
            x-data="{
                remainingSeconds: {{ $setBreakRemainingSeconds }},
                init() {
                    if (this.remainingSeconds <= 0) {
                        this.$wire.$refresh();

                        return;
                    }

                    const timer = window.setInterval(() => {
                        if (this.remainingSeconds <= 0) {
                            window.clearInterval(timer);
                            this.$wire.$refresh();

                            return;
                        }

                        this.remainingSeconds--;
                    }, 1000);
                },
                formattedCountdown() {
                    const minutes = String(Math.floor(this.remainingSeconds / 60)).padStart(2, '0');
                    const seconds = String(this.remainingSeconds % 60).padStart(2, '0');

                    return `${minutes}:${seconds}`;
                }
            }"
        >
            <div
                class="rounded-md border border-accent bg-white/90 px-4 py-2 text-center shadow-sm"
                data-set-break-countdown
            >
                <flux:text class="font-semibold text-slate-900">
                    Next set in <span x-text="formattedCountdown()">{{ $setBreakCountdownLabel }}</span>
                </flux:text>
            </div>

            @error('startSet')
            <flux:text class="mt-2 text-center text-red-600">{{ $message }}</flux:text>
            @enderror
        </div>
    @elseif ($showStartGameButton)
        <div class="absolute left-1/2 top-24 z-20 -translate-x-1/2 sm:top-[calc(50%-185px)]">
            <flux:button
                variant="primary"
                aria-label="Start game"
                wire:click="startSet"
                wire:loading.attr="disabled"
                wire:target="startSet"
            >
                Start Game
            </flux:button>

            @error('startSet')
            <flux:text class="mt-2 text-center text-red-600">{{ $message }}</flux:text>
            @enderror
        </div>
    @elseif ($shouldAutoStartSet)
        <div
            class="absolute left-1/2 top-24 z-20 -translate-x-1/2 sm:top-[calc(50%-185px)]"
            x-data
            x-init="$wire.startSet()"
        ></div>
    @endif
</div>
