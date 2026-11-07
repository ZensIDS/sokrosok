@php $edit = $user->exists; @endphp
@extends('layouts.app')

@section('title', ($edit ? 'Ubah ' : 'Tambah ') . $label)
@section('heading', ($edit ? 'Ubah ' : 'Tambah ') . $label)

@section('content')
<form method="POST" action="{{ $edit ? route($prefix . '.update', $user) : route($prefix . '.store') }}" class="card max-w-xl space-y-5 p-6">
    @csrf
    @if ($edit) @method('PUT') @endif

    <div>
        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama lengkap</label>
        <input name="name" value="{{ old('name', $user->name) }}" class="input" required>
        @error('name')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
            @error('email')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Telepon</label>
            <input name="phone" value="{{ old('phone', $user->phone) }}" class="input">
            @error('phone')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Kata sandi</label>
        <input type="password" name="password" class="input" autocomplete="new-password" @required(! $edit)>
        <p class="mt-1 text-xs text-slate-500">{{ $edit ? 'Kosongkan bila tidak diganti. ' : '' }}Minimal 8 karakter.</p>
        @error('password')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
        <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600" @checked(old('is_active', $user->is_active))>
        Akun aktif
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Simpan</button>
        <a href="{{ route($prefix . '.index') }}" class="text-sm font-semibold text-slate-500 hover:underline">Batal</a>
    </div>
</form>
@endsection