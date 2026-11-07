@extends('layouts.app')

@section('title', $label)
@section('heading', $label)

@section('content')
<div class="mb-5 flex items-center justify-between">
    <p class="text-sm text-slate-500">Kelola akun {{ strtolower($label) }}.</p>
    <a href="{{ route($prefix . '.create') }}" class="inline-flex items-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">+ Tambah {{ $label }}</a>
</div>

<div class="card overflow-x-auto">
    <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-5 py-3">Nama</th>
                <th class="px-5 py-3">Email</th>
                <th class="px-5 py-3">Telepon</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $u)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-semibold text-slate-900">{{ $u->name }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $u->phone ?: '-' }}</td>
                    <td class="px-5 py-3">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $u->is_active ? 'bg-green-50 text-level-empty' : 'bg-slate-200 text-slate-600' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route($prefix . '.edit', $u) }}" class="font-semibold text-brand-700 hover:underline">Ubah</a>
                        <form method="POST" action="{{ route($prefix . '.destroy', $u) }}" class="ml-3 inline" onsubmit="return confirm('Hapus {{ $u->name }}?')">
                            @csrf @method('DELETE')
                            <button class="font-semibold text-red-600 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada {{ strtolower($label) }}.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection