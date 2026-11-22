@extends('layouts.app')
@section('title', 'Profil & Verifikasi KTP')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded shadow space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Profil Penyewa</h1>
        <span class="px-2.5 py-1 rounded text-xs font-bold
            {{ $user->verification_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($user->verification_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
            STATUS: {{ strtoupper($user->verification_status) }}
        </span>
    </div>

    <div class="text-sm space-y-1 border-t border-b py-3">
        <div><span class="text-gray-500">NIK:</span> <span class="font-semibold">{{ $user->nik }}</span></div>
        <div><span class="text-gray-500">Nama:</span> <span class="font-semibold">{{ $user->name }}</span></div>
        <div><span class="text-gray-500">No. HP:</span> <span class="font-semibold">{{ $user->phone }}</span></div>
        <div><span class="text-gray-500">Email:</span> <span class="font-semibold">{{ $user->email }}</span></div>
    </div>

    @if($user->ktp_image_path)
        <div>
            <p class="text-xs font-medium text-gray-500 mb-1">KTP Terunggah:</p>
            <img src="{{ route('files.show', $user->ktp_image_path) }}" alt="KTP" class="max-h-52 rounded border">
        </div>
    @endif

    <form action="{{ route('tenant.profile.ktp') }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Upload Ulang Foto KTP (Jika Ditolak / Perbarui)</label>
            <input type="file" name="ktp_image" accept="image/*" required class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded text-sm font-medium">
            Simpan KTP Baru
        </button>
    </form>
</div>
@endsection