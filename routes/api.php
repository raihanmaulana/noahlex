<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\HelpRequestController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\FolderTemplateController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ProjectAssignmentController;

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

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Roles
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/detail', 'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
        Route::post('/update-permission', 'updatePermission');
    });

    //Users
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/detail', 'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
        Route::get('/template/download', [UserController::class, 'downloadTemplate']);
        Route::post('/preview-import', [UserController::class, 'previewImport']);
        Route::post('/import', [UserController::class, 'importUsers']);
    });

    //Account
    Route::prefix('account')->controller(AccountController::class)->group(function () {
        Route::post('/change-password',  'changePassword');
        Route::get('/notification-settings',  'getNotificationSettings');
        Route::put('/notification-settings',  'updateNotificationSettings');
        Route::post('/update',  'updateProfile');
        Route::post('/generate-2fa',  'generate2FASecret');
        Route::post('/verify-2fa',  'verify2FA');
    });

    // Projects
    Route::prefix('projects')->controller(ProjectController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/detail', 'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
        Route::post('/bulk-assign', 'bulkAssignUsersToProjects');
    });

    // Project Assignment
    Route::prefix('assignment-types')->controller(ProjectAssignmentController::class)->group(function () {
        Route::get('/',  'index');
    });

    // Project Type
    Route::prefix('project-types')->controller(ProjectTypeController::class)->group(function () {
        Route::get('/',  'index');
        Route::post('/',  'store');
        Route::get('/detail',  'detail');
        Route::put('/',  'update');
        Route::delete('/',  'destroy');
    });

    // Project Status
    Route::prefix('project-statuses')->group(function () {
        Route::get('/', [ProjectStatusController::class, 'index']);
        Route::post('/', [ProjectStatusController::class, 'store']);
        Route::get('/detail', [ProjectStatusController::class, 'detail']);
        Route::put('/', [ProjectStatusController::class, 'update']);
        Route::delete('/', [ProjectStatusController::class, 'destroy']);
    });

    // Folder Template
    Route::prefix('folder-templates')->group(function () {
        Route::get('/', [FolderTemplateController::class, 'index']);
        Route::post('/', [FolderTemplateController::class, 'store']);
        Route::post('/single', [FolderTemplateController::class, 'storeSingle']);
        Route::put('/single', [FolderTemplateController::class, 'updateSingle']);
        Route::get('/detail', [FolderTemplateController::class, 'detail']);
        Route::put('/', [FolderTemplateController::class, 'update']);
        Route::delete('/', [FolderTemplateController::class, 'destroy']);
    });

    // Notification
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::post('/mark-read', [NotificationController::class, 'markAsRead']);
    });

    //Help Request
    Route::prefix('help-center')->controller(HelpRequestController::class)->group(function () {
        Route::post('/', 'store');
        Route::get('/',  'index');
        Route::put('/update-status',  'updateStatus');
    });
});
