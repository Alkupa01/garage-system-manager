<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Mengelola logika bisnis registrasi penyewa, pembaruan KTP, dan verifikasi akun oleh Admin.
 */
class AuthService
{
    /**
     * Mendaftarkan akun penyewa baru beserta unggahan foto KTP dengan status awal pending.
     *
     * @param array<string, mixed> $data Data registrasi (nik, name, phone, email, password)
     * @param UploadedFile $ktpImage File foto KTP penyewa
     * @return User Model pengguna yang baru dibuat
     */
    public function registerTenant(array $data, UploadedFile $ktpImage): User
    {
        $ktpPath = $ktpImage->store('ktp', 'public');

        return User::create([
            'nik'                 => $data['nik'],
            'name'                => $data['name'],
            'phone'               => $data['phone'],
            'email'               => $data['email'],
            'password'            => $data['password'],
            'role'                => 'tenant',
            'verification_status' => 'pending',
            'ktp_image_path'      => $ktpPath,
        ]);
    }

    /**
     * Memperbarui status verifikasi KTP penyewa oleh Admin.
     *
     * @param User $tenant Akun penyewa yang diverifikasi
     * @param string $status Status baru ('approved' atau 'rejected')
     * @return User
     *
     * @throws DomainException Jika target bukan akun penyewa atau status tidak valid
     */
    public function verifyTenant(User $tenant, string $status): User
    {
        if ($tenant->role !== 'tenant') {
            throw new DomainException('Hanya akun penyewa yang dapat diverifikasi.');
        }

        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new DomainException('Status verifikasi tidak valid.');
        }

        $tenant->update(['verification_status' => $status]);

        return $tenant->fresh();
    }

    /**
     * Mengganti file foto KTP lama penyewa dan mereset status verifikasi menjadi pending.
     *
     * @param User $tenant Akun penyewa yang memperbarui KTP
     * @param UploadedFile $ktpImage File foto KTP baru
     * @return User
     */
    public function updateKtp(User $tenant, UploadedFile $ktpImage): User
    {
        if ($tenant->ktp_image_path) {
            Storage::disk('public')->delete($tenant->ktp_image_path);
        }

        $tenant->update([
            'ktp_image_path'      => $ktpImage->store('ktp', 'public'),
            'verification_status' => 'pending',
        ]);

        return $tenant->fresh();
    }
}