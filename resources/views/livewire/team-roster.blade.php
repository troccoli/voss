<aside @class(['w-[160px] space-y-2', 'text-right' => $alignRight])>
    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700">{{ $teamLabel }}</h2>

    @if ($players === [])
        <p class="text-xs text-slate-500">No players available.</p>
    @else
        <ul role="list" class="space-y-1">
            @foreach ($players as $player)
                <li wire:key="{{ $keyPrefix }}-{{ $player['player_key'] }}" class="text-sm text-slate-700">
                    @if ($numberFirst)
                        {{ $player['number'] }} {{ $player['last_name'] }}
                    @else
                        {{ $player['last_name'] }} {{ $player['number'] }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</aside>
