<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/account', [ProfileController::class, 'account'])->name('profile.account');
    Route::get('/profile/security', [ProfileController::class, 'security'])->name('profile.security');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions/store', [TransactionController::class, 'store'])->name('transactions.store');
    Route::post('/transactions/delete', [TransactionController::class, 'delete'])->name('transactions.delete');
    Route::post('/transactions/feature', [TransactionController::class, 'feature'])->name('transactions.feature');
    Route::post('/wishlist/store', [TransactionController::class, 'wishStore'])->name('wishlist.store');
    Route::post('/wishlist/delete', [TransactionController::class, 'wishDelete'])->name('wishlist.delete');
    Route::post('/goal', [TransactionController::class, 'goal'])->name('goal.update');
    Route::post('/limit', [TransactionController::class, 'limit'])->name('limit.update');
    Route::post('/budget', [TransactionController::class, 'budget'])->name('budget.update');
    Route::get('/export', [ReportController::class, 'export'])->name('transactions.export');
});
