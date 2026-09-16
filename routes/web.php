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
    
    Route::get('agent-accounts', [App\Http\Controllers\AgentAccountController::class, 'index'])->name('agent-accounts.index');
    Route::get('agent-accounts/{id}', [App\Http\Controllers\AgentAccountController::class, 'show'])->name('agent-accounts.show');

    // Settings Module
    Route::get('/settings/pricing', [App\Http\Controllers\SettingsController::class, 'pricing'])->name('settings.pricing');
    Route::post('/settings/pricing', [App\Http\Controllers\SettingsController::class, 'updatePricing'])->name('settings.pricing.update');
    
    // Devotees Module
    Route::get('devotees/export', [App\Http\Controllers\DevoteeController::class, 'export'])->name('devotees.export');
    Route::get('devotees/export-json', [App\Http\Controllers\DevoteeController::class, 'exportJson'])->name('devotees.exportJson');
    Route::get('devotees/import/template', [App\Http\Controllers\DevoteeController::class, 'downloadTemplate'])->name('devotees.import_template');
    Route::post('devotees/import', [App\Http\Controllers\DevoteeController::class, 'import'])->name('devotees.import');
    Route::get('devotees/{devotee}/family/create', [App\Http\Controllers\DevoteeController::class, 'createFamilyMember'])->name('devotees.create_family_member');
    Route::post('devotees/{devotee}/quick-booking', [App\Http\Controllers\DevoteeController::class, 'quickBooking'])->name('devotees.quick_booking');
    Route::patch('devotees/{devotee}/referred', [App\Http\Controllers\DevoteeController::class, 'updateReferred'])->name('devotees.update_referred');
    Route::get('api/devotees/{devotee}/family', [App\Http\Controllers\BookingController::class, 'getFamilyMembers'])->name('api.devotees.family');
    Route::resource('devotees', App\Http\Controllers\DevoteeController::class);
});

// Tier 2: Accessible ONLY by Operators and Super Admins
Route::middleware(['auth', 'role:Super Admin|Operator'])->group(function () {
    // Other Modules
    Route::post('phone-usages/{phone_usage}/bookings', [App\Http\Controllers\PhoneUsageController::class, 'storeBooking'])->name('phone-usages.bookings.store');
    Route::get('phone-usages/export', [App\Http\Controllers\PhoneUsageController::class, 'export'])->name('phone-usages.export');
    Route::resource('phone-usages', App\Http\Controllers\PhoneUsageController::class);
    
    Route::patch('bookings/{booking}/status', [App\Http\Controllers\BookingController::class, 'updateStatus'])->name('bookings.updateStatus');
    Route::resource('bookings', App\Http\Controllers\BookingController::class);
    Route::resource('investments', App\Http\Controllers\InvestmentController::class);
    
    Route::get('revenues/export', [App\Http\Controllers\RevenueController::class, 'export'])->name('revenues.export');
    
    Route::get('revenues/import', [App\Http\Controllers\RevenueImportController::class, 'index'])->name('revenues.import');
    Route::post('revenues/import/preview', [App\Http\Controllers\RevenueImportController::class, 'preview'])->name('revenues.import.preview');
    Route::post('revenues/import/confirm', [App\Http\Controllers\RevenueImportController::class, 'confirm'])->name('revenues.import.confirm');
    
    Route::delete('revenues/agent/{agent_name}', [App\Http\Controllers\RevenueController::class, 'destroyAgent'])->name('revenues.destroyAgent');
    Route::resource('revenues', App\Http\Controllers\RevenueController::class);

    // Indian Stocks Module
    Route::get('api/indian-stocks/live', [App\Http\Controllers\IndianStockController::class, 'getLivePrices'])->name('api.indian-stocks.live');
    Route::get('indian-stocks/export', [App\Http\Controllers\IndianStockController::class, 'export'])->name('indian-stocks.export');
    Route::resource('indian-stocks', App\Http\Controllers\IndianStockController::class);
});

// Tier 3: Accessible ONLY by Super Admins
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::patch('users/{user}/role', [App\Http\Controllers\UserController::class, 'updateRole'])->name('users.updateRole');
    Route::resource('users', App\Http\Controllers\UserController::class)->except(['create', 'store', 'show', 'edit', 'update']);
});

require __DIR__.'/auth.php';
