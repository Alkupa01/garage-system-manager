<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Mengelola tampilan laporan keuangan dan okupansi unit garasi untuk Admin.
 */
class ReportController extends Controller
{
    /**
     * @param ReportService $reportService Layanan agregasi data laporan keuangan
     */
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Menampilkan halaman laporan pendapatan dan statistik transaksi berdasarkan filter rentang tanggal.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from    = !empty($validated['from']) ? Carbon::parse($validated['from']) : null;
        $to      = !empty($validated['to']) ? Carbon::parse($validated['to']) : null;
        $summary = $this->reportService->getSummary($from, $to);

        return view('admin.reports', [
            'summary' => $summary,
            'from'    => $validated['from'] ?? '',
            'to'      => $validated['to'] ?? '',
        ]);
    }
}