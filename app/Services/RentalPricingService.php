<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mengelola perhitungan tarif sewa, pembuatan booking baru, serta perubahan status transaksi.
 */
class RentalPricingService
{
    /**
     * @param AvailabilityService $availabilityService Layanan pengecekan ketersediaan garasi
     */
    public function __construct(
        private readonly AvailabilityService $availabilityService
    ) {}

    /**
     * Menghitung rincian estimasi biaya sewa garasi (harian/bulanan) dan biaya charging kendaraan EV.
     *
     * @param Garage $garage Unit garasi yang dipilih
     * @param Vehicle $vehicle Kendaraan yang disewakan garasinya
     * @param string $rentalType Paket sewa ('daily' atau 'monthly')
     * @param Carbon $startDate Tanggal mulai sewa
     * @param int $quantity Jumlah hari (untuk daily) atau 1 (untuk monthly)
     * @return array<string, mixed> Rincian tanggal, durasi, snapshot tarif, dan total biaya
     *
     * @throws DomainException Jika tipe garasi tidak kompatibel atau durasi sewa melampaui batas aturan
     */
    public function calculateQuote(Garage $garage, Vehicle $vehicle, string $rentalType, Carbon $startDate, int $quantity): array
    {
        $this->validateVehicleGarageCompatibility($vehicle, $garage);

        $daysInMonth = $startDate->daysInMonth;

        if ($rentalType === 'monthly') {
            if ($quantity !== 1) {
                throw new DomainException('Paket bulanan hanya dapat dipesan maksimal 1 bulan per transaksi.');
            }
            $durationDays   = $daysInMonth;
            $baseRentalCost = (float) $garage->daily_rate * 25;
        } elseif ($rentalType === 'daily') {
            $maxDaily = $daysInMonth - 1;
            if ($quantity < 1 || $quantity > $maxDaily) {
                throw new DomainException("Sewa harian pada bulan tersebut minimal 1 hari dan maksimal {$maxDaily} hari.");
            }
            $durationDays   = $quantity;
            $baseRentalCost = (float) $garage->daily_rate * $durationDays;
        } else {
            throw new DomainException('Tipe paket sewa tidak valid.');
        }

        $endDate = $startDate->copy()->addDays($durationDays - 1);

        $chargingCost = 0.0;
        $kwhRateSnapshot = 0.0;

        if ($vehicle->type === 'exclusive' && $garage->type === 'E') {
            $neededKwh       = max(0, (float) $vehicle->battery_capacity_kwh - (float) $vehicle->remaining_kwh);
            $kwhRateSnapshot = (float) $garage->kwh_rate;
            $chargingCost    = $neededKwh * $kwhRateSnapshot;
        }

        $totalCost = $baseRentalCost + $chargingCost;

        return [
            'start_date'          => $startDate->toDateString(),
            'end_date'            => $endDate->toDateString(),
            'duration_days'       => $durationDays,
            'days_in_month'       => $daysInMonth,
            'daily_rate_snapshot' => round((float) $garage->daily_rate, 2),
            'kwh_rate_snapshot'   => round($kwhRateSnapshot, 2),
            'base_rental_cost'    => round($baseRentalCost, 2),
            'charging_cost'       => round($chargingCost, 2),
            'total_cost'          => round($totalCost, 2),
        ];
    }

