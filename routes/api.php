<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tokenvalidation', [AuthController::class, 'tokenValidation']);
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::resource('students', StudentController::class);
    
    Route::resource('users', UserController::class);
    Route::get('/users/image/{path}', [UserController::class, 'getImage']);
    Route::post('/users/image', [UserController::class, 'uploadImage']);
    Route::delete('/users/image/{id}', [UserController::class, 'deleteImage']);
    Route::put('/users/email/{id}', [UserController::class, 'updateEmail']);
    
    Route::get('/students/export/excel', [ExcelController::class, 'export']);
    Route::post('/students/import/excel', [ExcelController::class, 'import']);
    Route::get('/large/export/csv', [ExcelController::class, 'largeExport']);
    
    Route::post('/sendemail', [EmailController::class, 'sendEmail']);
});
