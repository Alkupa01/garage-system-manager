@extends('layouts.app')
@section('title', 'Kelola Unit Garasi')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ editMode: false, editUrl: '', code: '', type: 'S', daily_rate: '', kwh_rate: 0 }">
    <!-- Form Tambah / Edit -->
    <div class="bg-white p-5 rounded shadow h-fit">
        <h2 class="text-lg font-bold mb-3" x-text="editMode ? 'Edit Unit Garasi' : 'Tambah Unit Garasi'"></h2>

        <form :action="editMode ? editUrl : '{{ route('admin.garages.store') }}'" method="POST" class="space-y-3">
            @csrf
            <template x-if="editMode">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <div>
                <label class="block text-xs font-medium mb-1">Kode Garasi (Contoh: S-001, C-001, E-001)</label>
                <input type="text" name="code" x-model="code" required maxlength="10" class="w-full border rounded px-3 py-2 text-sm uppercase">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Tipe Garasi</label>
                <select name="type" x-model="type" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="S">S - Sepeda</option>
                    <option value="C">C - Mobil</option>
                    <option value="E">E - Mobil Listrik (EV)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Tarif Harian (Rp)</label>
                <input type="number" name="daily_rate" x-model="daily_rate" min="0" step="1000" required class="w-full border rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-0.5">Tarif bulanan otomatis: 25x tarif harian (30 hari).</p>
            </div>
            <div x-show="type === 'E'">
                <label class="block text-xs font-medium mb-1">Tarif Charging per kWh (Rp)</label>
                <input type="number" name="kwh_rate" x-model="kwh_rate" min="0" step="100" class="w-full border rounded px-3 py-2 text-sm">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded text-sm font-medium" x-text="editMode ? 'Update' : 'Simpan'"></button>
                <button type="button" x-show="editMode" @click="editMode = false; code = ''; type = 'S'; daily_rate = ''; kwh_rate = 0;" class="bg-gray-300 px-3 py-2 rounded text-sm">Batal</button>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Garasi -->
    <div class="lg:col-span-2 bg-white p-5 rounded shadow">
        <h2 class="text-lg font-bold mb-3">Daftar Unit Garasi ({{ $garages->count() }})</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2 text-left">Kode</th>
                        <th class="border p-2 text-center">Tipe</th>
                        <th class="border p-2 text-right">Tarif Harian</th>
                        <th class="border p-2 text-right">Tarif Bulanan (25x)</th>
                        <th class="border p-2 text-right">Tarif / kWh</th>
                        <th class="border p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($garages as $garage)
                        <tr>
                            <td class="border p-2 font-bold">{{ $garage->code }}</td>
                            <td class="border p-2 text-center">
                                {{ $garage->type }} - {{ $garage->type === 'S' ? 'Sepeda' : ($garage->type === 'C' ? 'Mobil' : 'Mobil Listrik') }}
                            </td>
                            <td class="border p-2 text-right">Rp {{ number_format($garage->daily_rate, 0, ',', '.') }}</td>
                            <td class="border p-2 text-right">Rp {{ number_format($garage->getMonthlyRate(), 0, ',', '.') }}</td>
                            <td class="border p-2 text-right">
                                {{ $garage->type === 'E' ? 'Rp ' . number_format($garage->kwh_rate, 0, ',', '.') : '-' }}
                            </td>
                            <td class="border p-2 text-center space-x-1">
                                <button type="button"
                                    @click="editMode = true; editUrl = '{{ route('admin.garages.update', $garage) }}'; code = '{{ $garage->code }}'; type = '{{ $garage->type }}'; daily_rate = '{{ (float) $garage->daily_rate }}'; kwh_rate = '{{ (float) $garage->kwh_rate }}';"
                                    class="bg-amber-500 hover:bg-amber-600 text-white px-2 py-1 rounded text-xs">Edit</button>

                                <form action="{{ route('admin.garages.destroy', $garage) }}" method="POST" class="inline" onsubmit="return confirm('Hapus unit garasi ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border p-4 text-center text-gray-500">Belum ada unit garasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection