<?php

use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/pipeline', [DashboardController::class, 'pipeline']);

// Maps Test
Route::get('/maps-test', function () {
    return view('maps-test');
});

// Billing Test
Route::get('/stripe-test', function () {
    return view('stripe-test');
});

Route::controller(BillingController::class)->group(function () {
    Route::post('/create-checkout-session',  'createCheckoutSession');
    Route::get('/billing/success',  'success');
    Route::get('/billing/cancel',  'cancel');
    Route::post('/stripe/webhook',  'webhook');
});
