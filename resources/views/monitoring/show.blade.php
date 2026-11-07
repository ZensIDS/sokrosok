@extends('layouts.app')

@section('title', $bin->code)
@section('heading', $bin->name)

@section('content')
@php $role = auth()->user()->role; @endphp
<div class="mb-4 flex items-center justify-between gap-3">
    <a href="{{ route($role . '.dashboard') }}" class="text-sm font-semibold text-brand-700 hover:underline">&larr; Kembali</a>
    @include('monitoring._live_status')
</div>

<div class="card p-6">
    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $bin->code }}</div>
    <h2 class="mt-1 text-xl font-extrabold text-slate-900">{{ $bin->name }}</h2>
    <p class="text-sm text-slate-500">{{ $bin->location ?: 'Lokasi belum diisi' }}</p>

    <div id="live-level" class="mt-6">@include('admin.bins._level', ['bin' => $bin])</div>

    <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
        <div><dt class="text-slate-500">Laporan terakhir</dt><dd id="live-last" class="font-bold text-slate-900">{{ $bin->last_reported_at?->diffForHumans() ?? 'Belum pernah' }}</dd></div>
        <div><dt class="text-slate-500">Tinggi tong</dt><dd class="font-bold text-slate-900">{{ $bin->height_cm }} cm</dd></div>
        <div>
            <dt class="text-slate-500">Lokasi di peta</dt>
            <dd class="font-bold text-slate-900">
                @if ($bin->latitude !== null && $bin->longitude !== null)
                    <a class="text-brand-700 hover:underline" target="_blank" rel="noopener"
                       href="https://www.openstreetmap.org/?mlat={{ $bin->latitude }}&mlon={{ $bin->longitude }}#map=17/{{ $bin->latitude }}/{{ $bin->longitude }}">Buka peta</a>
                @else
                    -
                @endif
            </dd>
        </div>
    </dl>
</div>

<div class="card mt-6 p-6">
    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Grafik volume</h3>
    <div id="live-chart">@include('monitoring._chart', ['logs' => $logs])</div>
</div>

<div class="card mt-6 overflow-x-auto">
    <div class="border-b border-slate-200 px-5 py-3 text-sm font-bold text-slate-900">20 laporan terakhir</div>
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr><th class="px-5 py-2.5">Waktu</th><th class="px-5 py-2.5">Volume</th><th class="px-5 py-2.5">Status</th></tr>
        </thead>
        <tbody id="live-rows" class="divide-y divide-slate-100">
            @include('monitoring._log_rows', ['logs' => $logs->take(20), 'withDistance' => false])
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
    <script>
        livePoll({
            url: @json(route($role . '.bins.data', $bin)),
            interval: 60000,
            onData: function (d) {
                liveHtml(document.getElementById('live-level'), d.level);
                liveHtml(document.getElementById('live-chart'), d.chart);
                liveHtml(document.getElementById('live-rows'), d.rows);
                document.getElementById('live-last').textContent = d.last_reported;
            }
        });
    </script>
@endpush