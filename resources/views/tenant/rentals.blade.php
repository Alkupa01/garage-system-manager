@extends('layouts.app')
@section('title', 'Sewa Garasi')

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div class="p-4 rounded bg-red-100 border border-red-400 text-red-900 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($user->verification_status !== 'approved')
        <div class="p-4 rounded bg-amber-100 border border-amber-400 text-amber-900 text-sm">
            Akun Anda berstatus <strong>{{ strtoupper($user->verification_status) }}</strong>. Anda baru dapat memesan garasi setelah KTP disetujui Admin.
        </div>
    @elseif($vehicles->isEmpty())
        <div class="p-4 rounded bg-blue-100 border border-blue-400 text-blue-900 text-sm">
            Anda belum memiliki kendaraan. <a href="{{ route('tenant.vehicles.index') }}" class="underline font-bold">Daftarkan kendaraan terlebih dahulu</a> sebelum memesan garasi.
        </div>
    @else
        <!-- Form Booking & Kalkulator Real-time -->
        <div class="bg-white p-6 rounded shadow" x-data="{
            vehicle_id: '{{ old('vehicle_id', $vehicles->first()->id) }}',
            rental_type: '{{ old('rental_type', 'daily') }}',
            start_date: '{{ old('start_date', now()->toDateString()) }}',
            quantity: {{ (int) old('quantity', 1) }},
            garage_id: '{{ old('garage_id', '') }}',
            availableGarages: [],
            quote: null,
            vehicleConflict: null,
            loading: false,
            get daysInMonth() {
                if (!this.start_date) return 30;
                const [y, m] = this.start_date.split('-').map(Number);
                return new Date(y, m, 0).getDate();
            },
            get maxDailyDays() {
                return this.daysInMonth - 1;
            },
            normalizeQuantity() {
                if (this.rental_type === 'monthly') {
                    this.quantity = 1;
                } else {
                    if (this.quantity < 1) this.quantity = 1;
                    if (this.quantity > this.maxDailyDays) this.quantity = this.maxDailyDays;
                }
            },
            async fetchQuote() {
                this.normalizeQuantity();
                if (!this.vehicle_id || !this.start_date || this.quantity < 1) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ route('tenant.rentals.quote') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({
                            vehicle_id: this.vehicle_id,
                            rental_type: this.rental_type,
                            start_date: this.start_date,
                            quantity: this.quantity,
                            garage_id: this.garage_id || null
                        })
                    });
                    const data = await res.json();
                    this.vehicleConflict = data.vehicle_conflict || null;
                    this.availableGarages = data.available_garages || [];

                    if (this.vehicleConflict) {
                        this.garage_id = '';
                        this.quote = null;
                        return;
                    }

                    if (!this.availableGarages.some(g => String(g.id) === String(this.garage_id))) {
                        this.garage_id = this.availableGarages.length ? String(this.availableGarages[0].id) : '';
                        if (this.garage_id) {
                            await this.fetchQuote();
                            return;
                        }
                    }
                    this.quote = data.quote;
                } finally {
                    this.loading = false;
                }
            }
        }" x-init="fetchQuote()">
            <h1 class="text-xl font-bold mb-4">Pesan Garasi Baru</h1>

            <form action="{{ route('tenant.rentals.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Pilih Kendaraan</label>
                        <select name="vehicle_id" x-model="vehicle_id" @change="garage_id = ''; fetchQuote()" required class="w-full border rounded px-3 py-2 text-sm">
                            @foreach($vehicles as $v)
                                @php
                                    $activeBooking = $v->rentals->first();
                                @endphp
                                <option value="{{ $v->id }}">
                                    {{ $v->license_plate ? $v->license_plate . ' - ' : '' }}{{ $v->brand_model }} ({{ $v->type === 'standard' ? 'Sepeda' : ($v->type === 'charging' ? 'Mobil' : 'Mobil Listrik') }})
                                    @if($activeBooking)
                                        — [Terjadwal: Garasi {{ $activeBooking->garage->code }} s/d {{ $activeBooking->end_date->format('d M Y') }}]
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <template x-if="vehicleConflict">
                            <p class="text-xs text-red-600 mt-1 font-medium" x-text="vehicleConflict"></p>
                        </template>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">Paket Sewa</label>
                            <select name="rental_type" x-model="rental_type" @change="fetchQuote()" class="w-full border rounded px-3 py-2 text-sm">
                                <option value="daily">Harian</option>
                                <option value="monthly">Bulanan (1 Bulan Kalender / Bayar 25x)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Durasi (<span x-text="rental_type === 'monthly' ? 'Bulan' : 'Hari'"></span>)</label>
                            <input type="number" name="quantity" x-model.number="quantity"
                                @input.debounce.300ms="fetchQuote()"
                                min="1"
                                :max="rental_type === 'monthly' ? 1 : maxDailyDays"
                                :readonly="rental_type === 'monthly'"
                                :class="rental_type === 'monthly' ? 'bg-gray-100 cursor-not-allowed' : ''"
                                required class="w-full border rounded px-3 py-2 text-sm">
                            <p class="text-[11px] text-gray-500 mt-0.5"
                               x-text="rental_type === 'monthly'
                                   ? 'Terkunci 1 bulan (' + daysInMonth + ' hari kalender bulan ini).'
                                   : 'Maksimal ' + maxDailyDays + ' hari untuk bulan ini.'">
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium mb-1">Tanggal Mulai Sewa</label>
                        <input type="date" name="start_date" x-model="start_date" @change="fetchQuote()" min="{{ now()->toDateString() }}" required class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium mb-1">Unit Garasi Tersedia (Sesuai Tipe Kendaraan)</label>
                        <select name="garage_id" x-model="garage_id" @change="fetchQuote()" :disabled="!!vehicleConflict" required class="w-full border rounded px-3 py-2 text-sm disabled:bg-gray-100">
                            <template x-if="vehicleConflict">
                                <option value="">-- Kendaraan sudah memiliki jadwal aktif pada tanggal ini --</option>
                            </template>
                            <template x-if="!vehicleConflict && availableGarages.length === 0">
                                <option value="">-- Tidak ada unit garasi tersedia --</option>
                            </template>
                            <template x-for="g in availableGarages" :key="g.id">
                                <option :value="g.id" x-text="g.code + ' (Tipe ' + g.type + ') - Rp ' + Number(g.daily_rate).toLocaleString('id-ID') + '/hari'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium mb-1">Upload Bukti Pembayaran (JPG/PNG, Maks 2MB)</label>
                        <input type="file" name="proof_image" accept="image/*" required class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </div>

                <!-- Ringkasan Estimasi Harga -->
                <div class="bg-slate-50 border rounded p-4 flex flex-col justify-between">
                    <div>
                        <h2 class="font-bold text-sm uppercase text-slate-700 mb-3">Rincian Estimasi Biaya</h2>
                        <template x-if="loading">
                            <p class="text-xs text-gray-500">Menghitung tarif...</p>
                        </template>
                        <template x-if="!loading && vehicleConflict">
                            <div class="p-3 rounded bg-red-50 border border-red-200 text-xs text-red-700 space-y-1">
                                <p class="font-bold">Kendaraan Tidak Tersedia</p>
                                <p x-text="vehicleConflict"></p>
                                <p class="text-red-600">Pilih kendaraan lain atau ubah tanggal mulai sewa setelah periode tersebut selesai.</p>
                            </div>
                        </template>
                        <template x-if="!loading && !vehicleConflict && quote">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Periode Sewa:</span>
                                    <span class="font-medium" x-text="quote.start_date + ' s/d ' + quote.end_date + ' (' + quote.duration_days + ' hari)'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Biaya Sewa Garasi:</span>
                                    <span class="font-medium" x-text="'Rp ' + Number(quote.base_rental_cost).toLocaleString('id-ID')"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Biaya Charging (Penuh):</span>
                                    <span class="font-medium" x-text="'Rp ' + Number(quote.charging_cost).toLocaleString('id-ID')"></span>
                                </div>
                                <div class="border-t pt-2 flex justify-between text-base font-bold text-slate-900">
                                    <span>Total Bayar:</span>
                                    <span x-text="'Rp ' + Number(quote.total_cost).toLocaleString('id-ID')"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!loading && !vehicleConflict && !quote">
                            <p class="text-xs text-red-600">Pilih kendaraan, tanggal, dan unit garasi yang tersedia untuk melihat total tagihan.</p>
                        </template>
                    </div>

                    <button type="submit" :disabled="!quote || !!vehicleConflict" class="mt-4 w-full bg-slate-900 disabled:bg-gray-400 hover:bg-slate-800 text-white py-2.5 rounded text-sm font-semibold">
                        Ajukan Booking & Kirim Bukti Bayar
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Riwayat Transaksi Tenant -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-bold mb-3">Riwayat Sewa Garasi Saya</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2 text-left">Garasi & Kendaraan</th>
                        <th class="border p-2 text-left">Periode Sewa</th>
                        <th class="border p-2 text-right">Total Biaya</th>
                        <th class="border p-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rentals as $rental)
                        <tr>
                            <td class="border p-2">
                                <div class="font-bold">Garasi {{ $rental->garage->code }} ({{ $rental->garage->type }})</div>
                                <div class="text-xs text-gray-600">{{ $rental->vehicle->brand_model }}{{ $rental->vehicle->license_plate ? ' - ' . $rental->vehicle->license_plate : '' }}</div>
                            </td>
                            <td class="border p-2">
                                <div>{{ $rental->start_date->format('d M Y') }} s/d {{ $rental->end_date->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500">Durasi: {{ $rental->duration_days }} hari</div>
                            </td>
                            <td class="border p-2 text-right">
                                <div class="font-bold">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</div>
                                <div class="text-xs text-gray-500">
                                    (Sewa: Rp {{ number_format($rental->base_rental_cost, 0, ',', '.') }} + Charge: Rp {{ number_format($rental->charging_cost, 0, ',', '.') }})
                                </div>
                            </td>
                            <td class="border p-2 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase
                                    {{ $rental->status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($rental->status === 'booked' ? 'bg-blue-100 text-blue-800' : ($rental->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-800')) }}">
                                    {{ $rental->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="border p-4 text-center text-gray-500">Belum ada riwayat sewa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection