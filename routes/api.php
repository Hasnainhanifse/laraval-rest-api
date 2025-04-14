<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\OrderItemController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ApprovalLogController;

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

// Public routes
Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('me', [UserController::class, 'me']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::apiResource('users', UserController::class);

    // Supplier routes
    Route::apiResource('suppliers', SupplierController::class);

    // Purchase Order routes
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchase_order}/send-approval', [PurchaseOrderController::class, 'sendApproval']);

    // Order Item routes (nested under purchase orders)
    Route::get('purchase-orders/{purchase_order}/items', [OrderItemController::class, 'index']);
    Route::post('purchase-orders/{purchase_order}/items', [OrderItemController::class, 'store']);
    Route::get('purchase-orders/{purchase_order}/items/{order_item}', [OrderItemController::class, 'show']);
    Route::put('purchase-orders/{purchase_order}/items/{order_item}', [OrderItemController::class, 'update']);
    Route::delete('purchase-orders/{purchase_order}/items/{order_item}', [OrderItemController::class, 'destroy']);

    // Approval Logs route
    Route::get('approval-logs', [ApprovalLogController::class, 'index']);
}); 