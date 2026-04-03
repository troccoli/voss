<div>
    @if ($canRecordRallyWinner)
        <div class="absolute left-1/2 top-24 z-20 -translate-x-1/2 sm:top-[calc(50%-185px)]">
            <div class="flex w-[min(92vw,600px)] justify-between gap-52">
                <flux:button
                    class="w-full"
                    variant="primary"
                    aria-label="Record rally winner: {{ $leftTeam->label() }}"
                    data-rally-winner-button="{{ $leftTeam->value }}"
                    data-rally-winner-side="left"
                    data-rally-winner-side-team="left-{{ $leftTeam->value }}"
                    wire:click="recordRallyWinner('{{ $leftTeam->value }}')"
                    wire:loading.attr="disabled"
                    wire:target="recordRallyWinner"
                >
                    Winner
                </flux:button>

                <flux:button
                    class="w-full"
                    variant="primary"
                    aria-label="Record rally winner: {{ $rightTeam->label() }}"
                    data-rally-winner-button="{{ $rightTeam->value }}"
                    data-rally-winner-side="right"
                    data-rally-winner-side-team="right-{{ $rightTeam->value }}"
                    wire:click="recordRallyWinner('{{ $rightTeam->value }}')"
                    wire:loading.attr="disabled"
                    wire:target="recordRallyWinner"
                >
                    Winner
                </flux:button>
            </div>

            @error('submit')
                <flux:text class="mt-2 text-center text-red-600">{{ $message }}</flux:text>
            @enderror
        </div>
    @endif
</div>
