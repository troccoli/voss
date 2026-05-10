<div>
    @if ($canRecordRallyWinner)
        @php $team = $side === 'left' ? $leftTeam : $rightTeam; @endphp
        <flux:button
            variant="primary"
            aria-label="Record rally winner: {{ $team->label() }}"
            data-rally-winner-button="{{ $team->value }}"
            data-rally-winner-side="{{ $side }}"
            data-rally-winner-side-team="{{ $side }}-{{ $team->value }}"
            wire:click="recordRallyWinner('{{ $team->value }}')"
            wire:loading.attr="disabled"
            wire:target="recordRallyWinner"
        >
            Winner
        </flux:button>

        @error('submit')
            <flux:text class="mt-2 text-red-600">{{ $message }}</flux:text>
        @enderror
    @endif
</div>
