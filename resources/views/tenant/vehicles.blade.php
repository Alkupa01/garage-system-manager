@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{
        type: '{{ old('type', 'standard') }}',
        editModalOpen: false,
        editAction: '',
        editData: {
            type: 'standard',
            license_plate: '',
            brand_model: '',
            battery_capacity_kwh: '',
            remaining_kwh: ''
        },
        openEdit(vehicle, actionUrl) {
            this.editAction = actionUrl;
            this.editData = {
                type: vehicle.type,
                license_plate: vehicle.license_plate ?? '',
                brand_model: vehicle.brand_model,
                battery_capacity_kwh: vehicle.battery_capacity_kwh ?? '',
                remaining_kwh: vehicle.remaining_kwh ?? ''
            };
            this.editModalOpen = true;
        }
     }">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Form Tambah Kendaraan -->
        <div class="lg:col-span-4 bg-white rounded-xl shadow-sm border border-slate-200 p-6 h-fit">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Daftarkan Kendaraan</h2>

            <form action="{{ route('tenant.vehicles.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipe Kendaraan</label>
                    <select name="type" x-model="type"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="standard">Sepeda / Motor (Garasi Tipe S)</option>
                        <option value="charging">Mobil (Garasi Tipe C)</option>
                        <option value="exclusive">Mobil Listrik / EV (Garasi Tipe C & E)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Plat Nomor
                        <span x-show="type === 'standard'" class="text-xs font-normal text-slate-500">(Opsional untuk Sepeda Ontel)</span>
                        <span x-show="type !== 'standard'" class="text-xs font-normal text-red-600">*</span>
                    </label>
                    <input type="text"
                           name="license_plate"
                           value="{{ old('license_plate') }}"
                           :required="type !== 'standard'"
                           :placeholder="type === 'standard' ? 'Kosongkan jika sepeda ontel / isi jika motor' : 'L 1234 AB'"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 uppercase placeholder:normal-case placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Merk & Model</label>
                    <input type="text"
                           name="brand_model"
                           value="{{ old('brand_model') }}"
                           required
                           autocomplete="off"
                           placeholder="Hyundai Ioniq 5"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>

                <div x-show="type === 'exclusive'" x-cloak class="space-y-4 pt-3 border-t border-slate-200">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kapasitas Maksimal Baterai (kWh)</label>
                        <input type="number" step="0.01" min="1" name="battery_capacity_kwh" value="{{ old('battery_capacity_kwh') }}"
                               :required="type === 'exclusive'"
                               placeholder="Contoh: 70"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Sisa Baterai Saat Ini (kWh)</label>
                        <input type="number" step="0.01" min="0" name="remaining_kwh" value="{{ old('remaining_kwh') }}"
                               :required="type === 'exclusive'"
                               placeholder="Contoh: 20"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Kendaraan -->
        <div class="lg:col-span-8 bg-white rounded-xl shadow-sm border border-slate-200 p-6 h-fit">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Daftar Kendaraan Saya ({{ $vehicles->count() }})</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse border border-slate-300">
                    <thead class="bg-slate-100 text-slate-700 font-semibold">
                        <tr>
                            <th class="border border-slate-300 px-3 py-2.5">Plat Nomor</th>
                            <th class="border border-slate-300 px-3 py-2.5">Merk / Model</th>
                            <th class="border border-slate-300 px-3 py-2.5 text-center">Tipe</th>
                            <th class="border border-slate-300 px-3 py-2.5 text-right">Kebutuhan Charge</th>
                            <th class="border border-slate-300 px-3 py-2.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr class="hover:bg-slate-50">
                                <td class="border border-slate-300 px-3 py-2.5 font-semibold text-slate-900">
                                    {{ $vehicle->license_plate ?? '-' }}
                                </td>
                                <td class="border border-slate-300 px-3 py-2.5 text-slate-700">
                                    {{ $vehicle->brand_model }}
                                </td>
                                <td class="border border-slate-300 px-3 py-2.5 text-center font-semibold text-xs">
                                    @if ($vehicle->type === 'standard')
                                        SEPEDA / MOTOR (S)
                                    @elseif ($vehicle->type === 'charging')
                                        MOBIL (C)
                                    @else
                                        MOBIL LISTRIK (C / E)
                                    @endif
                                </td>
                                <td class="border border-slate-300 px-3 py-2.5 text-right text-slate-700">
                                    @if ($vehicle->type === 'exclusive')
                                        @php
                                            $needed = max(0, (float) $vehicle->battery_capacity_kwh - (float) $vehicle->remaining_kwh);
                                        @endphp
                                        <span class="font-semibold">{{ number_format($needed, 0) }} kWh</span>
                                        <span class="text-xs text-slate-400">({{ number_format($vehicle->remaining_kwh, 0) }}/{{ number_format($vehicle->battery_capacity_kwh, 0) }})</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="border border-slate-300 px-3 py-2.5 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                                @click='openEdit(@json($vehicle), "{{ route('tenant.vehicles.update', $vehicle) }}")'
                                                class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded">
                                            Edit
                                        </button>
                                        <form action="{{ route('tenant.vehicles.destroy', $vehicle) }}" method="POST" onsubmit="return confirm('Hapus kendaraan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border border-slate-300 px-3 py-6 text-center text-slate-500">
                                    Belum ada kendaraan yang didaftarkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Edit Kendaraan -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.away="editModalOpen = false" class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Edit Kendaraan</h3>

            <form :action="editAction" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipe Kendaraan</label>
                    <select name="type" x-model="editData.type"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="standard">Sepeda / Motor (Garasi Tipe S)</option>
                        <option value="charging">Mobil (Garasi Tipe C)</option>
                        <option value="exclusive">Mobil Listrik / EV (Garasi Tipe C & E)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Plat Nomor
                        <span x-show="editData.type === 'standard'" class="text-xs font-normal text-slate-500">(Opsional untuk Sepeda Ontel)</span>
                    </label>
                    <input type="text" name="license_plate" x-model="editData.license_plate"
                           :required="editData.type !== 'standard'"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 uppercase focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Merk & Model</label>
                    <input type="text" name="brand_model" x-model="editData.brand_model" required
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>

                <div x-show="editData.type === 'exclusive'" class="space-y-3 pt-3 border-t border-slate-200">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kapasitas Maksimal Baterai (kWh)</label>
                        <input type="number" step="0.01" min="1" name="battery_capacity_kwh" x-model="editData.battery_capacity_kwh"
                               :required="editData.type === 'exclusive'"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Sisa Baterai Saat Ini (kWh)</label>
                        <input type="number" step="0.01" min="0" name="remaining_kwh" x-model="editData.remaining_kwh"
                               :required="editData.type === 'exclusive'"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection