<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mengelola HTTP request pendaftaran, pembaruan, dan penghapusan kendaraan milik penyewa.
 */
class VehicleController extends Controller
{
    /**
     * Menampilkan daftar kendaraan yang dimiliki penyewa yang sedang login.
     *
     * @return View
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vehicles = $user->vehicles()->latest()->get();

        return view('tenant.vehicles', compact('vehicles'));
    }

    /**
     * Menyimpan data kendaraan baru milik penyewa beserta normalisasi plat nomor.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $rawPlate = trim((string) $request->input('license_plate', ''));
        $normalizedPlate = $rawPlate !== ''
            ? strtoupper(preg_replace('/\s+/', ' ', $rawPlate))
            : null;

        $request->merge([
            'license_plate' => $normalizedPlate,
        ]);

        $validated = $request->validate([
            'type'                 => ['required', 'in:standard,charging,exclusive'],
            'license_plate'        => ['nullable', 'required_unless:type,standard', 'string', 'max:20', 'unique:vehicles,license_plate'],
            'brand_model'          => ['required', 'string', 'max:100'],
            'battery_capacity_kwh' => ['nullable', 'required_if:type,exclusive', 'numeric', 'min:1'],
            'remaining_kwh'        => ['nullable', 'required_if:type,exclusive', 'numeric', 'min:0', 'lte:battery_capacity_kwh'],
        ], [
            'license_plate.required_unless' => 'Nomor plat wajib diisi untuk kendaraan jenis mobil.',
            'license_plate.unique'          => 'Nomor plat kendaraan sudah terdaftar di sistem.',
        ]);

        $isEv = $validated['type'] === 'exclusive';

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->vehicles()->create([
            'type'                 => $validated['type'],
            'license_plate'        => $validated['license_plate'],
            'brand_model'          => $validated['brand_model'],
            'battery_capacity_kwh' => $isEv ? $validated['battery_capacity_kwh'] : null,
            'remaining_kwh'        => $isEv ? $validated['remaining_kwh'] : null,
        ]);

        return back()->with('success', 'Kendaraan berhasil didaftarkan.');
    }

    /**
     * Memperbarui data kendaraan milik penyewa yang terautentikasi.
     *
     * @param Request $request
     * @param Vehicle $vehicle Model kendaraan
     * @return RedirectResponse
     */
    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== Auth::id(), 403);

        $rawPlate = trim((string) $request->input('license_plate', ''));
        $normalizedPlate = $rawPlate !== ''
            ? strtoupper(preg_replace('/\s+/', ' ', $rawPlate))
            : null;

        $request->merge([
            'license_plate' => $normalizedPlate,
        ]);

        $validated = $request->validate([
            'type'                 => ['required', 'in:standard,charging,exclusive'],
            'license_plate'        => ['nullable', 'required_unless:type,standard', 'string', 'max:20', Rule::unique('vehicles', 'license_plate')->ignore($vehicle->id)],
            'brand_model'          => ['required', 'string', 'max:100'],
            'battery_capacity_kwh' => ['nullable', 'required_if:type,exclusive', 'numeric', 'min:1'],
            'remaining_kwh'        => ['nullable', 'required_if:type,exclusive', 'numeric', 'min:0', 'lte:battery_capacity_kwh'],
        ], [
            'license_plate.required_unless' => 'Nomor plat wajib diisi untuk kendaraan jenis mobil.',
            'license_plate.unique'          => 'Nomor plat kendaraan sudah terdaftar di sistem.',
        ]);

        $isEv = $validated['type'] === 'exclusive';

        $vehicle->update([
            'type'                 => $validated['type'],
            'license_plate'        => $validated['license_plate'],
            'brand_model'          => $validated['brand_model'],
            'battery_capacity_kwh' => $isEv ? $validated['battery_capacity_kwh'] : null,
            'remaining_kwh'        => $isEv ? $validated['remaining_kwh'] : null,
        ]);

        return back()->with('success', 'Data kendaraan berhasil diperbarui.');
    }

    /**
     * Menghapus kendaraan milik penyewa apabila tidak memiliki transaksi sewa aktif/booked.
     *
     * @param Vehicle $vehicle Model kendaraan
     * @return RedirectResponse
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== Auth::id(), 403);

        if ($vehicle->rentals()->whereIn('status', ['booked', 'active'])->exists()) {
            return back()->withErrors(['error' => 'Kendaraan masih memiliki transaksi sewa aktif/booked dan tidak dapat dihapus.']);
        }

        $vehicle->delete();

        return back()->with('success', 'Kendaraan berhasil dihapus.');
    }
}