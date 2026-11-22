@extends('layouts.app')
@section('title', 'Login - Sistem Sewa Garasi')

@section('content')
<div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4 text-center">Masuk Sistem Garasi</h1>

    <form action="{{ route('login') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2 rounded text-sm font-semibold">
            Masuk
        </button>
    </form>

    <p class="text-center text-sm mt-4 text-gray-600">
        Belum punya akun penyewa?
        <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-medium">Daftar & Upload KTP</a>
    </p>
</div>
@endsection