@extends('layouts.app')

@section('title', 'Tempat Sampah')
@section('heading', 'Tempat Sampah')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500">Daftar tong sampah pintar dan volume terakhirnya.</p>
    <div class="flex items-center gap-3">
        @include('monitoring._live_status')
        <a href="{{ route('admin.bins.create') }}" class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">+ Tambah Tong</a>
    </div>
</div>

<div class="card overflow-x-auto">
    <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3">Kode</th>
                <th class="px-5 py-3">Nama / Lokasi</th>
                <th class="px-5 py-3">Volume</th>
                <th class="px-5 py-3">Laporan Terakhir</th>
                <th class="px-5 py-3">WiFi</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody id="bin-rows" class="divide-y divide-slate-100">
            @include('admin.bins._rows')
        </tbody>
    </table>
</div>
<div id="bin-pager" class="mt-4">{{ $bins->links() }}</div>
@endsection

@push('scripts')
    <script>
        livePoll({
            url: window.location.href,
            interval: 60000,
            onData: function (d) {
                liveHtml(document.getElementById('bin-rows'), d.rows);
                liveHtml(document.getElementById('bin-pager'), d.pager);
            }
        });
    </script>
@endpush