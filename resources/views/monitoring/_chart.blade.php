@php
    $pts = $logs->filter(fn ($l) => ! $l->sensor_error && $l->fill_level_percent !== null)->sortBy('created_at')->values();
    $n = $pts->count();
    $w = 640; $h = 220; $pl = 38; $pr = 14; $pt = 14; $pb = 28;
    $iw = $w - $pl - $pr; $ih = $h - $pt - $pb;
    $yOf = fn ($v) => round($pt + $ih * (1 - $v / 100), 1);
    $xy = $pts->map(function ($l, $i) use ($n, $pl, $iw, $yOf) {
        return [round($pl + ($n > 1 ? $iw * $i / ($n - 1) : $iw / 2), 1), $yOf($l->fill_level_percent)];
    });
    $line = $xy->map(fn ($p) => $p[0] . ',' . $p[1])->implode(' ');
    $baseY = $yOf(0);
@endphp
@if ($n < 2)
    <p class="mt-3 text-sm text-slate-500">Grafik tampil setelah minimal 2 laporan masuk.</p>
@else
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="mt-3 w-full" role="img" aria-label="Grafik volume sampah">
        @foreach ([0, 40, 80, 100] as $g)
            <line x1="{{ $pl }}" x2="{{ $w - $pr }}" y1="{{ $yOf($g) }}" y2="{{ $yOf($g) }}"
                  stroke="{{ $g === 80 ? '#fca5a5' : ($g === 40 ? '#fcd34d' : '#e2e8f0') }}" stroke-width="1" @if (in_array($g, [40, 80])) stroke-dasharray="4 4" @endif/>
            <text x="{{ $pl - 6 }}" y="{{ $yOf($g) + 4 }}" text-anchor="end" font-size="11" fill="#64748b">{{ $g }}%</text>
        @endforeach
        <polygon points="{{ $xy->first()[0] }},{{ $baseY }} {{ $line }} {{ $xy->last()[0] }},{{ $baseY }}" fill="#14b8a6" fill-opacity="0.12"/>
        <polyline points="{{ $line }}" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
        @foreach ($xy as $p)
            <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="3" fill="#0d9488"/>
        @endforeach
        <text x="{{ $pl }}" y="{{ $h - 8 }}" font-size="11" fill="#64748b">{{ $pts->first()->created_at->format('d M H:i') }}</text>
        <text x="{{ $w - $pr }}" y="{{ $h - 8 }}" text-anchor="end" font-size="11" fill="#64748b">{{ $pts->last()->created_at->format('d M H:i') }}</text>
    </svg>
@endif