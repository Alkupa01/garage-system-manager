<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menangani pengecekan ketersediaan unit garasi dan kesesuaian tipe kendaraan.
 */
class AvailabilityService
{
    /**
     * Memeriksa apakah suatu unit garasi bebas dari jadwal sewa aktif/booked pada rentang tanggal tertentu.
     *
     * @param Garage $garage Unit garasi yang diperiksa
     * @param Carbon $startDate Tanggal mulai sewa
     * @param Carbon $endDate Tanggal selesai sewa
     * @param int|null $ignoreRentalId ID transaksi yang diabaikan (digunakan saat extend/update status)
     * @return bool True jika garasi tersedia, false jika bentrok
     */
    public function isGarageAvailable(Garage $garage, Carbon $startDate, Carbon $endDate, ?int $ignoreRentalId = null): bool
    {
        $query = Rental::where('garage_id', $garage->id)
            ->whereIn('status', ['booked', 'active'])
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString());

        if ($ignoreRentalId !== null) {
            $query->where('id', '!=', $ignoreRentalId);
        }

        return !$query->exists();
    }

    /**
     * Mengambil daftar unit garasi yang kompatibel dengan jenis kendaraan dan tersedia pada periode sewa.
     *
     * @param Vehicle $vehicle Kendaraan milik penyewa
     * @param Carbon $startDate Tanggal mulai sewa
     * @param Carbon $endDate Tanggal selesai sewa
     * @param int|null $ignoreRentalId ID transaksi yang diabaikan
     * @return Collection<int, Garage> Koleksi unit garasi yang tersedia
     */
    public function getAvailableGarages(Vehicle $vehicle, Carbon $startDate, Carbon $endDate, ?int $ignoreRentalId = null): Collection
    {
        $allowedTypes = match ($vehicle->type) {
            'standard'  => ['S'],
            'charging'  => ['C'],
            'exclusive' => ['C', 'E'],
            default     => [],
        };

        return Garage::whereIn('type', $allowedTypes)
            ->orderBy('code')
            ->get()
            ->filter(fn (Garage $garage) => $this->isGarageAvailable($garage, $startDate, $endDate, $ignoreRentalId))
            ->values();
    }
}