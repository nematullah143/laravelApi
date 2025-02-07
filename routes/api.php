<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/exams', [ExamController::class, 'index']);
//exam/getAllCount/
Route::get('/exam/getAllCount', [ExamController::class, 'getExamByAll']);
//package/test
Route::get('/package/test', [PackageController::class, 'getPackagesWithTotalTests']);
Route::get('/package/price', [PackageController::class, 'getPrice']);
//payment/create
Route::post('/payment/create', [PaymentController::class, 'paymentDetails']);
Route::get('payment/package/{userId}', [PaymentController::class, 'getPackageByPaymentDetails']);
// quistion/test/id test
Route::get('/question/test/{testId}', [QuestionController::class, 'getQuestionsByTestId']);
//result/get/id user
Route::get('/result/get/{id}', [ResultController::class, 'getResultByUserId']);
Route::post('/result/create', [ResultController::class, 'createResult']);
// test name usnig test id
Route::get('/result/test/{testId}', [ResultController::class, 'getResultById']);
//test//getall
Route::get('/test/getAll', [TestController::class, 'getAllTests']);
Route::get('/test/feeType/{feeType}', [TestController::class, 'getTestsByFeeType']);//
// auth user 
Route::post('/auth/signIn', [AuthController::class, 'loginUser']);
Route::post('/auth/signUp', [AuthController::class, 'signUp']);
Route::post('/auth/logOut', [AuthController::class, 'logOut']);
Route::post('/auth/logOut/email', [AuthController::class, 'logOutByEmail']);
Route::post('/auth/check-session', [AuthController::class, 'checkUserSession']);


// composer install
// php artisan key:generate
// php artisan migrate --force
// php artisan config:cache
