<?php

use App\Http\Controllers\CompletionRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderEdkController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rute default aplikasi
// Mengecek apakah pengguna sudah login (auth()->check())
// Jika sudah, arahkan (redirect) ke halaman dashboard, jika belum arahkan ke halaman login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Rute untuk halaman Dashboard
// Middleware 'auth' dan 'verified' memastikan hanya pengguna yang login dan terverifikasi yang bisa mengaksesnya
Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// Mengelompokkan rute-rute yang mewajibkan pengguna untuk login (auth)
Route::middleware('auth')->group(function () {
    // Rute untuk mengelola profil pengguna (menampilkan, memperbarui, dan menghapus profil)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['patch', 'post'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Mengelompokkan rute untuk fitur Status Pesanan
    // Middleware 'can:order_status.view' menggunakan Gate (otorisasi) untuk memastikan hanya peran tertentu yang bisa mengakses
    Route::middleware('can:order_status.view')->group(function () {
        Route::get('/order-statuses', [OrderStatusController::class, 'index'])->name('order-statuses.index');
        Route::post('/order-statuses', [OrderStatusController::class, 'store'])->name('order-statuses.store');
        Route::put('/order-statuses/{order_status}', [OrderStatusController::class, 'update'])->name('order-statuses.update');
        Route::delete('/order-statuses/{order_status}', [OrderStatusController::class, 'destroy'])->name('order-statuses.destroy');
    });

    Route::middleware('can:order_edk.view')->group(function () {
        Route::get('/order-edks', [OrderEdkController::class, 'index'])->name('order-edks.index');
        Route::post('/order-edks', [OrderEdkController::class, 'store'])->name('order-edks.store');
        Route::put('/order-edks/{order_edk}', [OrderEdkController::class, 'update'])->name('order-edks.update');
        Route::delete('/order-edks/{order_edk}', [OrderEdkController::class, 'destroy'])->name('order-edks.destroy');
    });

    Route::middleware('can:complete.view')->group(function () {
        Route::get('/completion-records', [CompletionRecordController::class, 'index'])->name('completion-records.index');
        Route::post('/completion-records', [CompletionRecordController::class, 'store'])->name('completion-records.store');
        Route::put('/completion-records/{completion_record}', [CompletionRecordController::class, 'update'])->name('completion-records.update');
        Route::patch('/completion-records/{completion_record}/approval', [CompletionRecordController::class, 'approve'])->name('completion-records.approval');
        Route::delete('/completion-records/{completion_record}', [CompletionRecordController::class, 'destroy'])->name('completion-records.destroy');
    });

    Route::middleware('can:user.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

require __DIR__.'/auth.php';
