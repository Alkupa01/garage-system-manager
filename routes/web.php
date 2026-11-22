<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return Auth::user()->role === 'admin'
            ? redirect()->route('admin.rentals.index')
            : redirect()->route('tenant.rentals.index');
    })->name('dashboard');

    Route::get('/files/{path}', function (string $path) {
    abort_unless(\Illuminate\Support\Facades\Storage::disk('public')->exists($path), 404);

    return response()->file(
        \Illuminate\Support\Facades\Storage::disk('public')->path($path)
    );
})->where('path', '.*')->name('files.show');

    // ADMIN ROUTES
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/tenants', [AuthController::class, 'tenants'])->name('tenants.index');
        Route::patch('/tenants/{user}/verify', [AuthController::class, 'verifyTenant'])->name('tenants.verify');

        Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
        Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');
        Route::put('/garages/{garage}', [GarageController::class, 'update'])->name('garages.update');
        Route::delete('/garages/{garage}', [GarageController::class, 'destroy'])->name('garages.destroy');

        Route::get('/rentals', [RentalController::class, 'adminIndex'])->name('rentals.index');
        Route::patch('/rentals/{rental}/status', [RentalController::class, 'updateStatus'])->name('rentals.status');
        Route::post('/rentals/{rental}/extend', [RentalController::class, 'extend'])->name('rentals.extend');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // TENANT ROUTES
    Route::middleware('role:tenant')->prefix('tenant')->name('tenant.')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
        Route::post('/profile/ktp', [AuthController::class, 'updateKtp'])->name('profile.ktp');

        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

        Route::get('/rentals', [RentalController::class, 'tenantIndex'])->name('rentals.index');
        Route::post('/rentals/quote', [RentalController::class, 'quote'])->name('rentals.quote');
        Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    });
});