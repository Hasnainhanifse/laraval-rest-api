<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\OrderItemController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ApprovalLogController;
use App\Http\Controllers\Api\V1\EmailController;

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

    Route::post('email/send', [EmailController::class, 'send']);
});
// Route::get('email/send', function () {
//     $emailBody = "<html><body style='font: 14px Georgia, serif; margin: 0; padding: 0;'><label style='display: block;'>Dear Team,</label><br /><br />Welcome to Panda Cube<br /><br /><b>Panda Cube</b><br /><hr style='border: 1px solid #FF9900;'/>System generated message from Cube. Please DO NOT reply.<br /><br /></body></html>";
//     $emailSubject = 'test';
//     return new App\Mail\GenericEmail($emailBody, $emailSubject, '');
// });
