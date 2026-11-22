<?php

namespace App\Services;

use App\Models\Garage;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

/**
 * Mengelola operasi CRUD master data unit garasi.
 */
class GarageService
{
    /**
     * Mengambil seluruh unit garasi terurut berdasarkan kode garasi.
     *
     * @return Collection<int, Garage>
     */
    public function getAll(): Collection
    {
        return Garage::orderBy('code')->get();
    }

    /**
     * Menambahkan unit garasi baru ke dalam sistem.
     *
     * @param array<string, mixed> $data Data garasi (code, type, daily_rate, kwh_rate)
     * @return Garage
     */
    public function create(array $data): Garage
    {
        return Garage::create([
            'code'       => strtoupper($data['code']),
            'type'       => $data['type'],
            'daily_rate' => $data['daily_rate'],
            'kwh_rate'   => $data['type'] === 'S' ? 0 : ($data['kwh_rate'] ?? 0),
        ]);
    }

    /**
     * Memperbarui data unit garasi yang sudah ada.
     *
     * @param Garage $garage Model unit garasi
     * @param array<string, mixed> $data Data pembaruan garasi
     * @return Garage
     */
    public function update(Garage $garage, array $data): Garage
    {
        $type = $data['type'] ?? $garage->type;

        $garage->update([
            'code'       => strtoupper($data['code'] ?? $garage->code),
            'type'       => $type,
            'daily_rate' => $data['daily_rate'] ?? $garage->daily_rate,
            'kwh_rate'   => $type === 'S' ? 0 : ($data['kwh_rate'] ?? $garage->kwh_rate),
        ]);

        return $garage->fresh();
    }

    /**
     * Menghapus unit garasi apabila tidak memiliki transaksi sewa yang sedang berjalan.
     *
     * @param Garage $garage Model unit garasi
     * @return void
     *
     * @throws DomainException Jika garasi masih memiliki transaksi booked atau active
     */
    public function delete(Garage $garage): void
    {
        $hasOngoing = $garage->rentals()
            ->whereIn('status', ['booked', 'active'])
            ->exists();

        if ($hasOngoing) {
            throw new DomainException('Garasi masih memiliki transaksi booked atau active.');
        }

        $garage->delete();
    }
}