    /**
     * Membuat transaksi pemesanan garasi baru beserta catatan pembayaran awal secara atomik.
     *
     * @param User $tenant Penyewa yang mengajukan pemesanan
     * @param Garage $garage Unit garasi yang dipilih
     * @param Vehicle $vehicle Kendaraan milik penyewa
     * @param string $rentalType Paket sewa ('daily' atau 'monthly')
     * @param Carbon $startDate Tanggal mulai sewa
     * @param int $quantity Durasi sewa
     * @param UploadedFile $proofFile File bukti transfer pembayaran awal
     * @return Rental
     *
     * @throws DomainException Jika KTP belum disetujui, kendaraan bukan milik penyewa, atau garasi sudah terisi
     */
    public function createBooking(
        User $tenant,
        Garage $garage,
        Vehicle $vehicle,
        string $rentalType,
        Carbon $startDate,
        int $quantity,
        UploadedFile $proofFile
    ): Rental {
        if ($tenant->verification_status !== 'approved') {
            throw new DomainException('Akun Anda belum diverifikasi oleh Admin.');
        }

        if ($vehicle->user_id !== $tenant->id) {
            throw new DomainException('Kendaraan tidak terdaftar atas nama akun Anda.');
        }

        $quote   = $this->calculateQuote($garage, $vehicle, $rentalType, $startDate, $quantity);
        $endDate = Carbon::parse($quote['end_date']);

        if (!$this->availabilityService->isGarageAvailable($garage, $startDate, $endDate)) {
            throw new DomainException('Unit garasi tidak tersedia pada rentang tanggal yang dipilih.');
        }

        $proofPath = $proofFile->store('payments', 'public');

        return DB::transaction(function () use ($tenant, $garage, $vehicle, $rentalType, $quote, $proofPath) {
            $rental = Rental::create([
                'user_id'             => $tenant->id,
                'garage_id'           => $garage->id,
                'vehicle_id'          => $vehicle->id,
                'rental_type'         => $rentalType,
                'start_date'          => $quote['start_date'],
                'end_date'            => $quote['end_date'],
                'duration_days'       => $quote['duration_days'],
                'daily_rate_snapshot' => $quote['daily_rate_snapshot'],
                'kwh_rate_snapshot'   => $quote['kwh_rate_snapshot'],
                'base_rental_cost'    => $quote['base_rental_cost'],
                'charging_cost'       => $quote['charging_cost'],
                'total_cost'          => $quote['total_cost'],
                'status'              => 'booked',
            ]);

            Payment::create([
                'rental_id'        => $rental->id,
                'uploaded_by'      => $tenant->id,
                'payment_type'     => 'initial',
                'amount'           => $quote['total_cost'],
                'proof_image_path' => $proofPath,
            ]);

            return $rental;
        });
    }

    /**
     * Memperbarui status transaksi penyewaan garasi oleh Admin.
     *
     * @param Rental $rental Transaksi sewa yang diperbarui
     * @param string $newStatus Status baru ('booked', 'active', 'completed', 'cancelled')
     * @return Rental
     *
     * @throws DomainException Jika status final diubah kembali atau terjadi bentrok jadwal garasi
     */
    public function updateStatus(Rental $rental, string $newStatus): Rental
    {
        if ($rental->status === $newStatus) {
            return $rental;
        }

        if (in_array($rental->status, ['completed', 'cancelled'], true)) {
            throw new DomainException('Transaksi yang sudah selesai atau dibatalkan tidak dapat diubah kembali.');
        }

        if (
            in_array($newStatus, ['booked', 'active'], true) &&
            !$this->availabilityService->isGarageAvailable(
                $rental->garage,
                Carbon::parse($rental->start_date),
                Carbon::parse($rental->end_date),
                $rental->id
            )
        ) {
            throw new DomainException('Unit garasi sudah terisi pada jadwal sewa tersebut.');
        }

        $rental->update(['status' => $newStatus]);

        return $rental;
    }

    /**
     * Memvalidasi kesesuaian tipe unit garasi terhadap jenis kendaraan.
     *
     * @param Vehicle $vehicle Kendaraan penyewa
     * @param Garage $garage Unit garasi tujuan
     * @return void
     *
     * @throws DomainException Jika tipe garasi tidak diizinkan untuk kendaraan tersebut
     */
    private function validateVehicleGarageCompatibility(Vehicle $vehicle, Garage $garage): void
    {
        $allowedTypes = match ($vehicle->type) {
            'standard'  => ['S'],
            'charging'  => ['C'],
            'exclusive' => ['C', 'E'],
            default     => [],
        };

        if (!in_array($garage->type, $allowedTypes, true)) {
            throw new DomainException('Tipe unit garasi tidak sesuai dengan jenis kendaraan.');
        }
    }
}