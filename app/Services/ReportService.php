<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Support\Carbon;

/**
 * Menyediakan rekapitulasi laporan pendapatan dan statistik penggunaan garasi untuk Admin.
 */
class ReportService
{
    /**
     * Menghitung ringkasan pendapatan sewa awal, perpanjangan, jumlah status transaksi, dan rincian per unit garasi.
     *
     * @param Carbon|null $from Batas awal tanggal filter laporan
     * @param Carbon|null $to Batas akhir tanggal filter laporan
     * @return array{
     *     initial_revenue: float,
     *     extension_revenue: float,
     *     total_revenue: float,
     *     status_counts: array<string, int>,
     *     garage_breakdown: \Illuminate\Database\Eloquent\Collection<int, Garage>
     * }
     */
    public function getSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $paymentQuery = Payment::query()
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to->toDateString()));

        $initialRevenue   = (float) (clone $paymentQuery)->where('payment_type', 'initial')->sum('amount');
        $extensionRevenue = (float) (clone $paymentQuery)->where('payment_type', 'extension')->sum('amount');

        $rentalQuery = Rental::query()
            ->when($from, fn ($q) => $q->whereDate('start_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('start_date', '<=', $to->toDateString()));

        $statusCounts = [
            'booked'    => (clone $rentalQuery)->where('status', 'booked')->count(),
            'active'    => (clone $rentalQuery)->where('status', 'active')->count(),
            'completed' => (clone $rentalQuery)->where('status', 'completed')->count(),
            'cancelled' => (clone $rentalQuery)->where('status', 'cancelled')->count(),
        ];

        $garageBreakdown = Garage::withCount(['rentals' => function ($q) use ($from, $to) {
                $q->where('status', '!=', 'cancelled')
                  ->when($from, fn ($sub) => $sub->whereDate('start_date', '>=', $from->toDateString()))
                  ->when($to, fn ($sub) => $sub->whereDate('start_date', '<=', $to->toDateString()));
            }])
            ->withSum(['rentals as total_revenue' => function ($q) use ($from, $to) {
                $q->where('status', '!=', 'cancelled')
                  ->when($from, fn ($sub) => $sub->whereDate('start_date', '>=', $from->toDateString()))
                  ->when($to, fn ($sub) => $sub->whereDate('start_date', '<=', $to->toDateString()));
            }], 'total_cost')
            ->orderBy('code')
            ->get();

        return [
            'initial_revenue'   => $initialRevenue,
            'extension_revenue' => $extensionRevenue,
            'total_revenue'     => round($initialRevenue + $extensionRevenue, 2),
            'status_counts'     => $statusCounts,
            'garage_breakdown'  => $garageBreakdown,
        ];
    }
}