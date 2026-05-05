<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainerCancellationController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CourtBookingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ChildController;

Route::get('/', function () {
    return redirect()->route('home');
});

Route::get('/home', function () {
    return view('layouts.home');
})->name('home');

Route::get('/rooms', [RoomController::class, 'showRooms'])->name('rooms.show');
Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.view');

Route::get('/trainings', [TrainingController::class, 'showTraining'])->name('trainings.show');
Route::get('/trainers/{trainer}', [TrainerController::class, 'show'])->name('trainers.show');

/* ---------- AUTH ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ---------- PROTECTED ---------- */
Route::middleware(['auth'])->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account');

    Route::get('/account/edit', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('/account/edit', [AccountController::class, 'update'])->name('account.update');

    Route::post('/account/password/send-code', [AccountController::class, 'sendPasswordCode'])->name('account.password.send-code');
    Route::post('/account/password/update', [AccountController::class, 'updatePassword'])->name('account.password.update');

    Route::post('/account/court-bookings/{group}/cancel', [AccountController::class, 'cancelCourtBooking'])
        ->name('account.court-bookings.cancel');

    Route::post('/account/court-bookings/{group}/persons', [AccountController::class, 'updateCourtBookingPersons'])
        ->name('account.court-bookings.update-persons');

    Route::get('/court-rent', [CourtBookingController::class, 'index'])->name('court-rent.index');
    Route::post('/court-rent/book', [CourtBookingController::class, 'store'])->name('court-rent.store');

    Route::post('/trainings/{training}/book', [BookingController::class, 'book'])->name('trainings.book');
    Route::post('/trainings/{training}/cancel', [BookingController::class, 'cancel'])->name('trainings.cancel');

    Route::post('/trainings/{training}/request-cancel', [TrainerCancellationController::class, 'requestCancel'])
        ->name('trainings.request_cancel');

    Route::get('/subscriptions/choose', [SubscriptionController::class, 'choose'])->name('subscriptions.choose');
    Route::get('/subscriptions/all', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/payment/init', [SubscriptionController::class, 'paymentInit'])->name('subscriptions.payment.init');
    Route::get('/subscriptions/payment/verify', [SubscriptionController::class, 'paymentVerify'])->name('subscriptions.payment.verify');
    Route::post('/subscriptions/payment/complete', [SubscriptionController::class, 'paymentComplete'])->name('subscriptions.payment.complete');
    Route::post('/subscriptions/installment-payment/init', [SubscriptionController::class, 'installmentPaymentInit'])->name('subscriptions.installment-payment.init');
    Route::get('/subscriptions/installment-payment/verify', [SubscriptionController::class, 'installmentPaymentVerify'])->name('subscriptions.installment-payment.verify');
    Route::post('/subscriptions/installment-payment/complete', [SubscriptionController::class, 'installmentPaymentComplete'])->name('subscriptions.installment-payment.complete');
    Route::get('/subscriptions/history', [SubscriptionController::class, 'history'])->name('subscriptions.history');

    Route::get('/account/children/create', [ChildController::class, 'create'])->name('account.children.create');
    Route::post('/account/children', [ChildController::class, 'store'])->name('account.children.store');
    Route::get('/account/children/{child}/edit', [ChildController::class, 'edit'])->name('account.children.edit');
    Route::post('/account/children/{child}/update', [ChildController::class, 'update'])->name('account.children.update');
});

/* ---------- ADMIN ---------- */
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.panel');
    Route::post('/rooms', [AdminController::class, 'storeRoom'])->name('admin.rooms.store');
    Route::post('/rooms/{room}/update', [AdminController::class, 'updateRoom'])->name('admin.rooms.update');
    Route::post('/courts/{room}/rent-settings', [AdminController::class, 'updateCourtRentSettings'])->name('admin.courts.rent-settings');

    Route::get('/users', [AdminController::class, 'showPanel'])->name('admin.panel');
    Route::post('/users/{user}/update-role', [AdminController::class, 'updateUserRole'])->name('admin.users.update-role');

    Route::get('/trainings/create', [AdminController::class, 'createTraining'])->name('admin.trainings.create');
    Route::get('/trainings/availability', [AdminController::class, 'availability'])->name('admin.trainings.availability');
    Route::post('/trainings', [AdminController::class, 'storeTraining'])->name('admin.trainings.store');
    Route::post('/trainings/settings/{type}', [AdminController::class, 'updateTrainingTypeSetting'])->name('admin.trainings.settings.update');

    Route::get('/cancellations', [AdminController::class, 'cancellations'])->name('admin.cancellations');
    Route::post('/cancellations/{requestModel}/approve', [AdminController::class, 'approveCancellation'])->name('admin.cancellations.approve');
    Route::post('/cancellations/{requestModel}/reject', [AdminController::class, 'rejectCancellation'])->name('admin.cancellations.reject');
});