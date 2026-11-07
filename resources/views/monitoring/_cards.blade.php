@if ($bins->isEmpty())
    <div class="card flex flex-col items-center px-6 py-14 text-center">
        <img src="{{ asset('images/logo.svg') }}" alt="" class="h-16 w-16 opacity-90">
        <h3 class="mt-5 text-lg font-bold text-slate-900">Belum ada tong sampah</h3>
        <p class="mt-1 max-w-md text-sm text-slate-500">
            @if ($role === 'admin')
                <a href="{{ route('admin.bins.create') }}" class="font-semibold text-brand-700 hover:underline">Tambah tong pertama</a>, lalu hubungkan sensor ESP32. Volume akan tampil di sini.
            @else
                Tong akan tampil di sini setelah admin mendaftarkannya.
            @endif
        </p>
    </div>
@else
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($bins as $bin)
            @php $url = $role === 'admin' ? route('admin.bins.show', $bin) : route($role . '.bins.show', $bin); @endphp
            <a href="{{ $url }}" class="card block p-5 transition hover:border-brand-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $bin->code }}</div>
                        <div class="truncate text-base font-extrabold text-slate-900">{{ $bin->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $bin->location ?: '-' }}</div>
                    </div>
                    @if ($bin->isOffline())
                        <span class="shrink-0 rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600">Offline</span>
                    @endif
                </div>
                <div class="mt-4">@include('admin.bins._level', ['bin' => $bin])</div>
                <div class="mt-3 text-xs text-slate-500">Laporan terakhir: {{ $bin->last_reported_at?->diffForHumans() ?? 'belum pernah' }}</div>
            </a>
        @endforeach
    </div>
@endif