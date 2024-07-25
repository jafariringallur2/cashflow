<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DatatableController;
use App\Http\Controllers\API\ExpenseController;
use App\Http\Controllers\API\IncomeController;
use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('/datatable/{model}', [DatatableController::class, 'getData']);

    Route::post('/report', [ReportController::class, 'getReport']);
    Route::post('/chart-data', [ReportController::class, 'getChartData']);

    Route::post('/income', [IncomeController::class, 'store']);
    Route::get('/income', [IncomeController::class, 'index']);
    Route::put('/income/{id}', [IncomeController::class, 'update']);
    Route::delete('/income/{id}', [IncomeController::class, 'delete']);
    Route::get('/income/report', [IncomeController::class, 'report']);

    Route::post('/expense', [ExpenseController::class, 'store']);
    Route::get('/expense', [ExpenseController::class, 'index']);
    Route::put('/expense/{id}', [ExpenseController::class, 'update']);
    Route::delete('/expense/{id}', [ExpenseController::class, 'delete']);
    Route::get('/expense/report', [ExpenseController::class, 'report']);

    Route::put('/item/{id}', [ItemController::class, 'update']);
    Route::delete('/item/{id}', [ItemController::class, 'delete']);
    Route::post('/item', [ItemController::class, 'store']);
    Route::get('/item', [ItemController::class, 'index']);

});

