<aside class="min-w-0">
    @if ($showPlayerPlaceholders)
        <ul
            role="list"
            class="flex flex-nowrap items-center gap-2 overflow-x-auto pb-1"
        >
            @foreach (range(1, $placeholderCount) as $placeholderIndex)
                <li
                    data-team-roster-placeholder="{{ $placeholderIndex }}"
                    class="h-8 w-8 rounded-full border border-slate-300 bg-slate-300 shadow-sm"
                ></li>
            @endforeach
        </ul>
    @elseif ($players === [] && ! $hasRosterPlayers)
        <p class="text-xs text-slate-500">No players available.</p>
    @elseif ($players !== [])
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

    @if ($staffMarkers !== [])
        <ul
            role="list"
            data-team-roster-staff-list
            @class([
                'mt-2 flex flex-nowrap items-center gap-2',
                'flex-row-reverse' => $reverseStaffOrder,
            ])
        >
            @foreach ($staffMarkers as $staffMarker)
                <li
                    data-team-roster-staff-role="{{ $staffMarker['role_letter'] }}{{ $staffMarker['subscript'] ?? '' }}"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-semibold text-slate-800 shadow-sm"
                >
                    <span class="leading-none">
                        {{ $staffMarker['role_letter'] }}
                        @if (! is_null($staffMarker['subscript']))
                            <sub class="text-[9px] leading-none">{{ $staffMarker['subscript'] }}</sub>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</aside>
