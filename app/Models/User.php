<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * Merepresentasikan entitas pengguna sistem (Admin dan Penyewa/Tenant).
 *
 * @property int $id
 * @property string $nik Nomor Induk Kependudukan (16 digit)
 * @property string $name Nama lengkap sesuai KTP
 * @property string $phone Nomor telepon/WhatsApp aktif
 * @property string $email Alamat email unik pengguna
 * @property string $password Password ter-hash
 * @property string $role Peran pengguna ('admin' atau 'tenant')
 * @property string $verification_status Status verifikasi KTP ('pending', 'approved', 'rejected')
 * @property string|null $ktp_image_path Path file foto KTP pada disk public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection<int, Vehicle> $vehicles Daftar kendaraan milik penyewa
 * @property-read Collection<int, Rental> $rentals Riwayat transaksi sewa garasi pengguna
 */
class User extends Authenticatable
{
    /** @var list<string> */
    protected $fillable = [
        'nik',
        'name',
        'phone',
        'email',
        'password',
        'role',
        'verification_status',
        'ktp_image_path',
    ];

    /** @var list<string> */
    protected $hidden = ['password'];

    /**
     * Menentukan casting tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    /**
     * Relasi one-to-many ke daftar kendaraan milik penyewa.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Relasi one-to-many ke daftar transaksi sewa garasi milik penyewa.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}