@extends('layouts.app')
@section('title', 'Verifikasi Penyewa')

@section('content')
<div class="bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4">Daftar & Verifikasi KTP Penyewa</h1>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">NIK & Nama</th>
                    <th class="border p-2 text-left">Kontak</th>
                    <th class="border p-2 text-center">Foto KTP</th>
                    <th class="border p-2 text-center">Status</th>
                    <th class="border p-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    <tr>
                        <td class="border p-2">
                            <div class="font-semibold">{{ $tenant->name }}</div>
                            <div class="text-xs text-gray-500">NIK: {{ $tenant->nik }}</div>
                        </td>
                        <td class="border p-2">
                            <div>{{ $tenant->email }}</div>
                            <div class="text-xs text-gray-500">{{ $tenant->phone }}</div>
                        </td>
                        <td class="border p-2 text-center">
                            @if($tenant->ktp_image_path)
                                <a href="{{ route('files.show', $tenant->ktp_image_path) }}" target="_blank" class="text-blue-600 underline text-xs">
                                    Lihat KTP
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="border p-2 text-center">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $tenant->verification_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($tenant->verification_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ strtoupper($tenant->verification_status) }}
                            </span>
                        </td>
                        <td class="border p-2 text-center space-x-1">
                            <form action="{{ route('admin.tenants.verify', $tenant) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded text-xs">Approve</button>
                            </form>
                            <form action="{{ route('admin.tenants.verify', $tenant) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2.5 py-1 rounded text-xs">Reject</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="border p-4 text-center text-gray-500">Belum ada penyewa terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection