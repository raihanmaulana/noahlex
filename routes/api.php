<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpRequestController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\FolderTemplateController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ProjectAssignmentController;
use App\Http\Controllers\ProjectDocumentAccessController;
use App\Http\Controllers\ProjectDocumentCommentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::post('auth/social/{provider}', [AuthController::class, 'socialLogin'])
    ->whereIn('provider', ['google', 'apple']);


Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);


Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);


    //Dashboard
    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'index');
    });

    // Roles
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}',  'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
        Route::post('/update-permission', 'updatePermission');
    });

    //Users
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
        Route::get('/template/download',  'downloadTemplate');
        Route::post('/preview-import',  'previewImport');
        Route::post('/import',  'importUsers');
    });

    // Account
    Route::prefix('account')->controller(AccountController::class)->group(function () {
        Route::post('/change-password',  'changePassword');
        Route::get('/notification-settings',  'getNotificationSettings');
        Route::put('/notification-settings',  'updateNotificationSettings');
        Route::post('/update',  'updateProfile');
        Route::post('/generate-2fa',  'generate2FASecret');
        Route::post('/verify-2fa',  'verify2FA');
        Route::get('/me',  'show');
    });

    // Projects
    Route::prefix('projects')->controller(ProjectController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
        Route::post('/bulk-assign', 'bulkAssignUsersToProjects');
    });

    // Project Document
    Route::prefix('project-documents')->controller(ProjectDocumentController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/store', 'store');
        Route::post('/update', 'update');
        Route::get('/{id}', 'detail');
        Route::post('/{id}', 'destroy');
        Route::post('/toggle-expiry', 'toggleExpiryReminder');
        Route::post('/approve', 'approveDocument');
        Route::post('/reject', 'rejectDocument');
        Route::get('/download/{id}', 'download');
        Route::get('/preview/{id}', 'preview');
        Route::post('/{groupId}/versions', 'storeVersion');
        Route::get('/{groupId}/versions',  'listVersions');
        Route::get('/{documentId}/activity',  'activityLog');
        Route::post('/{id}/restore',  'restore');
    });

    //Project Document Comment
    Route::prefix('project-document-comments')->controller(ProjectDocumentCommentController::class)->group(function () {
        Route::get('/{document_id}', 'index');
        Route::post('/', 'store');
        Route::delete('/', 'destroy');
    });

    //Project Document Access
    Route::prefix('project-document-access')->controller(ProjectDocumentAccessController::class)->group(function () {
        Route::post('/invite', 'invite');
        Route::get('/{document_id}', 'listAccess');
        Route::delete('/revoke', 'revokeAccess');
    });

    // Project Assignment
    Route::prefix('assignment-types')->controller(ProjectAssignmentController::class)->group(function () {
        Route::get('/',  'index');
    });

    // Project Type
    Route::prefix('project-types')->controller(ProjectTypeController::class)->group(function () {
        Route::get('/',  'index');
        Route::post('/',  'store');
        Route::get('/{id}',  'detail');
        Route::put('/',  'update');
        Route::delete('/',  'destroy');
    });

    // Project Status
    Route::prefix('project-statuses')->controller(ProjectStatusController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
    });

    // Folder Template
    Route::prefix('folder-templates')->controller(FolderTemplateController::class)->group(function () {
        Route::get('/',  'index');
        Route::post('/',  'store');
        Route::post('/single',  'storeSingle');
        Route::put('/single',  'updateSingle');
        Route::get('/{id}',  'detail');
        Route::put('/',  'update');
        Route::delete('/',  'destroy');
    });

    // Notification
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'getNotifications');
        Route::post('/mark-read', 'markAsRead');
    });

    //Help Request
    Route::prefix('help-center')->controller(HelpRequestController::class)->group(function () {
        Route::post('/', 'store');
        Route::get('/',  'index');
        Route::put('/update-status',  'updateStatus');
    });

    Route::prefix('billing')->controller(BillingController::class)->group(function () {
        Route::post('/create-checkout-session',  'createCheckoutSession');
        Route::get('/billing/success',  'success');
        Route::get('/billing/cancel',  'cancel');
    });

    Route::post('/stripe/webhook', [BillingController::class, 'webhook']);
});
