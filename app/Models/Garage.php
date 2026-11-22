<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Merepresentasikan unit garasi beserta tarif sewa dan tarif pengisian daya listrik.
 *
 * @property int $id
 * @property string $code Kode unik unit garasi (contoh: S-001, C-001, E-001)
 * @property string $type Tipe garasi ('S' = Sepeda, 'C' = Mobil, 'E' = Mobil Listrik)
 * @property string $daily_rate Tarif sewa harian dalam Rupiah
 * @property string $kwh_rate Tarif pengisian daya per kWh dalam Rupiah
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection<int, Rental> $rentals Daftar transaksi sewa pada unit garasi ini
 */
class Garage extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'code',
        'type',
        'daily_rate',
        'kwh_rate',
    ];

    /**
     * Menentukan casting tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'kwh_rate'   => 'decimal:2',
        ];
    }

    /**
     * Relasi one-to-many ke transaksi penyewaan pada garasi ini.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Menghitung tarif paket bulanan standar (25 kali tarif harian).
     *
     * @return float
     */
    public function getMonthlyRate(): float
    {
        return (float) $this->daily_rate * 25;
    }
}