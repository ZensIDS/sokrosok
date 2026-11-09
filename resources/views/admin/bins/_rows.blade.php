@forelse ($bins as $bin)
    @php $wifi = $bin->wifiResetState(); @endphp
    <tr class="hover:bg-slate-50">
        <td class="px-5 py-3 font-bold text-slate-900">
            {{ $bin->code }}
            @unless ($bin->is_active)<span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">NONAKTIF</span>@endunless
        </td>
        <td class="px-5 py-3">
            <div class="font-semibold text-slate-800">{{ $bin->name }}</div>
            <div class="text-xs text-slate-500">{{ $bin->location ?: '-' }}</div>
        </td>
        <td class="px-5 py-3">@include('admin.bins._level', ['bin' => $bin])</td>
        <td class="px-5 py-3 text-slate-500">{{ $bin->last_reported_at?->diffForHumans() ?? 'Belum pernah' }}</td>
        <td class="px-5 py-3 text-xs">
            @if ($wifi === 'pending')
                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 font-bold text-level-mid">Menunggu perangkat bangun</span>
                <div class="mt-1 max-w-56 text-slate-500">Tekan tombol EN di ESP32 agar perintah langsung diambil.</div>
            @elseif ($wifi === 'portal')
                <span class="rounded-full bg-brand-50 px-2.5 py-0.5 font-bold text-brand-700">Portal WiFi dibuka</span>
                <div class="mt-1 max-w-56 text-slate-600">
                    Sambungkan HP ke WiFi <b class="font-mono">{{ $bin->code }}</b>, lalu buka <b class="font-mono">192.168.4.1</b>.
                </div>
            @else
                <span class="text-slate-500">SSID portal: <b class="font-mono text-slate-700">{{ $bin->code }}</b></span>
            @endif
        </td>
        <td class="px-5 py-3 text-right">
            <a href="{{ route('admin.bins.show', $bin) }}" class="font-semibold text-brand-700 hover:underline">Detail</a>
            <a href="{{ route('admin.bins.edit', $bin) }}" class="ml-3 font-semibold text-slate-600 hover:underline">Ubah</a>
            @if ($wifi === 'pending')
                <form method="POST" action="{{ route('admin.bins.wifi-reset.cancel', $bin) }}" class="ml-3 inline">
                    @csrf @method('DELETE')
                    <button class="font-semibold text-slate-500 hover:underline">Batalkan reset</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.bins.wifi-reset', $bin) }}" class="ml-3 inline" onsubmit="return confirm('Reset WiFi {{ $bin->code }}? Perangkat akan membuka portal WiFi {{ $bin->code }} saat bangun berikutnya.')">
                    @csrf
                    <button class="font-semibold text-level-mid hover:underline">Reset WiFi</button>
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Belum ada tong sampah. Klik <b>+ Tambah Tong</b> untuk mendaftarkan yang pertama.</td></tr>
@endforelse