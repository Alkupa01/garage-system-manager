@extends('layouts.app')
@section('title', 'Registrasi Penyewa')

@section('content')
<div class="max-w-lg mx-auto mt-6 bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4 text-center">Registrasi Akun Penyewa</h1>

    <form action="{{ route('register') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">NIK (16 Digit)</label>
            <input type="text" name="nik" value="{{ old('nik') }}" maxlength="16" minlength="16" required class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Nama Lengkap Sesuai KTP</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">No. HP / WhatsApp</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2 text-sm">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" required class="w-full border rounded px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Foto KTP (JPG/PNG, Maks 2MB)</label>
            <input type="file" name="ktp_image" accept="image/*" required class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2 rounded text-sm font-semibold">
            Daftar Sekarang
        </button>
    </form>

    <p class="text-center text-sm mt-4 text-gray-600">
        Sudah terdaftar? <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Masuk</a>
    </p>
</div>
@endsection