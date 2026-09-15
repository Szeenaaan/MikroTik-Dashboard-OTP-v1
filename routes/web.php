<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Services\MikrotikService;

// OTP

Route::redirect('/', '/hotspot');

Route::get('/hotspot', [HotspotController::class, 'index'])->name('hotspot.index');

Route::post('/hotspot/send-otp', [HotspotController::class, 'sendOtp'])->name('hotspot.send-otp')->middleware('throttle:hotspot.send-otp');

Route::get('/hotspot/otp', [HotspotController::class, 'showOtp'])->name('hotspot.otp')->middleware('otp.session');

Route::post('/hotspot/verify-otp', [HotspotController::class, 'verifyOtp'])->name('hotspot.verify-otp')->middleware(['otp.session', 'throttle:hotspot.verify-otp']);

Route::post('/hotspot/resend-otp', [HotspotController::class, 'resendOtp'])->name('hotspot.resend-otp')->middleware(['otp.session', 'throttle:hotspot.resend-otp']);

Route::get('/hotspot/success', [HotspotController::class, 'success'])->name('hotspot.success');

//Dashboard
Route::get('/application/activation', function () {
    return view('dashboard.activation');
})->name('application.activation')->middleware('application.activation.page');

Route::post('/application/activate', [AuthController::class, 'activateApplication'])->name('application.activate')->middleware('application.activation.response');

Route::get('/application/activation/status', function () {
    return view('dashboard.already-activated');
})->name('application.activation.status')->middleware('application.activated');

Route::middleware('application.activated')->group(function () {

    Route::middleware(['auth', 'admin'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/dashboard/users', [DashboardController::class, 'users'])
            ->name('dashboard.users');

        Route::post('/dashboard/users/{id}/block', [DashboardController::class, 'blockUser'])
            ->name('dashboard.users.block');

        Route::post('/dashboard/users/{id}/unblock', [DashboardController::class, 'unblockUser'])
            ->name('dashboard.users.unblock');

        Route::get('/dashboard/sessions', [DashboardController::class, 'sessions'])
            ->name('dashboard.sessions');

        Route::post('/dashboard/sessions/{id}/terminate', [DashboardController::class, 'terminateSession'])
            ->name('dashboard.sessions.terminate');

        Route::get('/dashboard/users/export', [DashboardController::class, 'exportUsers'])
            ->name('dashboard.users.export');

        Route::get('/dashboard/sessions/export', [DashboardController::class, 'exportSessions'])
            ->name('dashboard.sessions.export');

        Route::post('/dashboard/heartbeat', function () {
            session(['dashboard_last_seen' => now()->timestamp]);

            return response()->json([
                'status' => 'ok',
            ]);
        })->name('dashboard.heartbeat');

        Route::post('/dashboard/users/{id}/bypass', [DashboardController::class, 'bypassUser'])
            ->name('dashboard.users.bypass');

        Route::post('/dashboard/users/{id}/remove-bypass', [DashboardController::class, 'removeBypassUser'])
            ->name('dashboard.users.remove-bypass');
    });
});

//Login

Route::get('/login', function () {
    return view('dashboard.login');
})
    ->middleware(['application.activated', 'guest'])
    ->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware(['application.activated', 'throttle:login']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('application.activated');

Route::post('/signup', [AuthController::class, 'createUser'])
    ->name('signup')
    ->middleware(['application.activated', 'throttle:signup']);

Route::get('/signup', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->middleware('application.activated')->name('signup.page');