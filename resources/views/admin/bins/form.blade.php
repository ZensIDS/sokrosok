@php $edit = $bin->exists; @endphp
@extends('layouts.app')

@section('title', $edit ? 'Ubah Tempat Sampah' : 'Tambah Tempat Sampah')
@section('heading', $edit ? 'Ubah Tempat Sampah' : 'Tambah Tempat Sampah')

@section('content')
<form method="POST" action="{{ $edit ? route('admin.bins.update', $bin) : route('admin.bins.store') }}" class="card max-w-2xl space-y-5 p-6">
    @csrf
    @if ($edit) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Kode perangkat</label>
            <input name="code" value="{{ old('code', $bin->code) }}" placeholder="BIN-001" class="input uppercase" required>
            @error('code')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama tong</label>
            <input name="name" value="{{ old('name', $bin->name) }}" placeholder="Tong Taman Kota" class="input" required>
            @error('name')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Lokasi / alamat</label>
        <input name="location" value="{{ old('location', $bin->location) }}" class="input">
        @error('location')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Latitude</label>
            <input name="latitude" value="{{ old('latitude', $bin->latitude) }}" placeholder="-8.2000000" class="input">
            @error('latitude')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Longitude</label>
            <input name="longitude" value="{{ old('longitude', $bin->longitude) }}" placeholder="111.1000000" class="input">
            @error('longitude')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tinggi tong (cm)</label>
            <input type="number" name="height_cm" value="{{ old('height_cm', $bin->height_cm) }}" class="input" required>
            <p class="mt-1 text-xs text-slate-500">Jarak sensor ke dasar tong saat kosong.</p>
            @error('height_cm')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Interval tidur (detik)</label>
            <input type="number" name="sleep_interval_sec" value="{{ old('sleep_interval_sec', $bin->sleep_interval_sec) }}" class="input" required>
            <p class="mt-1 text-xs text-slate-500">14400 = lapor tiap 4 jam.</p>
            @error('sleep_interval_sec')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600" @checked(old('is_active', $bin->is_active))>
        Aktif
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Simpan</button>
        <a href="{{ route('admin.bins.index') }}" class="text-sm font-semibold text-slate-500 hover:underline">Batal</a>
    </div>
</form>
@endsection