@php
    $user = auth()->user();
    $menus = match ($user->role) {
        'admin' => [
            ['Dashboard', 'admin.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Tempat Sampah', 'admin.bins.index', 'M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3'],
            ['Petugas', 'admin.petugas.index', 'M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 21a8 8 0 0116 0'],
            // Nasabah disembunyikan sementara (route tetap ada). Aktifkan lagi dengan menghapus tanda // di bawah:
            // ['Nasabah', 'admin.nasabah.index', 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zM2 20v-2a4 4 0 014-4h6a4 4 0 014 4v2'],
        ],
        'petugas' => [
            ['Dashboard', 'petugas.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
        ],
        default => [
            ['Dashboard', 'nasabah.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
        <div class="flex items-center gap-3 px-6 py-5">
            <img src="{{ asset('images/logo.svg') }}" alt="Sokrosok" class="h-10 w-10">
            <div>
                <div class="text-lg font-extrabold leading-tight tracking-tight text-slate-900">Sokrosok</div>
                <div class="text-[11px] font-medium text-slate-500">Smart Bin Monitoring</div>
            </div>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-2">
            @foreach ($menus as [$label, $route, $icon])
                @php $active = $route && (str_ends_with($route, '.dashboard') ? request()->routeIs($route) : request()->routeIs(\Illuminate\Support\Str::beforeLast($route, '.') . '.*')); @endphp
                @if ($route)
                    <a href="{{ route($route) }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                        {{ $label }}
                    </a>
                @else
                    <div class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                        {{ $label }}
                        <span class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">Segera</span>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                    <div class="text-xs text-slate-500">{{ $user->roleLabel() }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Keluar</button>
            </form>
        </div>
    </aside>

    <div id="backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden"></div>

    {{-- Konten --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/80 px-4 py-3 backdrop-blur lg:px-8">
            <button id="menu-btn" type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-bold text-slate-900">@yield('heading', 'Dashboard')</h1>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @if (session('status'))
                <div class="mb-5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<script>
    (function () {
        var s = document.getElementById('sidebar'), b = document.getElementById('backdrop');
        function toggle(open) {
            s.classList.toggle('-translate-x-full', !open);
            b.classList.toggle('hidden', !open);
        }
        document.getElementById('menu-btn').addEventListener('click', function () { toggle(true); });
        b.addEventListener('click', function () { toggle(false); });
    })();
</script>
<script>
    // Helper polling AJAX: dipakai dashboard dan detail tong.
    window.liveHtml = function (el, html) {
        if (!el || el._liveHtml === html) { return; }
        el.innerHTML = html;
        el._liveHtml = html;
    };
    window.livePoll = function (opts) {
        var interval = opts.interval || 60000, retry = Math.min(interval, 15000);
        var busy = false, nextAt = Date.now() + interval, span = interval, ok = true, lastTime = null;
        var $ = function (id) { return document.getElementById(id); };
        var dot = $('live-dot'), text = $('live-text'), bar = $('live-bar'), count = $('live-count'), icon = $('live-icon'), btn = $('live-refresh');

        function paint() {
            if (!text) { return; }
            var left = Math.max(0, nextAt - Date.now());
            if (busy) {
                text.textContent = 'Memperbarui...';
                if (bar) { bar.style.width = '100%'; }
                if (count) { count.textContent = ''; }
            } else {
                text.textContent = ok ? 'Diperbarui ' + lastTime : 'Gagal memperbarui';
                if (bar) { bar.style.width = (100 - left / span * 100) + '%'; }
                if (count) { count.textContent = Math.ceil(left / 1000) + ' dtk'; }
            }
            if (dot) {
                dot.classList.toggle('bg-emerald-500', ok);
                dot.classList.toggle('bg-red-500', !ok);
                dot.classList.toggle('animate-pulse', busy);
            }
            if (icon) { icon.classList.toggle('animate-spin', busy); }
        }

        function tick(force) {
            if (busy || (document.hidden && !force)) { return; }
            busy = true; paint();
            fetch(opts.url || window.location.href, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (r) {
                if (r.status === 401 || r.status === 419) { window.location.reload(); throw new Error('session'); }
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            }).then(function (d) {
                opts.onData(d);
                ok = true; lastTime = d.updated_at; span = interval;
            }).catch(function (e) {
                if (e.message === 'session') { return; }
                ok = false; span = retry;
            }).then(function () {
                busy = false; nextAt = Date.now() + span; paint();
            });
        }

        lastTime = text ? text.textContent.replace('Diperbarui ', '') : '';
        setInterval(function () {
            if (!busy && !document.hidden && Date.now() >= nextAt) { tick(); } else { paint(); }
        }, 250);
        document.addEventListener('visibilitychange', function () { if (!document.hidden && Date.now() >= nextAt) { tick(); } });
        if (btn) { btn.addEventListener('click', function () { tick(true); }); }
        paint();
    };
</script>
@stack('scripts')
</body>
</html>