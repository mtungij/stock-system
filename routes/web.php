<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Registration Steps
Route::get('register/company', [App\Http\Controllers\Auth\RegistrationStepController::class, 'showCompany'])
    ->middleware('guest')
    ->name('register.company');
Route::post('register/company', [App\Http\Controllers\Auth\RegistrationStepController::class, 'storeCompany'])
    ->middleware('guest')
    ->name('register.company.store');

Route::get('register/branch', [App\Http\Controllers\Auth\RegistrationStepController::class, 'showBranch'])
    ->middleware('guest')
    ->name('register.branch');
Route::post('register/branch', [App\Http\Controllers\Auth\RegistrationStepController::class, 'storeBranch'])
    ->middleware('guest')
    ->name('register.branch.store');

Route::get('register/admin', [App\Http\Controllers\Auth\RegistrationStepController::class, 'showAdmin'])
    ->middleware('guest')
    ->name('register.admin');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

    Route::livewire('/stock/create', 'pages::stock.create');
    Route::livewire('/stock/adjust', 'pages::stock.adjust');
    Route::livewire('categories/create','pages::categories.create');
    Route::livewire('branch/create','pages::branch.create');
    Route::livewire('user/create','pages::user.create');
    Route::livewire('sell','pages::sell.sell');
    Route::livewire('purchase/create','pages::purchase.create');
    Route::livewire('supplier/create','pages::supplier.create');
    Route::livewire('reports/report','pages::reports.report');
    Route::livewire('reports/sales-report','pages::reports.sales-report');
    Route::livewire('reports/outstock','pages::reports.outstock');
    Route::livewire('reports/branch-performance','pages::reports.branch-performance');
    
    Route::get('/stock/pdf', [App\Http\Controllers\StockController::class, 'showPdf'])
        ->middleware(['auth'])->name('stock.pdf');
    Route::get('/stock/pdf/download', [App\Http\Controllers\StockController::class, 'downloadPdf'])
        ->middleware(['auth'])->name('stock.pdf.download');

require __DIR__.'/settings.php';
