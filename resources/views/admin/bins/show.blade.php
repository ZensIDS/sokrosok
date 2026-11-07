@extends('layouts.app')

@section('title', $bin->code)
@section('heading', $bin->name)

@section('content')
<div class="mb-4 flex justify-end">@include('monitoring._live_status')</div>
<div class="grid gap-6 xl:grid-cols-3">
    <div class="card p-6 xl:col-span-2">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $bin->code }}</div>
                <h2 class="mt-1 text-xl font-extrabold text-slate-900">{{ $bin->name }}</h2>
                <p class="text-sm text-slate-500">{{ $bin->location ?: 'Lokasi belum diisi' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.bins.edit', $bin) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Ubah</a>
                <form method="POST" action="{{ route('admin.bins.destroy', $bin) }}" onsubmit="return confirm('Hapus tong {{ $bin->code }} beserta seluruh riwayatnya?')">
                    @csrf @method('DELETE')
                    <button class="rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Hapus</button>
                </form>
            </div>
        </div>

        <div id="live-level" class="mt-6">@include('admin.bins._level', ['bin' => $bin])</div>

        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-500">Tinggi tong</dt><dd class="font-bold text-slate-900">{{ $bin->height_cm }} cm</dd></div>
            <div><dt class="text-slate-500">Interval lapor</dt><dd class="font-bold text-slate-900">{{ round($bin->sleep_interval_sec / 3600, 1) }} jam</dd></div>
            <div><dt class="text-slate-500">Laporan terakhir</dt><dd id="live-last" class="font-bold text-slate-900">{{ $bin->last_reported_at?->diffForHumans() ?? 'Belum pernah' }}</dd></div>
            <div><dt class="text-slate-500">Koordinat</dt><dd class="font-bold text-slate-900">{{ $bin->latitude !== null ? $bin->latitude . ', ' . $bin->longitude : '-' }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-bold text-slate-900">{{ $bin->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
        </dl>
    </div>

    <div class="card p-6">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Kredensial perangkat</h3>
        <p class="mt-2 text-xs text-slate-500">Tulis ke firmware ESP32 (<code>BIN_CODE</code> dan <code>API_KEY</code>). Jaga kerahasiaannya.</p>
        <div class="mt-4 space-y-3 text-sm">
            <div>
                <div class="text-xs font-semibold text-slate-500">BIN_CODE</div>
                <div class="rounded-lg bg-slate-100 px-3 py-2 font-mono text-slate-800">{{ $bin->code }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500">API_KEY</div>
                <div class="break-all rounded-lg bg-slate-100 px-3 py-2 font-mono text-slate-800">{{ $bin->api_key }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500">API_BASE_URL</div>
                <div class="break-all rounded-lg bg-slate-100 px-3 py-2 font-mono text-slate-800">{{ url('/api/bins') }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.bins.regenerate-key', $bin) }}" class="mt-4" onsubmit="return confirm('Buat API key baru? Perangkat yang memakai key lama akan putus sampai di-flash ulang.')">
            @csrf
            <button class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Buat ulang API key</button>
        </form>
    </div>
</div>

<div class="card mt-6 p-6">
    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Grafik volume</h3>
    <div id="live-chart">@include('monitoring._chart', ['logs' => $logs])</div>
</div>

<div class="card mt-6 overflow-x-auto">
    <div class="border-b border-slate-200 px-5 py-3 text-sm font-bold text-slate-900">20 laporan terakhir</div>
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr><th class="px-5 py-2.5">Waktu</th><th class="px-5 py-2.5">Volume</th><th class="px-5 py-2.5">Jarak</th><th class="px-5 py-2.5">Status</th></tr>
        </thead>
        <tbody id="live-rows" class="divide-y divide-slate-100">
            @include('monitoring._log_rows', ['logs' => $logs, 'withDistance' => true])
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
    <script>
        livePoll({
            url: @json(route('admin.bins.data', $bin)),
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