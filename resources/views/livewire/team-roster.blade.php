<aside class="min-w-0">
    @if ($players === [])
        <p class="text-xs text-slate-500">No players available.</p>
    @else
        <ul
            role="list"
            class="flex flex-nowrap items-center gap-2 overflow-x-auto pb-1"
        >
            @foreach ($players as $player)
                <li
                    wire:key="{{ $keyPrefix }}-{{ $player['player_key'] }}"
                    data-team-roster-number="{{ $player['number'] }}"
                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-semibold text-white shadow {{ $markerTone }}"
                >
                    {{ $player['number'] }}
                </li>
            @endforeach
        </ul>
    @endif
</aside>
