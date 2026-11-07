@php
    $k = $bin->statusKey();
    $p = $bin->fill_level_percent;
    $bar = ['full' => 'bg-level-full', 'medium' => 'bg-level-mid', 'empty' => 'bg-level-empty'][$k] ?? 'bg-slate-300';
    $badge = [
        'full' => 'bg-red-50 text-level-full',
        'medium' => 'bg-amber-50 text-level-mid',
        'empty' => 'bg-green-50 text-level-empty',
        'error' => 'bg-slate-200 text-slate-600',
    ][$k] ?? 'bg-slate-100 text-slate-500';
@endphp
<div class="flex items-center gap-3">
    <div class="h-2.5 w-24 overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full {{ $bar }}" style="width: {{ $p ?? 0 }}%"></div>
    </div>
    <span class="w-10 text-sm font-bold text-slate-700">{{ $p !== null ? $p . '%' : '-' }}</span>
    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $badge }}">{{ $bin->statusLabel() }}</span>
</div>