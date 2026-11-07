@forelse ($bins as $bin)
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
        <td class="px-5 py-3 text-right">
            <a href="{{ route('admin.bins.show', $bin) }}" class="font-semibold text-brand-700 hover:underline">Detail</a>
            <a href="{{ route('admin.bins.edit', $bin) }}" class="ml-3 font-semibold text-slate-600 hover:underline">Ubah</a>
        </td>
    </tr>
@empty
    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada tong sampah. Klik <b>+ Tambah Tong</b> untuk mendaftarkan yang pertama.</td></tr>
@endforelse