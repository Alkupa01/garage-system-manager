@extends('layouts.app')
@section('title', 'Kelola Transaksi Sewa')

@section('content')
<div class="bg-white p-6 rounded shadow" x-data="{
    extendModal: false,
    extendUrl: '',
    rentalCode: '',
    currentGarageId: '',
    vehicleType: '',
    requiredGarageType: '',
    extensionType: 'daily',
    extensionQuantity: 1,
    nextDaysInMonth: 30,
    allGarages: {{ $garages->toJson() }},
    get compatibleGarages() {
        const map = {
            standard: ['S'],
            charging: ['C'],
            exclusive: ['C', 'E']
        };
        const allowed = map[this.vehicleType] || [];
        return this.allGarages.filter(g => allowed.includes(g.type));
    },
    get maxDailyExtension() {
        return this.nextDaysInMonth - 1;
    },
    normalizeExtensionQty() {
        if (this.extensionType === 'monthly') {
            this.extensionQuantity = 1;
        } else {
            if (this.extensionQuantity < 1) this.extensionQuantity = 1;
            if (this.extensionQuantity > this.maxDailyExtension) this.extensionQuantity = this.maxDailyExtension;
        }
    },
    openExtend(url, code, garageId, vehicleType, nextDaysInMonth) {
        const labelMap = { standard: 'S', charging: 'C', exclusive: 'C / E' };
        this.extendUrl = url;
        this.rentalCode = code;
        this.currentGarageId = String(garageId);
        this.vehicleType = vehicleType;
        this.requiredGarageType = labelMap[vehicleType] || '';
        this.nextDaysInMonth = Number(nextDaysInMonth);
        this.extensionType = 'daily';
        this.extensionQuantity = 1;
        this.extendModal = true;
    }
}">
    <h1 class="text-xl font-bold mb-4">Daftar Semua Transaksi Sewa</h1>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">Penyewa & Kendaraan</th>
                    <th class="border p-2 text-left">Garasi & Periode</th>
                    <th class="border p-2 text-right">Biaya & Pembayaran</th>
                    <th class="border p-2 text-center">Status</th>
                    <th class="border p-2 text-center">Aksi Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentals as $rental)
                    <tr>
                        <td class="border p-2 align-top">
                            <div class="font-bold">{{ $rental->user->name }}</div>
                            <div class="text-xs text-gray-600">{{ $rental->vehicle->brand_model }} ({{ $rental->vehicle->license_plate }})</div>
                            <div class="text-xs text-gray-500">
                                Tipe: {{ $rental->vehicle->type === 'standard' ? 'Sepeda' : ($rental->vehicle->type === 'charging' ? 'Mobil' : 'Mobil Listrik') }}
                            </div>
                        </td>
                        <td class="border p-2 align-top">
                            <div class="font-semibold">Garasi {{ $rental->garage->code }} ({{ $rental->garage->type }})</div>
                            <div class="text-xs">{{ $rental->start_date->format('d M Y') }} s/d {{ $rental->end_date->format('d M Y') }} ({{ $rental->duration_days }} hari)</div>
                            @if($rental->extensions->isNotEmpty())
                                <div class="mt-1 text-xs text-blue-700 bg-blue-50 p-1 rounded">
                                    Diperpanjang {{ $rental->extensions->count() }}x
                                    (Terakhir: {{ $rental->extensions->last()->previousGarage->code }} &rarr; {{ $rental->extensions->last()->newGarage->code }})
                                </div>
                            @endif
                        </td>
                        <td class="border p-2 align-top text-right">
                            <div>Sewa: Rp {{ number_format($rental->base_rental_cost, 0, ',', '.') }}</div>
                            <div class="text-xs text-gray-600">Charge: Rp {{ number_format($rental->charging_cost, 0, ',', '.') }}</div>
                            <div class="font-bold">Total: Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</div>
                            <div class="mt-1 space-x-1">
                                @foreach($rental->payments as $pay)
                                    <a href="{{ route('files.show', $pay->proof_image_path) }}" target="_blank" class="inline-block text-xs text-blue-600 underline">
                                        [Bukti {{ $pay->payment_type }}]
                                    </a>
                                @endforeach
                            </div>
                        </td>
                        <td class="border p-2 align-top text-center">
                            <form action="{{ route('admin.rentals.status', $rental) }}" method="POST">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs font-semibold">
                                    @foreach(['booked', 'active', 'completed', 'cancelled'] as $st)
                                        <option value="{{ $st }}" {{ $rental->status === $st ? 'selected' : '' }}>{{ strtoupper($st) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="border p-2 align-top text-center">
                            @if(in_array($rental->status, ['booked', 'active']))
                                <button type="button"
                                    @click="openExtend('{{ route('admin.rentals.extend', $rental) }}', '#{{ $rental->id }} - {{ $rental->user->name }}', '{{ $rental->garage_id }}', '{{ $rental->vehicle->type }}', '{{ $rental->end_date->copy()->addDay()->daysInMonth }}')"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded text-xs font-medium">
                                    + Extend Sewa
                                </button>
                            @else
                                <span class="text-xs text-gray-400">Selesai/Batal</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="border p-4 text-center text-gray-500">Belum ada transaksi sewa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Extend Admin -->
    <div x-show="extendModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded shadow-lg max-w-md w-full" @click.away="extendModal = false">
            <h2 class="text-lg font-bold mb-1">Perpanjang Sewa (Admin)</h2>
            <p class="text-xs text-gray-600 mb-4" x-text="rentalCode"></p>

            <form :action="extendUrl" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium mb-1">Pilih Garasi (Tetap atau Pindah Unit Tipe <span x-text="requiredGarageType"></span>)</label>
                    <select name="new_garage_id" x-model="currentGarageId" required class="w-full border rounded px-3 py-2 text-sm">
                        <template x-for="g in compatibleGarages" :key="g.id">
                            <option :value="g.id" x-text="g.code + ' - Rp ' + Number(g.daily_rate).toLocaleString('id-ID') + '/hari'"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Tipe Perpanjangan</label>
                        <select name="extension_type" x-model="extensionType" @change="normalizeExtensionQty()" class="w-full border rounded px-3 py-2 text-sm">
                            <option value="daily">Harian</option>
                            <option value="monthly">Bulanan (1 Bulan Kalender / 25x)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Jumlah (<span x-text="extensionType === 'monthly' ? 'Bulan' : 'Hari'"></span>)</label>
                        <input type="number" name="quantity" x-model.number="extensionQuantity"
                            @input="normalizeExtensionQty()"
                            min="1"
                            :max="extensionType === 'monthly' ? 1 : maxDailyExtension"
                            :readonly="extensionType === 'monthly'"
                            :class="extensionType === 'monthly' ? 'bg-gray-100 cursor-not-allowed' : ''"
                            required class="w-full border rounded px-3 py-2 text-sm">
                        <p class="text-[11px] text-gray-500 mt-0.5"
                           x-text="extensionType === 'monthly'
                               ? 'Terkunci 1 bulan (' + nextDaysInMonth + ' hari kalender).'
                               : 'Maksimal ' + maxDailyExtension + ' hari.'">
                        </p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Upload Bukti Pembayaran Perpanjangan</label>
                    <input type="file" name="proof_image" accept="image/*" required class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="extendModal = false" class="bg-gray-300 px-3 py-1.5 rounded text-sm">Batal</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm font-semibold">Proses Extend</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection