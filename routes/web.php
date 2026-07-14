<?php

use App\Http\Controllers\Admin\AdminArticleCategoryController;
use App\Http\Controllers\Admin\AdminArticleController;
use App\Http\Controllers\Admin\AdminDoctorApplicationController;
use App\Http\Controllers\Admin\AdminMessagesController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminSpecialtyController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\DoctorApplicationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Doctor\DoctorAlertsController;
use App\Http\Controllers\Doctor\DoctorDashboardController;
use App\Http\Controllers\Doctor\DoctorPlansController;
use App\Http\Controllers\Doctor\DoctorReportsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Patient\AiChatController;
use App\Http\Controllers\Patient\PatientAppointmentController;
use App\Http\Controllers\Patient\PatientArticleController;
use App\Http\Controllers\Patient\PatientCalorieController;
use App\Http\Controllers\Patient\PatientDashboardController;
use App\Http\Controllers\Patient\PatientDoctorController;
use App\Http\Controllers\Patient\PatientMessageController;
use App\Http\Controllers\Patient\PatientNotificationController;
use App\Http\Controllers\Patient\PatientProfileController;
use App\Http\Controllers\Patient\PatientTaskController;
use App\Http\Controllers\PublicArticleController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;




/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/join-doctor', [DoctorApplicationController::class, 'create'])
    ->name('join-doctor');

Route::post('/join-doctor', [DoctorApplicationController::class, 'store'])
    ->name('join-doctor.store');

Route::post('/join-doctor/check-email', [DoctorApplicationController::class, 'checkEmail'])
    ->name('join-doctor.check-email');

Route::get('/articles', [PublicArticleController::class, 'index'])
    ->name('articles');

Route::get('/articles/{slug}', [PublicArticleController::class, 'show'])
    ->name('articles.show');

Route::get('/article-details', function () {
    return redirect()->route('articles');
})->name('article-details');

Route::get('/contact', [ContactController::class, 'create'])
    ->name('contact');

Route::post('/contact/messages', [ContactController::class, 'store'])
    ->name('contact.messages.store');

Route::get('/terms', function () {
    return view('pages.terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('pages.privacy');
})->name('privacy');

Route::get('/quiz', [QuizController::class, 'index'])
    ->name('quiz.index');

Route::post('/quiz/analyze', [QuizController::class, 'analyze'])
    ->name('quiz.analyze');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::get('/register', [AuthController::class, 'showRegister'])
    ->name('register');

Route::post('/register', [AuthController::class, 'register'])
    ->name('register.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Forgot Password Routes
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])
    ->name('forgot-password');

Route::middleware('throttle:6,1')->group(function () {
    Route::post('/forgot-password/send-code', [ForgotPasswordController::class, 'sendCode'])
        ->name('forgot-password.send-code');

    Route::post('/forgot-password/verify-code', [ForgotPasswordController::class, 'verifyCode'])
        ->name('forgot-password.verify-code');

    Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword'])
        ->name('forgot-password.reset');

    Route::post('/forgot-password/resend-code', [ForgotPasswordController::class, 'resendCode'])
        ->name('forgot-password.resend-code');
});

Route::get('/forgot-password/direct-setup', [ForgotPasswordController::class, 'directSetup'])
    ->middleware('signed')
    ->name('forgot-password.direct-setup');

/*
|--------------------------------------------------------------------------
| Social Login Routes
|--------------------------------------------------------------------------
*/

Route::get('/auth/google/redirect', [SocialiteController::class, 'redirectToGoogle'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback'])
    ->name('google.callback');

Route::get('/auth/facebook/redirect', [SocialiteController::class, 'redirectToFacebook'])
    ->name('facebook.redirect');

Route::get('/auth/facebook/callback', [SocialiteController::class, 'handleFacebookCallback'])
    ->name('facebook.callback');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/search', [\App\Http\Controllers\Admin\AdminSearchController::class, 'search'])
            ->name('search');

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/doctor-applications', [AdminDoctorApplicationController::class, 'index'])
            ->name('doctor-applications');

        Route::get('/doctor-applications/{doctorApplication}', [AdminDoctorApplicationController::class, 'show'])
            ->name('doctor-applications-show');

        Route::patch('/doctor-applications/{doctorApplication}/status', [AdminDoctorApplicationController::class, 'updateStatus'])
            ->name('doctor-applications-update-status');

        Route::get('/doctor-applications/{doctorApplication}/file/{type}/view', [AdminDoctorApplicationController::class, 'viewFile'])
            ->name('doctor-applications-file-viewer');

        Route::get('/doctor-applications/{doctorApplication}/file/{type}', [AdminDoctorApplicationController::class, 'previewFile'])
            ->name('doctor-applications-file-preview');

        Route::get('/doctor-applications/{doctorApplication}/file/{type}/download', [AdminDoctorApplicationController::class, 'downloadFile'])
            ->name('doctor-applications-file-download');

        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('admin-users');

        Route::get('/users/{user}', [AdminUserController::class, 'show'])
            ->name('admin-users.show');

        Route::put('/users/{user}', [AdminUserController::class, 'update'])
            ->name('admin-users.update');

        Route::patch('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])
            ->name('admin-users.toggle-status');

        Route::delete('/users/bulk', [AdminUserController::class, 'bulkDestroy'])
            ->name('admin-users.bulk-destroy');

        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])
            ->name('admin-users.destroy');

        Route::get('/settings', [SettingController::class, 'index'])
            ->name('settings');

        Route::post('/settings', [SettingController::class, 'update'])
            ->name('settings.update');

        Route::resource('article-categories', AdminArticleCategoryController::class)
            ->except(['show', 'create']);

        Route::resource('specialties', AdminSpecialtyController::class)
            ->except(['show', 'create']);

        Route::post('/articles/ai-generate', [AdminArticleController::class, 'generateAiDraft'])
            ->middleware('throttle:10,1')
            ->name('articles.ai-generate');

        Route::resource('articles', AdminArticleController::class)
            ->except(['show']);

        Route::patch('/articles/{article}/approve', [AdminArticleController::class, 'approve'])
            ->name('articles.approve');

        Route::patch('/articles/{article}/reject', [AdminArticleController::class, 'reject'])
            ->name('articles.reject');

        Route::prefix('notifications')
            ->name('notifications.')
            ->group(function () {
                Route::get('/', [AdminNotificationController::class, 'index'])
                    ->name('index');

                Route::get('/unread-count', [AdminNotificationController::class, 'unreadCount'])
                    ->name('unread-count');

                Route::get('/dropdown', [AdminNotificationController::class, 'dropdown'])
                    ->name('dropdown');

                Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllRead'])
                    ->name('mark-all-read');

                Route::get('/{adminNotification}/open', [AdminNotificationController::class, 'open'])
                    ->name('open');

                Route::post('/{adminNotification}/read', [AdminNotificationController::class, 'markRead'])
                    ->name('mark-read');
            });

        Route::prefix('messages')
            ->name('messages.')
            ->group(function () {
                Route::get('/', [AdminMessagesController::class, 'index'])
                    ->name('index');

                Route::get('/{conversation}', [AdminMessagesController::class, 'show'])
                    ->name('show');

                Route::post('/{conversation}/send', [AdminMessagesController::class, 'send'])
                    ->name('send');

                Route::get('/{conversation}/poll', [AdminMessagesController::class, 'poll'])
                    ->name('poll');

                Route::patch('/{conversation}/status', [AdminMessagesController::class, 'updateStatus'])
                    ->name('status');
            });
    });

/*
|--------------------------------------------------------------------------
| Patient Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Patient Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'patient'])
    ->prefix('patient')
    ->name('patient.')
    ->group(function () {
        Route::get('/', [PatientDashboardController::class, 'index'])
            ->name('index');

        Route::get('/home', [PatientDashboardController::class, 'home'])
            ->name('home');

        /*
        |--------------------------------------------------------------------------
        | Patient Journey / Tasks
        |--------------------------------------------------------------------------
        */

        Route::get('/journey', [PatientTaskController::class, 'index'])
            ->name('journey');

        // لو انفتح رابط الحفظ بالغلط كـ GET يرجع المستخدم لصفحة رحلتي
        Route::get('/journey/tasks', function () {
            return redirect()->route('patient.journey');
        })->name('journey.tasks.index');

        Route::post('/journey/tasks', [PatientTaskController::class, 'store'])
            ->name('journey.tasks.store');

        Route::patch('/journey/tasks/{task}/complete', [PatientTaskController::class, 'complete'])
            ->name('journey.tasks.complete');

        Route::delete('/journey/tasks/{task}', [PatientTaskController::class, 'destroy'])
            ->name('journey.tasks.destroy');

        Route::post('/journey/tasks/{task}/attachment', [PatientTaskController::class, 'uploadAttachment'])
             ->name('journey.tasks.attachment');
        /*
        |--------------------------------------------------------------------------
        | Followup / Appointments
        |--------------------------------------------------------------------------
        */

        Route::get('/followup', [PatientAppointmentController::class, 'followUp'])
            ->name('followup');

        Route::post('/appointments/book', [PatientAppointmentController::class, 'bookAppointment'])
            ->name('appointments.book');

        Route::put('/appointments/{appointment}/update', [PatientAppointmentController::class, 'updateAppointment'])
            ->name('appointments.update');

        Route::post('/appointments/{appointment}/suggestion/accept', [PatientAppointmentController::class, 'acceptSuggestedAppointment'])
            ->name('appointments.suggestion.accept');

        Route::post('/appointments/{appointment}/suggestion/decline', [PatientAppointmentController::class, 'declineSuggestedAppointment'])
            ->name('appointments.suggestion.decline');

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */

        Route::get('/profile', [PatientProfileController::class, 'profile'])
            ->name('profile');

        Route::post('/profile/complete', [PatientProfileController::class, 'completeProfile'])
            ->name('profile.complete');

       Route::get('/doctors/recommended', [PatientDoctorController::class, 'recommendedDoctorsPage'])
            ->name('doctors.recommended');

        Route::get('/doctors/{doctorProfile}/details', [PatientDoctorController::class, 'doctorDetails'])
            ->name('doctors.details');

        Route::post('/doctors/{doctorProfile}/select', [PatientDoctorController::class, 'selectDoctor'])
            ->name('doctors.select');

        Route::get('/my-doctor', [PatientDoctorController::class, 'myDoctor'])
            ->name('doctor.current');

        Route::post('/doctors/{doctorProfile}/review', [PatientDoctorController::class, 'storeDoctorReview'])
            ->name('doctors.review');


        /*
        |--------------------------------------------------------------------------
        | Other Patient Pages
        |--------------------------------------------------------------------------
        */

        Route::get('/calories', [PatientCalorieController::class, 'calories'])
            ->name('calories');

        Route::get('/articles', [PatientArticleController::class, 'articles'])
            ->name('articles');


        Route::get('/articles/{slug}', [PatientArticleController::class, 'articleDetails'])->name('articles.show');


        Route::get('/messages', [PatientMessageController::class, 'messages'])
            ->name('messages');

        Route::post('/messages/send', [PatientMessageController::class, 'sendDoctorMessage'])
            ->name('messages.send');

        Route::get('/support', [PatientMessageController::class, 'support'])
            ->name('support');

        Route::post('/support/send', [PatientMessageController::class, 'sendSupportMessage'])
            ->name('support.send');

        Route::get('/notifications', [PatientNotificationController::class, 'notifications'])
            ->name('notifications');

        Route::post('/notifications/{notification}/read', [PatientNotificationController::class, 'markNotificationRead'])
            ->name('notifications.read');

        Route::post('/notifications/read-all', [PatientNotificationController::class, 'markAllNotificationsRead'])
            ->name('notifications.read-all');

        Route::get('/live/notifications', [PatientNotificationController::class, 'liveNotifications'])
            ->name('live.notifications');

        Route::get('/journey', [PatientTaskController::class, 'index'])
            ->name('journey');

        Route::post('/journey/tasks', [PatientTaskController::class, 'store'])
            ->name('journey.tasks.store');

        Route::patch('/journey/tasks/{task}/complete', [PatientTaskController::class, 'complete'])
            ->name('journey.tasks.complete');

        Route::patch('/journey/tasks/{task}/note', [PatientTaskController::class, 'updateNote'])
            ->name('journey.tasks.note');

        Route::post('/journey/tasks/{task}/attachment', [PatientTaskController::class, 'uploadAttachment'])
            ->name('journey.tasks.attachment');

        Route::delete('/journey/tasks/{task}', [PatientTaskController::class, 'destroy'])
            ->name('journey.tasks.destroy');



        /*
        |--------------------------------------------------------------------------
        | صورة نتيجة مهمة الطبيب فقط
        |--------------------------------------------------------------------------
        | نخلي نفس اسم route القديم عشان ما نخرب البليد أو الجافاسكريبت.
        | لكن وظيفته الآن: رفع صورة فقط لمهمة الطبيب.
        */
        Route::post('/journey/tasks/{task}/attachment', [PatientTaskController::class, 'uploadAttachment'])
            ->name('journey.tasks.attachment');

        Route::get('/journey/tasks/{task}/attachment/show', [PatientTaskController::class, 'showAttachment'])
            ->name('journey.tasks.attachment.show');

        Route::delete('/journey/tasks/{task}', [PatientTaskController::class, 'destroy'])
            ->name('journey.tasks.destroy');







            Route::get('/ai-chat', [AiChatController::class, 'index'])
                ->name('ai-chat.index');

            Route::get('/ai-chat/{conversation}', [AiChatController::class, 'show'])
                ->name('ai-chat.show');

            Route::post('/ai-chat/send', [AiChatController::class, 'send'])
                ->middleware('throttle:15,1')
                ->name('ai-chat.send');

            Route::delete('/ai-chat/{conversation}', [AiChatController::class, 'destroy'])
                ->name('ai-chat.destroy');



        Route::get('/calories', [PatientCalorieController::class, 'calories'])
            ->name('calories');

        Route::post('/calories/analyze', [PatientCalorieController::class, 'analyzeMeal'])
            ->middleware('throttle:15,1')
            ->name('calories.analyze');

        Route::post('/calories/confirm', [PatientCalorieController::class, 'confirmMeal'])
            ->name('calories.confirm');

        Route::delete('/calories/{meal}', [PatientCalorieController::class, 'destroyMeal'])
            ->name('calories.destroy');




                Route::post('/weight/log', [\App\Http\Controllers\Patient\PatientWeightController::class, 'store'])
                    ->name('weight.log');


                    Route::patch('/weight/{log}', [\App\Http\Controllers\Patient\PatientWeightController::class, 'update'])
                        ->name('weight.update');

                    Route::delete('/weight/{log}', [\App\Http\Controllers\Patient\PatientWeightController::class, 'destroy'])
                        ->name('weight.destroy');

                    Route::post('/weight/goal', [\App\Http\Controllers\Patient\PatientWeightController::class, 'setGoal'])
                        ->name('weight.goal');

    });




