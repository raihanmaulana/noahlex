<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\FolderTemplateController;
use App\Http\Controllers\ForgotPasswordController;

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
    });

    // Projects
    Route::prefix('projects')->controller(ProjectController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/detail', 'detail');
        Route::put('/', 'update');
        Route::delete('/', 'destroy');
    });

    // Project Type
    Route::prefix('project-types')->group(function () {
        Route::get('/', [ProjectTypeController::class, 'index']);
        Route::post('/', [ProjectTypeController::class, 'store']);
        Route::get('/detail', [ProjectTypeController::class, 'detail']);
        Route::put('/', [ProjectTypeController::class, 'update']);
        Route::delete('/', [ProjectTypeController::class, 'destroy']);
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
});
