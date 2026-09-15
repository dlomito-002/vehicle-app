<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleComparisonController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleDeliveryController;
use App\Http\Controllers\VehicleReceptionController;
use App\Http\Controllers\VehicleServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Availability calendar — verify which vehicles are free/in-use by date.
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Vehicle reception.
    Route::get('/receptions', [VehicleReceptionController::class, 'index'])->name('receptions.index');
    Route::get('/receptions/create', [VehicleReceptionController::class, 'create'])->name('receptions.create');
    Route::post('/receptions', [VehicleReceptionController::class, 'store'])->name('receptions.store');
    Route::get('/receptions/{reception}', [VehicleReceptionController::class, 'show'])->name('receptions.show');

    // Vehicle delivery/return — explicit vehicle -> open-reception selection flow.
    Route::get('/deliveries', [VehicleDeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/select-vehicle', [VehicleDeliveryController::class, 'selectVehicle'])->name('deliveries.select-vehicle');
    Route::get('/deliveries/select-reception/{vehicle}', [VehicleDeliveryController::class, 'selectReception'])->name('deliveries.select-reception');
    Route::get('/deliveries/damage-report/{reception}', [VehicleDeliveryController::class, 'damageReport'])->name('deliveries.damage-report');
    Route::get('/deliveries/damage-report/{reception}/pdf', [VehicleDeliveryController::class, 'damageReportPdf'])->name('deliveries.damage-report.pdf');
    Route::get('/deliveries/create/{reception}', [VehicleDeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('/deliveries/{reception}', [VehicleDeliveryController::class, 'store'])->name('deliveries.store');
    Route::get('/deliveries/{delivery}', [VehicleDeliveryController::class, 'show'])->name('deliveries.show');

    // Reception vs. delivery comparison report.
    Route::get('/comparisons/{reception}', [VehicleComparisonController::class, 'show'])->name('comparisons.show');
    Route::get('/comparisons/{reception}/pdf', [VehicleComparisonController::class, 'pdf'])->name('comparisons.pdf');

    // Maintenance registry, visible to everyone; mutations are admin-only below.
    Route::get('/services', [VehicleServiceController::class, 'index'])->name('services.index');

    // Vehicle master data, user management and service mutations — admin only.
    Route::middleware('admin')->group(function () {
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');

        Route::get('/services/create', [VehicleServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [VehicleServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}/edit', [VehicleServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{service}', [VehicleServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [VehicleServiceController::class, 'destroy'])->name('services.destroy');

        Route::resource('users', UserController::class)->except(['show']);
    });
});
