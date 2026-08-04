<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Route for registered users who don't have an admin role yet
Route::get('/pending-approval', function () {
    return view('pending-approval');
})->middleware(['auth'])->name('pending.approval');

// Tier 1: Accessible by Regular Users, Operators, and Super Admins
Route::middleware(['auth', 'role:Super Admin|Operator|User'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Devotees Module
    Route::get('devotees/export', [App\Http\Controllers\DevoteeController::class, 'export'])->name('devotees.export');
    Route::get('devotees/export-json', [App\Http\Controllers\DevoteeController::class, 'exportJson'])->name('devotees.exportJson');
    Route::get('devotees/{devotee}/family/create', [App\Http\Controllers\DevoteeController::class, 'createFamilyMember'])->name('devotees.create_family_member');
    Route::get('api/devotees/{devotee}/family', [App\Http\Controllers\BookingController::class, 'getFamilyMembers'])->name('api.devotees.family');
    Route::resource('devotees', App\Http\Controllers\DevoteeController::class);
});

// Tier 2: Accessible ONLY by Operators and Super Admins
Route::middleware(['auth', 'role:Super Admin|Operator'])->group(function () {
    // Other Modules
    Route::post('phone-usages/{phone_usage}/bookings', [App\Http\Controllers\PhoneUsageController::class, 'storeBooking'])->name('phone-usages.bookings.store');
    Route::resource('phone-usages', App\Http\Controllers\PhoneUsageController::class);
    
    Route::patch('bookings/{booking}/status', [App\Http\Controllers\BookingController::class, 'updateStatus'])->name('bookings.updateStatus');
    Route::resource('bookings', App\Http\Controllers\BookingController::class);
    Route::resource('investments', App\Http\Controllers\InvestmentController::class);
    
    Route::get('revenues/export', [App\Http\Controllers\RevenueController::class, 'export'])->name('revenues.export');
    Route::resource('revenues', App\Http\Controllers\RevenueController::class);
});

// Tier 3: Accessible ONLY by Super Admins
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::patch('users/{user}/role', [App\Http\Controllers\UserController::class, 'updateRole'])->name('users.updateRole');
    Route::resource('users', App\Http\Controllers\UserController::class)->except(['create', 'store', 'show', 'edit', 'update']);
});

require __DIR__.'/auth.php';
