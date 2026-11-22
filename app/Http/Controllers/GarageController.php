<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Services\GarageService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mengelola HTTP request manajemen unit garasi (CRUD) oleh Admin.
 */
class GarageController extends Controller
{
    /**
     * @param GarageService $garageService Layanan pengelolaan unit garasi
     */
    public function __construct(
        private readonly GarageService $garageService
    ) {}

    /**
     * Menampilkan halaman daftar dan form manajemen unit garasi.
     *
     * @return View
     */
    public function index(): View
    {
        $garages = $this->garageService->getAll();

        return view('admin.garages', compact('garages'));
    }

    /**
     * Menyimpan unit garasi baru ke database.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => ['required', 'string', 'max:10', 'unique:garages,code'],
            'type'       => ['required', 'in:S,C,E'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'kwh_rate'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->garageService->create($validated);

        return back()->with('success', 'Unit garasi berhasil ditambahkan.');
    }

    /**
     * Memperbarui data unit garasi yang dipilih.
     *
     * @param Request $request
     * @param Garage $garage Model unit garasi
     * @return RedirectResponse
     */
    public function update(Request $request, Garage $garage): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => ['required', 'string', 'max:10', 'unique:garages,code,' . $garage->id],
            'type'       => ['required', 'in:S,C,E'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'kwh_rate'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->garageService->update($garage, $validated);

        return back()->with('success', 'Unit garasi berhasil diperbarui.');
    }

    /**
     * Menghapus unit garasi jika tidak sedang digunakan.
     *
     * @param Garage $garage Model unit garasi
     * @return RedirectResponse
     */
    public function destroy(Garage $garage): RedirectResponse
    {
        try {
            $this->garageService->delete($garage);
            return back()->with('success', 'Unit garasi berhasil dihapus.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}