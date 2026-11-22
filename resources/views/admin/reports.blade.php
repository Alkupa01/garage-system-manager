@extends('layouts.app')
@section('title', 'Laporan Pendapatan & Okupansi')

@section('content')
<div class="space-y-6">
    <!-- Filter Tanggal -->
    <div class="bg-white p-5 rounded shadow">
        <h1 class="text-xl font-bold mb-3">Laporan Keuangan & Penggunaan Garasi</h1>
        <form action="{{ route('admin.reports.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium mb-1">Dari Tanggal</label>
                <input type="date" name="from" value="{{ $from }}" class="border rounded px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Sampai Tanggal</label>
                <input type="date" name="to" value="{{ $to }}" class="border rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit" class="bg-slate-900 text-white px-4 py-1.5 rounded text-sm font-medium">Filter</button>
            <a href="{{ route('admin.reports.index') }}" class="bg-gray-200 px-3 py-1.5 rounded text-sm">Reset</a>
        </form>
    </div>

    <!-- Ringkasan Pendapatan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded shadow border-l-4 border-blue-600">
            <div class="text-xs text-gray-500">Pendapatan Sewa Awal</div>
            <div class="text-xl font-bold mt-1">Rp {{ number_format($summary['initial_revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow border-l-4 border-amber-500">
            <div class="text-xs text-gray-500">Pendapatan Perpanjangan (Extend)</div>
            <div class="text-xl font-bold mt-1">Rp {{ number_format($summary['extension_revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow border-l-4 border-emerald-600">
            <div class="text-xs text-gray-500">Total Pendapatan Bersih</div>
            <div class="text-xl font-bold mt-1 text-emerald-700">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Status Transaksi -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($summary['status_counts'] as $status => $count)
            <div class="bg-white p-4 rounded shadow text-center">
                <div class="text-xs uppercase text-gray-500">{{ $status }}</div>
                <div class="text-2xl font-bold mt-1">{{ $count }}</div>
            </div>
        @endforeach
    </div>

    <!-- Rincian Per Unit Garasi -->
    <div class="bg-white p-5 rounded shadow">
        <h2 class="text-lg font-bold mb-3">Rincian Pendapatan Per Unit Garasi</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2 text-left">Kode Garasi</th>
                        <th class="border p-2 text-center">Tipe</th>
                        <th class="border p-2 text-center">Jumlah Transaksi Valid</th>
                        <th class="border p-2 text-right">Total Nilai Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['garage_breakdown'] as $g)
                        <tr>
                            <td class="border p-2 font-bold">{{ $g->code }}</td>
                            <td class="border p-2 text-center">{{ $g->type }}</td>
                            <td class="border p-2 text-center">{{ $g->rentals_count }}</td>
                            <td class="border p-2 text-right font-semibold">Rp {{ number_format($g->total_revenue ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border p-4 text-center text-gray-500">Belum ada data garasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection