<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Merepresentasikan catatan perpanjangan masa sewa garasi yang diproses oleh Admin.
 *
 * @property int $id
 * @property int $rental_id ID transaksi sewa induk
 * @property int $admin_id ID admin yang memproses perpanjangan
 * @property int $previous_garage_id ID garasi sebelum perpanjangan
 * @property int $new_garage_id ID garasi pada masa perpanjangan
 * @property string $extension_type Jenis perpanjangan ('daily' atau 'monthly')
 * @property int $added_days Jumlah hari yang ditambahkan
 * @property string $daily_rate_snapshot Snapshot tarif harian garasi baru
 * @property string $additional_cost Biaya tambahan perpanjangan
 * @property Carbon $previous_end_date Tanggal selesai sewa sebelum diperpanjang
 * @property Carbon $new_end_date Tanggal selesai sewa setelah diperpanjang
 * @property Carbon|null $created_at
 *
 * @property-read Rental $rental Transaksi sewa induk
 * @property-read User $admin Admin yang memproses transaksi
 * @property-read Garage $previousGarage Unit garasi sebelumnya
 * @property-read Garage $newGarage Unit garasi tujuan perpanjangan
 */
class RentalExtension extends Model
{
    /** @var bool */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'rental_id',
        'admin_id',
        'previous_garage_id',
        'new_garage_id',
        'extension_type',
        'added_days',
        'daily_rate_snapshot',
        'additional_cost',
        'previous_end_date',
        'new_end_date',
    ];

    /**
     * Menentukan casting tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_end_date'   => 'date',
            'new_end_date'        => 'date',
            'daily_rate_snapshot' => 'decimal:2',
            'additional_cost'     => 'decimal:2',
            'created_at'          => 'datetime',
        ];
    }

    /**
     * Relasi belongs-to ke transaksi sewa induk.
     *
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Relasi belongs-to ke admin yang memproses perpanjangan.
     *
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Relasi belongs-to ke unit garasi sebelum perpanjangan.
     *
     * @return BelongsTo<Garage, $this>
     */
    public function previousGarage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'previous_garage_id');
    }

    /**
     * Relasi belongs-to ke unit garasi setelah perpanjangan.
     *
     * @return BelongsTo<Garage, $this>
     */
    public function newGarage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'new_garage_id');
    }
}