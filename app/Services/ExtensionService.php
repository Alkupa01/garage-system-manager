<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mengelola proses perpanjangan masa sewa garasi (extend) oleh Admin.
 */
class ExtensionService
{
    /**
     * @param AvailabilityService $availabilityService Layanan pengecekan ketersediaan garasi
     */
    public function __construct(
        private readonly AvailabilityService $availabilityService
    ) {}

    /**
     * Memproses perpanjangan masa sewa harian atau bulanan serta mencatat riwayat dan pembayaran tambahannya.
     *
     * @param Rental $rental Transaksi sewa yang akan diperpanjang
     * @param User $admin Akun admin yang memproses perpanjangan
     * @param Garage $newGarage Unit garasi pada periode perpanjangan (dapat tetap atau pindah unit)
     * @param string $extensionType Jenis perpanjangan ('daily' atau 'monthly')
     * @param int $quantity Durasi perpanjangan (hari atau 1 bulan)
     * @param UploadedFile $proofFile Bukti pembayaran perpanjangan sewa
     * @return RentalExtension
     *
     * @throws DomainException Jika status sewa tidak aktif/booked, garasi tidak kompatibel, durasi tidak valid, atau jadwal bentrok
     */
    public function extendRental(
        Rental $rental,
        User $admin,
        Garage $newGarage,
        string $extensionType,
        int $quantity,
        UploadedFile $proofFile
    ): RentalExtension {
        if (!in_array($rental->status, ['booked', 'active'], true)) {
            throw new DomainException('Hanya sewa berstatus booked atau active yang dapat diperpanjang.');
        }

        $allowedTypes = match ($rental->vehicle->type) {
            'standard'  => ['S'],
            'charging'  => ['C'],
            'exclusive' => ['C', 'E'],
            default     => [],
        };

        if (!in_array($newGarage->type, $allowedTypes, true)) {
            throw new DomainException('Tipe unit garasi tujuan tidak sesuai dengan jenis kendaraan.');
        }

        $previousEndDate = Carbon::parse($rental->end_date);
        $extensionStart  = $previousEndDate->copy()->addDay();
        $daysInMonth     = $extensionStart->daysInMonth;

        if ($extensionType === 'monthly') {
            if ($quantity !== 1) {
                throw new DomainException('Perpanjangan bulanan hanya dapat diproses 1 bulan per transaksi.');
            }
            $addedDays         = $daysInMonth;
            $extensionBaseCost = (float) $newGarage->daily_rate * 25;
        } elseif ($extensionType === 'daily') {
            $maxDaily = $daysInMonth - 1;
            if ($quantity < 1 || $quantity > $maxDaily) {
                throw new DomainException("Perpanjangan harian minimal 1 hari dan maksimal {$maxDaily} hari.");
            }
            $addedDays         = $quantity;
            $extensionBaseCost = (float) $newGarage->daily_rate * $addedDays;
        } else {
            throw new DomainException('Tipe perpanjangan sewa tidak valid.');
        }

        $extensionEnd = $extensionStart->copy()->addDays($addedDays - 1);

        if (!$this->availabilityService->isGarageAvailable($newGarage, $extensionStart, $extensionEnd, $rental->id)) {
            throw new DomainException('Unit garasi tujuan sudah terisi pada tanggal perpanjangan tersebut.');
        }

        $proofPath        = $proofFile->store('payments', 'public');
        $previousGarageId = $rental->garage_id;

        return DB::transaction(function () use (
            $rental,
            $admin,
            $previousGarageId,
            $newGarage,
            $extensionType,
            $addedDays,
            $extensionBaseCost,
            $previousEndDate,
            $extensionEnd,
            $proofPath
        ) {
            $extension = RentalExtension::create([
                'rental_id'           => $rental->id,
                'admin_id'            => $admin->id,
                'previous_garage_id'  => $previousGarageId,
                'new_garage_id'       => $newGarage->id,
                'extension_type'      => $extensionType,
                'added_days'          => $addedDays,
                'daily_rate_snapshot' => round((float) $newGarage->daily_rate, 2),
                'additional_cost'     => round($extensionBaseCost, 2),
                'previous_end_date'   => $previousEndDate->toDateString(),
                'new_end_date'        => $extensionEnd->toDateString(),
            ]);

            Payment::create([
                'rental_id'        => $rental->id,
                'extension_id'     => $extension->id,
                'uploaded_by'      => $admin->id,
                'payment_type'     => 'extension',
                'amount'           => round($extensionBaseCost, 2),
                'proof_image_path' => $proofPath,
            ]);

            $rental->update([
                'garage_id'        => $newGarage->id,
                'end_date'         => $extensionEnd->toDateString(),
                'duration_days'    => $rental->duration_days + $addedDays,
                'base_rental_cost' => round((float) $rental->base_rental_cost + $extensionBaseCost, 2),
                'total_cost'       => round((float) $rental->total_cost + $extensionBaseCost, 2),
            ]);

            return $extension;
        });
    }
}