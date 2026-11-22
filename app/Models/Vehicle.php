<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Merepresentasikan data kendaraan yang didaftarkan oleh penyewa.
 *
 * @property int $id
 * @property int $user_id ID pemilik kendaraan
 * @property string $type Jenis kendaraan ('standard', 'charging', 'exclusive')
 * @property string|null $license_plate Nomor polisi kendaraan (opsional untuk sepeda)
 * @property string $brand_model Merek dan model kendaraan
 * @property string|null $battery_capacity_kwh Kapasitas maksimal baterai dalam kWh (khusus EV)
 * @property string|null $remaining_kwh Sisa kapasitas baterai saat ini dalam kWh (khusus EV)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user Pemilik kendaraan
 * @property-read Collection<int, Rental> $rentals Daftar riwayat sewa garasi untuk kendaraan ini
 */
class Vehicle extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'type',
        'license_plate',
        'brand_model',
        'battery_capacity_kwh',
        'remaining_kwh',
    ];

    /**
     * Menentukan casting tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'battery_capacity_kwh' => 'decimal:2',
            'remaining_kwh'        => 'decimal:2',
        ];
    }

    /**
     * Relasi belongs-to ke penyewa pemilik kendaraan.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi one-to-many ke transaksi sewa garasi yang menggunakan kendaraan ini.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Menghitung kebutuhan pengisian daya listrik (kWh) hingga baterai penuh.
     *
     * @return float
     */
    public function getRequiredChargeKwh(): float
    {
        return max(0.0, (float) $this->battery_capacity_kwh - (float) $this->remaining_kwh);
    }
}