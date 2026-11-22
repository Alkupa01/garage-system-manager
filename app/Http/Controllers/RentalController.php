<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use App\Services\ExtensionService;
use App\Services\RentalPricingService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Mengelola seluruh alur HTTP transaksi sewa garasi untuk Penyewa maupun Admin.
 */
class RentalController extends Controller
{
    /**
     * @param AvailabilityService $availabilityService Layanan pengecekan ketersediaan garasi
     * @param RentalPricingService $pricingService Layanan perhitungan tarif dan pembuatan booking
     * @param ExtensionService $extensionService Layanan perpanjangan durasi sewa
     */
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly RentalPricingService $pricingService,
        private readonly ExtensionService $extensionService
    ) {}

    /**
     * Menampilkan halaman pemesanan garasi baru dan riwayat sewa milik penyewa.
     *
     * @return View
     */
    public function tenantIndex(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $vehicles = $user->vehicles()
            ->with(['rentals' => function ($query) {
                $query->with('garage')
                    ->whereIn('status', ['booked', 'active'])
                    ->whereDate('end_date', '>=', now()->toDateString())
                    ->orderBy('start_date');
            }])
            ->get();

        $rentals = $user->rentals()
            ->with(['garage', 'vehicle', 'extensions.newGarage', 'payments'])
            ->latest()
            ->get();

        return view('tenant.rentals', compact('user', 'vehicles', 'rentals'));
    }

    /**
     * Mengembalikan daftar garasi tersedia, estimasi biaya, dan deteksi bentrok jadwal kendaraan dalam format JSON.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id'  => ['required', 'exists:vehicles,id'],
            'rental_type' => ['required', 'in:daily,monthly'],
            'start_date'  => ['required', 'date', 'after_or_equal:today'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'garage_id'   => ['nullable', 'exists:garages,id'],
        ]);

        /** @var Vehicle $vehicle */
        $vehicle     = Vehicle::where('user_id', Auth::id())->findOrFail($validated['vehicle_id']);
        $start       = Carbon::parse($validated['start_date']);
        $daysInMonth = $start->daysInMonth;

        $quantity = $validated['rental_type'] === 'monthly'
            ? 1
            : min((int) $validated['quantity'], $daysInMonth - 1);

        $days = $validated['rental_type'] === 'monthly' ? $daysInMonth : $quantity;
        $end  = $start->copy()->addDays($days - 1);

        $conflictingRental = $this->findVehicleConflict($vehicle->id, $start, $end);

        if ($conflictingRental) {
            return response()->json([
                'available_garages' => [],
                'quote'             => null,
                'vehicle_conflict'  => sprintf(
                    'Kendaraan ini sudah berstatus %s di Garasi %s pada %s s/d %s.',
                    strtoupper($conflictingRental->status),
                    $conflictingRental->garage->code,
                    $conflictingRental->start_date->format('d M Y'),
                    $conflictingRental->end_date->format('d M Y')
                ),
                'days_in_month'     => $daysInMonth,
                'max_daily_days'    => $daysInMonth - 1,
            ]);
        }

        $availableGarages = $this->availabilityService->getAvailableGarages($vehicle, $start, $end);

        $quote = null;
        if (!empty($validated['garage_id'])) {
            $selectedGarage = $availableGarages->firstWhere('id', (int) $validated['garage_id']);
            if ($selectedGarage instanceof Garage) {
                $quote = $this->pricingService->calculateQuote(
                    $selectedGarage,
                    $vehicle,
                    $validated['rental_type'],
                    $start,
                    $quantity
                );
            }
        }

        return response()->json([
            'available_garages' => $availableGarages,
            'quote'             => $quote,
            'vehicle_conflict'  => null,
            'days_in_month'     => $daysInMonth,
            'max_daily_days'    => $daysInMonth - 1,
        ]);
    }

    /**
     * Memvalidasi dan menyimpan pengajuan booking garasi baru oleh penyewa.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id'  => ['required', 'exists:vehicles,id'],
            'garage_id'   => ['required', 'exists:garages,id'],
            'rental_type' => ['required', 'in:daily,monthly'],
            'start_date'  => ['required', 'date', 'after_or_equal:today'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'proof_image' => ['required', 'image', 'max:2048'],
        ]);

        /** @var \App\Models\User $tenant */
        $tenant  = Auth::user();
        /** @var Vehicle $vehicle */
        $vehicle = Vehicle::where('user_id', $tenant->id)->findOrFail($validated['vehicle_id']);
        /** @var Garage $garage */
        $garage  = Garage::findOrFail($validated['garage_id']);

        $start       = Carbon::parse($validated['start_date']);
        $daysInMonth = $start->daysInMonth;
        $quantity    = $validated['rental_type'] === 'monthly'
            ? 1
            : min((int) $validated['quantity'], $daysInMonth - 1);

        $days = $validated['rental_type'] === 'monthly' ? $daysInMonth : $quantity;
        $end  = $start->copy()->addDays($days - 1);

        $conflictingRental = $this->findVehicleConflict($vehicle->id, $start, $end);
        if ($conflictingRental) {
            return back()
                ->withErrors([
                    'vehicle_id' => sprintf(
                        'Booking ditolak: kendaraan ini sudah berstatus %s di Garasi %s (%s s/d %s).',
                        strtoupper($conflictingRental->status),
                        $conflictingRental->garage->code,
                        $conflictingRental->start_date->format('d M Y'),
                        $conflictingRental->end_date->format('d M Y')
                    ),
                ])
                ->withInput();
        }

        try {
            $this->pricingService->createBooking(
                $tenant,
                $garage,
                $vehicle,
                $validated['rental_type'],
                $start,
                $quantity,
                $request->file('proof_image')
            );

            return back()->with('success', 'Booking garasi berhasil dibuat.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Mencari transaksi aktif atau booked untuk kendaraan tertentu yang bertabrakan dengan rentang tanggal.
     *
     * @param int $vehicleId ID kendaraan
     * @param Carbon $start Tanggal mulai yang diajukan
     * @param Carbon $end Tanggal selesai yang diajukan
     * @return Rental|null
     */
    private function findVehicleConflict(int $vehicleId, Carbon $start, Carbon $end): ?Rental
    {
        return Rental::with('garage')
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['booked', 'active'])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->first();
    }

    /**
     * Menampilkan seluruh daftar transaksi sewa garasi pada halaman Admin.
     *
     * @return View
     */
    public function adminIndex(): View
    {
        $rentals = Rental::with([
            'user',
            'garage',
            'vehicle',
            'extensions.previousGarage',
            'extensions.newGarage',
            'payments',
        ])->latest()->get();

        $garages = Garage::orderBy('code')->get();

        return view('admin.rentals', compact('rentals', 'garages'));
    }

    /**
     * Memperbarui status transaksi sewa (booked, active, completed, cancelled) oleh Admin.
     *
     * @param Request $request
     * @param Rental $rental Model transaksi sewa
     * @return RedirectResponse
     */
    public function updateStatus(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:booked,active,completed,cancelled'],
        ]);

        try {
            $this->pricingService->updateStatus($rental, $validated['status']);
            return back()->with('success', 'Status transaksi berhasil diperbarui.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Memproses perpanjangan masa sewa (extend) transaksi yang sedang berjalan oleh Admin.
     *
     * @param Request $request
     * @param Rental $rental Model transaksi sewa
     * @return RedirectResponse
     */
    public function extend(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate([
            'new_garage_id'  => ['required', 'exists:garages,id'],
            'extension_type' => ['required', 'in:daily,monthly'],
            'quantity'       => ['required', 'integer', 'min:1'],
            'proof_image'    => ['required', 'image', 'max:2048'],
        ]);

        /** @var \App\Models\User $admin */
        $admin     = Auth::user();
        /** @var Garage $newGarage */
        $newGarage = Garage::findOrFail($validated['new_garage_id']);
        $quantity  = $validated['extension_type'] === 'monthly' ? 1 : (int) $validated['quantity'];

        try {
            $this->extensionService->extendRental(
                $rental,
                $admin,
                $newGarage,
                $validated['extension_type'],
                $quantity,
                $request->file('proof_image')
            );

            return back()->with('success', 'Perpanjangan sewa berhasil diproses.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}