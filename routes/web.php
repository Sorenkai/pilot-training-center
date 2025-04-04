<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EndorsementController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\GlobalSettingController;
use App\Http\Controllers\PilotTrainingActivityController;
use App\Http\Controllers\PilotTrainingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// --------------------------------------------------------------------------
// Main page
// --------------------------------------------------------------------------
Route::get('/', [FrontPageController::class, 'index'])->name('front');

// --------------------------------------------------------------------------
// VATSIM Authentication
// --------------------------------------------------------------------------
Route::get('/login', [LoginController::class, 'login'])->middleware('guest')->name('login');
Route::get('/validate', [LoginController::class, 'validateLogin'])->middleware('guest');
Route::get('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// --------------------------------------------------------------------------
// Sites behind authentication
// --------------------------------------------------------------------------
Route::middleware(['auth', 'activity', 'suspended'])->group(function () {
    // Sidebar Navigation
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/content', [DashboardController::class, 'content'])->name('content');
    Route::get('/pilot/trainings', [PilotTrainingController::class, 'index'])->name('pilot.requests');
    Route::get('/pilot/trainings/history', [PilotTrainingController::class, 'history'])->name('pilot.requests.history');
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/other', [UserController::class, 'indexOther'])->name('users.other');
    Route::get('/roster', [RosterController::class, 'index'])->name('roster');

    // Endorsements
    Route::controller(EndorsementController::class)->group(function () {
        Route::get('/endorsements/create', 'create')->name('endorsements.create');
        Route::get('/endorsements/create/{id}', 'create')->name('endorsements.create.id');
        Route::post('/endorsements/store', 'store')->name('endorsements.store');
        Route::get('/endorsements/{id}/delete', 'destroy')->name('endorsements.delete');
    });

    // Users
    Route::controller(UserController::class)->group(function () {
        Route::get('/user/{user}', 'show')->name('user.show');
        Route::patch('/user/{user}', 'update')->name('user.update');
        Route::get('/user/{user}/reports', 'reports')->name('user.reports');
        Route::get('/settings', 'settings')->name('user.settings');
        Route::post('/settings', 'settings_update')->name('user.settings.store');
        Route::get('/settings/extendworkmail', 'extendWorkmail')->name('user.settings.extendworkmail');

        // Internal user searc
        Route::get('/user/search/find', 'search')->name('user.search');
        Route::get('/user/search/vatsimhours', 'fetchVatsimHours')->name('user.vatsimhours');
    });

    // Reports
    Route::controller(ReportController::class)->group(function () {
        Route::get('/reports/trainings', 'trainings')->name('reports.trainings');
        Route::get('/reports/training/{id}', 'trainings')->name('reports.training.area');
        Route::get('/reports/activities', 'activities')->name('reports.activities');
        Route::get('/reports/activities/{id}', 'activities')->name('reports.activities.area');
        Route::get('/reports/instructors', 'instructors')->name('reports.instructors');
        Route::get('/reports/access', 'access')->name('reports.access');
        Route::get('/reports/feedback', 'feedback')->name('reports.feedback');
    });

    // Admin
    Route::get('/admin/settings', [GlobalSettingController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings', [GlobalSettingController::class, 'edit'])->name('admin.settings.store');
    Route::get('/admin/log', [ActivityLogController::class, 'index'])->name('admin.logs');

    // Pilot routes

    Route::controller(PilotTrainingController::class)->group(function () {
        Route::get('/pilot/training/apply', 'apply')->name('pilot.training.apply');
        Route::get('/pilot/training/create', 'create')->name('pilot.training.create');
        Route::get('/pilot/training/create/{id}', 'create')->name('pilot.training.create.id');
        Route::post('/pilot/training/store', 'store')->name('pilot.training.store');
        Route::get('/pilot/training/{training}', 'show')->name('pilot.training.show');
        Route::get('/pilot/training/{training}/action/close', 'close')->name('pilot.training.action.close');
        Route::get('/pilot/training/{training}/action/pretraining', 'togglePreTrainingCompleted')->name('pilot.training.action.pretraining');
        Route::patch('/pilot/training/{training}', 'updateDetails')->name('pilot.training.update.details');
        Route::get('/pilot/training/edit/{training}', 'edit')->name('pilot.training.edit');
        Route::patch('/pilot/training/edit/{training}', 'updateRequest')->name('pilot.training.update.request');
    });

    Route::post('/pilot/training/activity/comment', [PilotTrainingActivityController::class, 'storeComment'])->name('pilot.training.activity.comment');

    Route::controller(PilotTrainingReportController::class)->group(function () {
        Route::get('/pilot//training/report/{report}', 'edit')->name('pilot.training.report.edit');
        Route::get('/pilot/training/{training}/report/create', 'create')->name('pilot.training.report.create');
        Route::post('/pilot/training/{training}/report', 'store')->name('pilot.training.report.store');
        Route::patch('/pilot/training/report/{report}', 'update')->name('pilot.training.report.update');
        Route::get('/pilot/training/report/{report}/delete', 'destroy')->name('pilot.training.report.delete');
    });

    Route::controller(PilotTrainingObjectAttachmentController::class)->group(function () {
        Route::get('/pilot/training/attachment/{attachment}', 'show')->name('pilot.training.object.attachment.show');
        Route::post('/pilot/training/{trainingObjectType}/{trainingObject}/attachment', 'store')->name('pilot.training.object.attachment.store');
        Route::delete('/pilot/training/attachment/{attachment}', 'destroy')->name('pilot.training.object.attachment.delete');
    });

    Route::controller(ExamController::class)->group(function () {
        Route::get('/exam/create', 'createTheory')->name('exam.create');
        Route::get('/exam/create/{id}', 'createTheory')->name('exam.create.id');
        Route::post('exam/store', 'storeTheory')->name('exam.store');
        Route::get('/exam/practical/create', 'createPractical')->name('exam.practical.create');
        Route::get('/exam/practical/create/{id}', 'createPractical')->name('exam.practical.create.id');
        Route::post('/exam/practical/store', 'storePractical')->name('exam.practical.store');
    });

    Route::controller(ExamObjectAttachmentController::class)->group(function () {
        Route::get('/exam/attachment/{attachment}', 'show')->name('exam.object.attachment.show');
        Route::post('/exam/{trainingObjectType}/{trainingObject}/attachment', 'store')->name('exam.object.attachment.store');
        Route::delete('/exam/attachment/{attachment}', 'destroy')->name('exam.object.attachment.delete');
    });

    Route::controller(FileController::class)->group(function () {
        Route::get('/files/{file}', 'get')->name('file.get');
        Route::post('/files', 'store')->name('file.store');
        Route::delete('/files/{file}', 'destroy')->name('file.delete');
    });

    Route::controller(TaskController::class)->group(function () {
        Route::get('/tasks', 'index')->name('tasks');
        Route::get('/tasks/{activeFilter}', 'index')->name('tasks.filtered');
        Route::get('/tasks/complete/{id}', 'complete')->name('task.complete');
        Route::get('/tasks/decline/{id}', 'decline')->name('task.decline');
        Route::post('/task/store', 'store')->name('task.store');
    });
});
