<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Merepresentasikan transaksi penyewaan unit garasi oleh penyewa.
 *
 * @property int $id
 * @property int $user_id ID penyewa
 * @property int $garage_id ID unit garasi yang ditempati
 * @property int $vehicle_id ID kendaraan yang diparkir
 * @property string $rental_type Tipe paket sewa ('daily' atau 'monthly')
 * @property Carbon $start_date Tanggal mulai sewa
 * @property Carbon $end_date Tanggal berakhir sewa
 * @property int $duration_days Total durasi sewa dalam hari
 * @property string $daily_rate_snapshot Snapshot tarif harian garasi saat transaksi
 * @property string $kwh_rate_snapshot Snapshot tarif per kWh garasi saat transaksi
 * @property string $base_rental_cost Total biaya pokok sewa garasi
 * @property string $charging_cost Total biaya pengisian daya listrik kendaraan EV
 * @property string $total_cost Total keseluruhan biaya sewa
 * @property string $status Status transaksi ('booked', 'active', 'completed', 'cancelled')
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user Penyewa yang melakukan transaksi
 * @property-read Garage $garage Unit garasi yang digunakan
 * @property-read Vehicle $vehicle Kendaraan yang disewakan garasinya
 * @property-read Collection<int, RentalExtension> $extensions Riwayat perpanjangan masa sewa
 * @property-read Collection<int, Payment> $payments Riwayat bukti pembayaran transaksi
 */
class Rental extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'garage_id',
        'vehicle_id',
        'rental_type',
        'start_date',
        'end_date',
        'duration_days',
        'daily_rate_snapshot',
        'kwh_rate_snapshot',
        'base_rental_cost',
        'charging_cost',
        'total_cost',
        'status',
    ];

    /**
     * Menentukan casting tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date'          => 'date',
            'end_date'            => 'date',
            'daily_rate_snapshot' => 'decimal:2',
            'kwh_rate_snapshot'   => 'decimal:2',
            'base_rental_cost'    => 'decimal:2',
            'charging_cost'       => 'decimal:2',
            'total_cost'          => 'decimal:2',
        ];
    }

    /**
     * Relasi belongs-to ke penyewa.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi belongs-to ke unit garasi aktif pada transaksi ini.
     *
     * @return BelongsTo<Garage, $this>
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Relasi belongs-to ke kendaraan yang menempati garasi.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Relasi one-to-many ke riwayat perpanjangan sewa.
     *
     * @return HasMany<RentalExtension, $this>
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    /**
     * Relasi one-to-many ke catatan pembayaran sewa awal maupun perpanjangan.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}