<?php

use App\Http\Controllers\api\AdminController;
use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\api\DefaultCategoryController;
use App\Http\Controllers\apiTAES\TransactionControllerTAES;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\VcardController;
use App\Http\Controllers\api\TransactionController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\apiTAES\VcardControllerTAES;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('auth/login', [AuthController::class, 'login']);
Route::post('vcards', [VcardController::class, 'store']);
Route::patch('admin/updatepasswordbyToken', [AdminController::class, 'updateAdminPasswordByToken']);
Route::middleware('auth:api')->group(
    function () {
        Route::get('users/me', [UserController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('admins', [AdminController::class, 'store'])
            ->middleware('can:create,App\Models\Admin');
        Route::get('admins', [AdminController::class, 'index']);
        Route::put('admins/{admin}', [AdminController::class, 'update'])
            ->middleware('can:update,admin');
        Route::delete('admins/{admin}', [AdminController::class, 'destroy'])
            ->middleware('can:delete,admin');
        Route::get('admins/statistics', [AdminController::class, 'getStats']);
        Route::get('transactions/bydate', [TransactionController::class, 'getTransactionsByDateFilter']);
        Route::get('vcards/{vcard}/transactions/filtered', [VcardController::class, 'getTransactionsByCard'])
            ->middleware('can:view,vcard');
        Route::get('/vcards/{vcard}/transactions/last3', [VcardController::class, 'getLast3Transactions'])
            ->middleware('can:view,vcard');
        Route::get('vcards', [VcardController::class, 'index'])
            ->middleware('can:viewAny,App\Models\Vcard');
        Route::put('vcards/{vcard}', [VcardController::class, 'update'])
            ->middleware('can:update,vcard');
        Route::get('vcards/{vcard}', [VcardController::class, 'show'])
            ->middleware('can:view,vcard');
        Route::delete('vcards/{vcard}', [VcardController::class, 'destroy'])
            ->middleware('can:delete,vcard');
        Route::get('vcards/{vcard}/transactions/categories', [VcardController::class, 'getTransactionsCategories'])
            ->middleware('can:view,vcard');
        Route::get('vcards/{vcard}/statistics', [VcardController::class, 'getStatistics'])
            ->middleware('can:view,vcard');
        Route::get('vcards/{vcard}/categories', [VcardController::class, 'getCategories'])
            ->middleware('can:view,vcard');
        Route::patch('vcards/{vcard}/changestatus', [VcardController::class, 'changeVCardStatus'])
            ->middleware('can:update,vcard');
        Route::patch('vcards/{vcard}/maxdebit', [VcardController::class, 'updateVCardMaxDebit'])
            ->middleware('can:update,vcard');
        Route::patch('vcards/{vcard}/piggybank/add', [VcardController::class, 'addMoneyToPiggyBank'])
            ->middleware('can:update,vcard');
        Route::patch('vcards/{vcard}/piggybank/remove', [VcardController::class, 'removeMoneyFromPiggyBank'])
            ->middleware('can:update,vcard');
        Route::patch('/vcards/{vcard}/piggybank/changestatus', [VcardController::class, 'wantRoundToPiggyBank'])
            ->middleware('can:update,vcard');
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('defaultcategories', DefaultCategoryController::class);
    }
);

// ROTAS DE TAES
Route::prefix('taes')->group(function () {
    Route::post('/login', [VcardControllerTAES::class, 'authenticate']);
    Route::get('/transactions/{vcard}/last3', [TransactionControllerTAES::class, 'getTransactionsByCardLast3']);
    Route::get('/transactions/{vcard}', [TransactionControllerTAES::class, 'getTransactionsByCard']);
    Route::get('/vcards/{vcard}/notifications', [VcardControllerTAES::class, 'getNotifications']);
    Route::patch('/vcards/{vcard}/notifications/changestatus', [VcardControllerTAES::class, 'changeNotificationsStatus']);
    Route::patch('/vcards/{vcard}/notifications/{id}', [VcardControllerTAES::class, 'updateNotifications']);
    Route::patch('/vcards/{vcard}/piggybank/add', [VcardControllerTAES::class, 'addMoneyToPiggyBank']);
    Route::patch('/vcards/{vcard}/piggybank/remove', [VcardControllerTAES::class, 'removeMoneyFromPiggyBank']);
    Route::patch('/vcards/{vcard}/piggybank/changestatus', [VcardControllerTAES::class, 'wantRoundToPiggyBank']);
    Route::apiResource('vcards', VcardControllerTAES::class);
    Route::apiResource('transactions', TransactionControllerTAES::class);
});
