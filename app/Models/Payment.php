<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Merepresentasikan bukti pembayaran untuk sewa awal maupun perpanjangan sewa.
 *
 * @property int $id
 * @property int $rental_id ID transaksi sewa induk
 * @property int|null $extension_id ID record perpanjangan (null jika pembayaran awal)
 * @property int $uploaded_by ID pengguna yang mengunggah bukti bayar
 * @property string $payment_type Jenis pembayaran ('initial' atau 'extension')
 * @property string $amount Nominal pembayaran dalam Rupiah
 * @property string $proof_image_path Path file gambar bukti pembayaran
 * @property Carbon|null $paid_at Waktu pembayaran tercatat
 *
 * @property-read Rental $rental Transaksi sewa terkait
 * @property-read User $uploader Pengguna yang mengunggah bukti bayar
 * @property-read RentalExtension|null $extension Detail perpanjangan sewa terkait
 */
class Payment extends Model
{
    /** @var bool */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'rental_id',
        'extension_id',
        'uploaded_by',
        'payment_type',
        'amount',
        'proof_image_path',
        'paid_at',
    ];

    /**
     * Menentukan casting tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_at' => 'datetime',
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
     * Relasi belongs-to ke pengguna pengunggah bukti pembayaran.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Relasi belongs-to ke detail perpanjangan sewa (jika ada).
     *
     * @return BelongsTo<RentalExtension, $this>
     */
    public function extension(): BelongsTo
    {
        return $this->belongsTo(RentalExtension::class);
    }
}