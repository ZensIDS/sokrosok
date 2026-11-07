@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Panel brand --}}
    <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-32 -left-16 h-[28rem] w-[28rem] rounded-full bg-white/5"></div>

        <div class="relative flex items-center gap-3">
            <img src="{{ asset('images/logo.svg') }}" alt="" class="h-12 w-12 rounded-2xl ring-2 ring-white/30">
            <span class="text-2xl font-extrabold tracking-tight">Sokrosok</span>
        </div>

        <div class="relative max-w-md">
            <h2 class="text-4xl font-extrabold leading-tight">Pantau isi tong sampah, kapan saja.</h2>
            <p class="mt-4 text-brand-100">Sensor di setiap tong mengirim volume sampah secara otomatis, sehingga penjemputan bisa dilakukan tepat waktu.</p>

            <div class="mt-10 space-y-3">
                @foreach ([['Penuh', 'bg-red-400', 'w-[92%]'], ['Sedang', 'bg-amber-300', 'w-[58%]'], ['Kosong', 'bg-emerald-300', 'w-[18%]']] as [$l, $c, $w])
                    <div class="flex items-center gap-3">
                        <span class="w-14 text-xs font-semibold text-brand-100">{{ $l }}</span>
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full {{ $c }} {{ $w }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="relative text-xs text-brand-200">© {{ date('Y') }} Sokrosok</p>
    </div>

    {{-- Form --}}
    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-11 w-11">
                <span class="text-2xl font-extrabold tracking-tight text-slate-900">Sokrosok</span>
            </div>

            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Selamat datang</h1>
            <p class="mt-1 text-sm text-slate-500">Masuk untuk melihat status tong sampah.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           placeholder="nama@email.com" class="input @error('email') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="••••••••" class="input">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Ingat saya
                </label>

                <button type="submit" class="btn-primary">Masuk</button>
            </form>
        </div>
    </div>
</div>
@endsection