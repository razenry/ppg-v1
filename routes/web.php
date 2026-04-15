<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\SSOController;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\UserManager;
use App\Livewire\Client\MyPlan;
use App\Livewire\Client\ServerDetail;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

// SSO Routes
Route::get('sso/login', [SSOController::class, 'login'])->name('sso.login');
Route::post('sso/return', [SSOController::class, 'returnToAdmin'])->name('sso.return');

Route::middleware(['auth', 'verified', '2fa.enforce'])->group(function () {
    Route::view('dashboard', 'pages.dashboard.index')->name('dashboard');

    // Client Servers
    Route::get('my-plan', MyPlan::class)->name('my-plan');
    Route::view('servers', 'pages.servers.index')->name('servers.index');
    Route::get('servers/{id}', ServerDetail::class)->name('servers.show');

    // Admin Routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::view('/', 'pages.admin.index')->name('index');
        Route::view('nodes', 'pages.admin.nodes')->name('nodes');
        Route::view('plans', 'pages.admin.plans')->name('plans');
        Route::view('servers', 'pages.admin.servers')->name('servers');
        Route::get('users', UserManager::class)->name('users');
        Route::get('settings', Settings::class)->name('settings');

        // Impersonation
        Route::get('impersonate/start/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    });

    Route::post('admin/impersonate/stop', [ImpersonationController::class, 'stop'])->name('admin.impersonate.stop');
});

require __DIR__.'/settings.php';
