<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Applicant\DashboardController as ApplicantDashboard;
use App\Http\Controllers\Auth\ApplicantRegistrationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Lecturer\DashboardController as LecturerDashboard;
use App\Http\Controllers\PublicAcademicsController;
use App\Http\Controllers\PublicAdmissionsController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicNewsController;
use App\Http\Controllers\Shared\NotificationsController;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', PublicHomeController::class)->name('home');

Route::get('/about', fn () => view('public.about'))->name('about');
Route::get('/campus-life', fn () => view('public.campus-life'))->name('campus-life');
Route::get('/research', fn () => view('public.research'))->name('research');

Route::get('/academics', [PublicAcademicsController::class, 'index'])->name('academics');
Route::get('/academics/{programme:slug}', [PublicAcademicsController::class, 'show'])->name('programmes.show');

Route::get('/admissions', PublicAdmissionsController::class)->name('admissions');

Route::get('/news', [PublicNewsController::class, 'index'])->name('news.index');
Route::get('/news/{article:slug}', [PublicNewsController::class, 'show'])->name('news.show');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

/*
|--------------------------------------------------------------------------
| Guest authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [ApplicantRegistrationController::class, 'create'])->name('register');
    Route::post('register', [ApplicantRegistrationController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated & shared
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::post('notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{id}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');

    /*
    |----------------------------------------------------------------------
    | Portals — role middleware IS the access boundary, not just navigation.
    | Guests hitting these paths are redirected to login by EnsureRole.
    |----------------------------------------------------------------------
    */
    Route::prefix('applicant')->middleware(['verified', 'role:applicant'])->group(function (): void {
        Route::get('/', ApplicantDashboard::class)->name('applicant.dashboard');
    });

    Route::prefix('student')->middleware(['verified', 'role:student'])->group(function (): void {
        Route::get('/', StudentDashboard::class)->name('student.dashboard');
    });

    Route::prefix('lecturer')->middleware(['verified', 'role:lecturer'])->group(function (): void {
        Route::get('/', LecturerDashboard::class)->name('lecturer.dashboard');
    });

    Route::prefix('admin')->middleware(['verified', 'role:super_admin,registrar,admissions_officer,faculty_admin,finance_officer,support_staff'])->group(function (): void {
        Route::get('/', AdminDashboard::class)->name('admin.dashboard');
    });
});
