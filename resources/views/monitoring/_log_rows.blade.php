@php $labels = ['full' => 'Penuh', 'medium' => 'Sedang', 'empty' => 'Kosong']; @endphp
@forelse ($logs as $log)
    <tr>
        <td class="px-5 py-2.5 text-slate-600">{{ $log->created_at->format('d M Y H:i') }}</td>
        <td class="px-5 py-2.5 font-bold text-slate-800">{{ $log->fill_level_percent !== null ? $log->fill_level_percent . '%' : '-' }}</td>
        @if ($withDistance)
            <td class="px-5 py-2.5 text-slate-600">{{ $log->distance_cm !== null ? $log->distance_cm . ' cm' : '-' }}</td>
        @endif
        <td class="px-5 py-2.5 text-slate-600">{{ $log->sensor_error ? 'Sensor error' : ($labels[$log->status] ?? $log->status) }}</td>
    </tr>
@empty
    <tr><td colspan="{{ $withDistance ? 4 : 3 }}" class="px-5 py-8 text-center text-slate-500">Belum ada laporan dari perangkat.</td></tr>
@endforelse