Route::prefix('doctor')
    ->name('doctor.')
    ->middleware(['auth', 'doctor'])
    ->group(function () {


        Route::get('/', [DoctorDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard', [DoctorDashboardController::class, 'dashboard'])->name('dashboard.alt');
        Route::patch('/availability', [DoctorDashboardController::class, 'toggleAvailability'])->name('availability.toggle');
        Route::post('/requests/{doctorRequest}/approve', [\App\Http\Controllers\Doctor\DoctorPatientRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/requests/{doctorRequest}/reject', [\App\Http\Controllers\Doctor\DoctorPatientRequestController::class, 'reject'])->name('requests.reject');
        Route::get('/requests', [DoctorDashboardController::class, 'page'])->defaults('page', 'requests')->name('requests');
        Route::get('/patients', [\App\Http\Controllers\Doctor\DoctorPatientListController::class, 'index'])->name('patients');
        Route::get('/patient-details', [DoctorDashboardController::class, 'page'])->defaults('page', 'patient-details')->name('patient_details');
        Route::get('/appointments', [DoctorDashboardController::class, 'page'])->defaults('page', 'appointments')->name('appointments');
        Route::get('/messages', [DoctorDashboardController::class, 'page'])->defaults('page', 'messages')->name('messages');
        Route::get('/meal-reviews', [DoctorDashboardController::class, 'page'])->defaults('page', 'meal-reviews')->name('meal_reviews');
        Route::get('/plans', [DoctorPlansController::class, 'index'])->name('plans');
        Route::get('/alerts', [DoctorAlertsController::class, 'index'])->name('alerts');
        Route::get('/articles', [DoctorDashboardController::class, 'page'])->defaults('page', 'articles')->name('articles');
        Route::get('/reports', [DoctorReportsController::class, 'index'])->name('reports');
        Route::get('/profile', [DoctorDashboardController::class, 'page'])->defaults('page', 'profile')->name('profile');
        Route::get('/settings', [DoctorDashboardController::class, 'page'])->defaults('page', 'settings')->name('settings');


        // طلبات الاستشارة (المتابعة) — DoctorPatientRequestController
            Route::get('/patient-requests', [\App\Http\Controllers\Doctor\DoctorPatientRequestController::class, 'index'])
                ->name('patient-requests.index');

            Route::post('/patient-requests/{doctorRequest}/approve', [\App\Http\Controllers\Doctor\DoctorPatientRequestController::class, 'approve'])
                ->name('patient-requests.approve');

            Route::post('/patient-requests/{doctorRequest}/reject', [\App\Http\Controllers\Doctor\DoctorPatientRequestController::class, 'reject'])
                ->name('patient-requests.reject');

            Route::post('/patient-requests/{doctorRequest}/complete', [\App\Http\Controllers\Doctor\DoctorPatientRequestController::class, 'complete'])
                ->name('patient-requests.complete');


            Route::get('/patients/{patientProfile}/details', [\App\Http\Controllers\Doctor\DoctorPatientDetailController::class, 'show'])
                ->name('patient-profile.show');



            Route::post('/patients/{patientProfile}/calorie-goal', [\App\Http\Controllers\Doctor\DoctorPatientDetailController::class, 'setCalorieGoal'])
                ->name('patients.calorie-goal');



            Route::post('/patients/{patientProfile}/weight', [\App\Http\Controllers\Doctor\DoctorPatientDetailController::class, 'logWeight'])
                 ->name('patients.log-weight');


            // نظام إشعارات الطبيب — DoctorNotificationController
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Doctor\DoctorNotificationController::class, 'index'])->name('index');
                Route::get('/unread-count', [\App\Http\Controllers\Doctor\DoctorNotificationController::class, 'unreadCount'])->name('unread-count');
                Route::get('/dropdown', [\App\Http\Controllers\Doctor\DoctorNotificationController::class, 'dropdown'])->name('dropdown');
                Route::post('/mark-all-read', [\App\Http\Controllers\Doctor\DoctorNotificationController::class, 'markAllRead'])->name('mark-all-read');
                Route::get('/{appNotification}/open', [\App\Http\Controllers\Doctor\DoctorNotificationController::class, 'open'])->name('open');
                Route::post('/{appNotification}/read', [\App\Http\Controllers\Doctor\DoctorNotificationController::class, 'markRead'])->name('mark-read');
            });
    });
