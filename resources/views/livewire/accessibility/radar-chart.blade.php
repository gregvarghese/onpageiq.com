<div class="radar-chart">
    <svg
        viewBox="0 0 {{ $size }} {{ $size }}"
        width="{{ $size }}"
        height="{{ $size }}"
        class="mx-auto"
    >
        {{-- Background grid circles --}}
        @foreach($gridCircles as $radius)
            <circle
                cx="{{ $size / 2 }}"
                cy="{{ $size / 2 }}"
                r="{{ $radius }}"
                fill="none"
                stroke="currentColor"
                class="text-gray-200 dark:text-gray-700"
                stroke-width="1"
            />
        @endforeach

        {{-- Axis lines --}}
        @foreach($axisLines as $line)
            <line
                x1="{{ $line['x1'] }}"
                y1="{{ $line['y1'] }}"
                x2="{{ $line['x2'] }}"
                y2="{{ $line['y2'] }}"
                stroke="currentColor"
                class="text-gray-200 dark:text-gray-700"
                stroke-width="1"
            />
        @endforeach

        {{-- Data polygon --}}
        @if($polygonPoints)
            <polygon
                points="{{ $polygonPoints }}"
                fill="currentColor"
                class="text-primary-500/30 dark:text-primary-400/30"
                stroke="currentColor"
                stroke-width="2"
                class="text-primary-600 dark:text-primary-400"
            />

            {{-- Data points --}}
            @php
                $points = explode(' ', $polygonPoints);
            @endphp
            @foreach($points as $point)
                @php
                    [$x, $y] = explode(',', $point);
                @endphp
                <circle
                    cx="{{ $x }}"
                    cy="{{ $y }}"
                    r="4"
                    fill="currentColor"
                    class="text-primary-600 dark:text-primary-400"
                />
            @endforeach
        @endif

        {{-- Labels --}}
        @if($showLabels)
            @foreach($labelPositions as $category => $position)
                <g>
                    <text
                        x="{{ $position['x'] }}"
                        y="{{ $position['y'] }}"
                        text-anchor="{{ $position['anchor'] }}"
                        dominant-baseline="middle"
                        class="fill-gray-700 dark:fill-gray-300 text-xs font-medium"
                    >
                        {{ $position['label'] }}
                    </text>
                    @if($showValues)
                        <text
                            x="{{ $position['x'] }}"
                            y="{{ $position['y'] + 14 }}"
                            text-anchor="{{ $position['anchor'] }}"
                            dominant-baseline="middle"
                            class="fill-gray-500 dark:fill-gray-400 text-xs"
                        >
                            {{ number_format($position['score'], 0) }}%
                        </text>
                    @endif
                </g>
            @endforeach
        @endif

        {{-- Center point --}}
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="3"
            fill="currentColor"
            class="text-gray-400 dark:text-gray-500"
        />
    </svg>

    {{-- Legend (optional, can be toggled) --}}
    @if(count($chartData) > 0)
        <div class="mt-4 flex flex-wrap justify-center gap-4">
            @foreach($chartData as $category => $data)
                <div class="flex items-center gap-x-2">
                    <span class="inline-block w-3 h-3 rounded-full {{ $data['score'] >= 90 ? 'bg-green-500' : ($data['score'] >= 70 ? 'bg-yellow-500' : 'bg-red-500') }}"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $data['label'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
