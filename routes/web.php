<?php

use App\Http\Controllers\Admin\AdmissionsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\RegistrationsController;
use App\Http\Controllers\Applicant\ApplicationDocumentController;
use App\Http\Controllers\Applicant\ApplicationWizardController;
use App\Http\Controllers\Applicant\DashboardController as ApplicantDashboard;
use App\Http\Controllers\Applicant\OfferController;
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
use App\Http\Controllers\Student\RegistrationController;
use App\Http\Controllers\Student\TimetableController;
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

        Route::get('/application', [ApplicationWizardController::class, 'show'])->name('applicant.application');
        Route::post('/application/start', [ApplicationWizardController::class, 'start'])->name('applicant.application.start');
        Route::post('/application/personal', [ApplicationWizardController::class, 'savePersonal'])->name('applicant.application.personal');
        Route::post('/application/education', [ApplicationWizardController::class, 'saveEducation'])->name('applicant.application.education');
        Route::post('/application/choices', [ApplicationWizardController::class, 'saveChoices'])->name('applicant.application.choices');
        Route::post('/application/submit', [ApplicationWizardController::class, 'submit'])->name('applicant.application.submit');
        Route::post('/application/withdraw', [ApplicationWizardController::class, 'withdraw'])->name('applicant.application.withdraw');

        Route::post('/application/documents', [ApplicationDocumentController::class, 'store'])->name('applicant.documents.store');
        Route::get('/application/documents/{document}/download', [ApplicationDocumentController::class, 'download'])->name('applicant.documents.download');

        Route::post('/offer/accept', [OfferController::class, 'accept'])->name('applicant.offer.accept');
        Route::post('/offer/decline', [OfferController::class, 'decline'])->name('applicant.offer.decline');
    });

    Route::prefix('student')->middleware(['verified', 'role:student'])->group(function (): void {
        Route::get('/', StudentDashboard::class)->name('student.dashboard');

        // Course registration
        Route::get('/registration', [RegistrationController::class, 'index'])->name('student.registration');
        Route::post('/registration/add', [RegistrationController::class, 'add'])->name('student.registration.add');
        Route::post('/registration/remove/{item}', [RegistrationController::class, 'remove'])->name('student.registration.remove');
        Route::post('/registration/submit', [RegistrationController::class, 'submit'])->name('student.registration.submit');

        Route::get('/timetable', [TimetableController::class, 'index'])->name('student.timetable');
    });

    Route::prefix('lecturer')->middleware(['verified', 'role:lecturer'])->group(function (): void {
        Route::get('/', LecturerDashboard::class)->name('lecturer.dashboard');
    });

    Route::prefix('admin')->middleware(['verified', 'role:super_admin,registrar,admissions_officer,faculty_admin,finance_officer,support_staff'])->group(function (): void {
        Route::get('/', AdminDashboard::class)->name('admin.dashboard');

        // Admissions queue (policy-scoped to officers/registrar beyond this gate).
        Route::get('/admissions', [AdmissionsController::class, 'index'])->name('admin.admissions.index');
        Route::get('/admissions/{application}', [AdmissionsController::class, 'show'])->name('admin.admissions.show');
        Route::post('/admissions/{application}/start-review', [AdmissionsController::class, 'startReview'])->name('admin.admissions.review');
        Route::post('/admissions/{application}/decide', [AdmissionsController::class, 'decide'])->name('admin.admissions.decide');

        Route::get('/admission-documents/{document}/download', [AdmissionsController::class, 'document'])->name('admin.admissions.document');
        Route::post('/admission-documents/{document}/verify', [AdmissionsController::class, 'verifyDocument'])->name('admin.admissions.document.verify');
        Route::post('/admission-documents/{document}/reject', [AdmissionsController::class, 'rejectDocument'])->name('admin.admissions.document.reject');

        // Course registration approvals (registry only — gated in the controller).
        Route::get('/registrations', [RegistrationsController::class, 'index'])->name('admin.registrations.index');
        Route::post('/registrations/{registration}/approve', [RegistrationsController::class, 'approve'])->name('admin.registrations.approve');
        Route::post('/registrations/{registration}/reject', [RegistrationsController::class, 'reject'])->name('admin.registrations.reject');
    });
});
