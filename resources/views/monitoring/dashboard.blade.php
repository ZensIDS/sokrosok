@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Halo, {{ auth()->user()->name }} 👋</h2>
    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
        <span>Volume sampah di tiap tong.</span>
        @include('monitoring._live_status')
    </p>
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['total', 'Total Tong', 'text-slate-900', 'bg-slate-100'],
        ['full', 'Penuh', 'text-level-full', 'bg-red-50'],
        ['medium', 'Sedang', 'text-level-mid', 'bg-amber-50'],
        ['empty', 'Kosong', 'text-level-empty', 'bg-green-50'],
    ] as [$key, $label, $text, $bg])
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-500">{{ $label }}</span>
                <span class="h-9 w-9 rounded-xl {{ $bg }}"></span>
            </div>
            <div class="mt-3 text-3xl font-extrabold {{ $text }}" data-count="{{ $key }}">{{ $counts[$key] }}</div>
        </div>
    @endforeach
</div>

<div id="offline-alert" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 {{ $counts['offline'] > 0 ? '' : 'hidden' }}">
    <span id="offline-count">{{ $counts['offline'] }}</span> tong tidak melapor atau sensornya bermasalah. Data volume yang tampil bisa sudah usang.
</div>

<div id="map-card" class="card mt-6 overflow-hidden {{ $markers->isEmpty() ? 'hidden' : '' }}">
    <div class="border-b border-slate-200 px-5 py-3 text-sm font-bold text-slate-900">Peta tong</div>
    <div id="map" class="h-80 w-full"></div>
</div>

<div id="bin-cards" class="mt-6">@include('monitoring._cards')</div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            var map = null, pins = {}, fitted = false;

            function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

            function popup(m) {
                return '<b>' + esc(m.name) + '</b><br>' + esc(m.code) + '<br>' + esc(m.label) +
                    (m.percent !== null ? ' · ' + m.percent + '%' : '') +
                    '<br><a href="' + encodeURI(m.url) + '">Detail</a>';
            }

            function renderMap(markers) {
                var card = document.getElementById('map-card');
                if (typeof L === 'undefined') { return; }
                if (!markers.length) { card.classList.add('hidden'); return; }
                card.classList.remove('hidden');

                if (!map) {
                    map = L.map('map');
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19, attribution: '&copy; OpenStreetMap'
                    }).addTo(map);
                }
                map.invalidateSize();

                var seen = {}, pts = [];
                markers.forEach(function (m) {
                    var style = { radius: 11, color: '#fff', weight: 2, fillColor: m.color, fillOpacity: 0.95 };
                    seen[m.code] = true;
                    pts.push([m.lat, m.lng]);
                    if (pins[m.code]) {
                        pins[m.code].setLatLng([m.lat, m.lng]);
                        pins[m.code].setStyle(style);
                        pins[m.code].setPopupContent(popup(m));
                    } else {
                        pins[m.code] = L.circleMarker([m.lat, m.lng], style).addTo(map).bindPopup(popup(m));
                    }
                });
                Object.keys(pins).forEach(function (code) {
                    if (!seen[code]) { map.removeLayer(pins[code]); delete pins[code]; }
                });

                // Zoom/geser hanya diatur sekali, supaya refresh tidak mengganggu pengguna.
                if (!fitted) {
                    if (pts.length === 1) { map.setView(pts[0], 16); } else { map.fitBounds(pts, { padding: [40, 40] }); }
                    fitted = true;
                }
            }

            function apply(d) {
                ['total', 'full', 'medium', 'empty'].forEach(function (k) {
                    var el = document.querySelector('[data-count="' + k + '"]');
                    if (el) { el.textContent = d.counts[k]; }
                });
                document.getElementById('offline-count').textContent = d.counts.offline;
                document.getElementById('offline-alert').classList.toggle('hidden', d.counts.offline < 1);
                liveHtml(document.getElementById('bin-cards'), d.cards);
                renderMap(d.markers);
            }

            renderMap(@json($markers));
            livePoll({ url: @json(route($role . '.dashboard.data')), interval: 60000, onData: apply });
        })();
    </script>
@endpush