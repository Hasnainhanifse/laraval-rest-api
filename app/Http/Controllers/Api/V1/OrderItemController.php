<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($purchaseOrderId, Request $request): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
            $perPage = $request->input('per_page', 15);
            
            return response()->json(
                $purchaseOrder->orderItems()
                    ->when($request->sort_by && $request->sort_direction, function ($query) use ($request) {
                        $query->orderBy($request->sort_by, $request->sort_direction);
                    }, function ($query) {
                        $query->latest();
                    })
                    ->paginate($perPage)
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Purchase order not found',
                'error' => 'The requested purchase order does not exist or has been deleted.'
            ], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $purchaseOrderId): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
            
            if ($purchaseOrder->isImmutable()) {
                return response()->json(['message' => 'Cannot add items to an immutable purchase order.'], 422);
            }

            $validated = $request->validate([
                'sku' => ['required', 'string'],
                'description' => ['required', 'string'],
                'qty' => ['required', 'integer', 'min:1'],
                'unit_price' => ['required', 'numeric', 'min:0'],
            ]);

            try {
                DB::beginTransaction();

                $orderItem = $purchaseOrder->orderItems()->create($validated);

                // Recalculate total amount
                $purchaseOrder->total_amount = $purchaseOrder->calculateTotalAmount();
                $purchaseOrder->save();

                DB::commit();

                return response()->json($orderItem, 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Purchase order not found',
                'error' => 'The requested purchase order does not exist or has been deleted.'
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($purchaseOrderId, $orderItemId): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
            $orderItem = OrderItem::findOrFail($orderItemId);
            
            if ($orderItem->purchase_order_id !== $purchaseOrder->id) {
                return response()->json(['message' => 'Order item not found.'], 404);
            }

            return response()->json($orderItem);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'The requested resource does not exist or has been deleted.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $purchaseOrderId, $orderItemId): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
            $orderItem = OrderItem::findOrFail($orderItemId);
            
            if ($orderItem->purchase_order_id !== $purchaseOrder->id) {
                return response()->json(['message' => 'Order item not found.'], 404);
            }

            if ($purchaseOrder->isImmutable()) {
                return response()->json(['message' => 'Cannot modify items in an immutable purchase order.'], 422);
            }

            $validated = $request->validate([
                'sku' => ['sometimes', 'string'],
                'description' => ['sometimes', 'string'],
                'qty' => ['sometimes', 'integer', 'min:1'],
                'unit_price' => ['sometimes', 'numeric', 'min:0'],
            ]);

            try {
                DB::beginTransaction();

                $orderItem->update($validated);

                // Recalculate total amount
                $purchaseOrder->total_amount = $purchaseOrder->calculateTotalAmount();
                $purchaseOrder->save();

                DB::commit();

                return response()->json($orderItem);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'The requested resource does not exist or has been deleted.'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($purchaseOrderId, $orderItemId): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
            $orderItem = OrderItem::findOrFail($orderItemId);
            
            if ($orderItem->purchase_order_id !== $purchaseOrder->id) {
                return response()->json(['message' => 'Order item not found.'], 404);
            }

            if ($purchaseOrder->isImmutable()) {
                return response()->json(['message' => 'Cannot delete items from an immutable purchase order.'], 422);
            }

            try {
                DB::beginTransaction();

                $orderItem->delete();

                // Recalculate total amount
                $purchaseOrder->total_amount = $purchaseOrder->calculateTotalAmount();
                $purchaseOrder->save();

                DB::commit();

                return response()->json(null, 204);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'The requested resource does not exist or has been deleted.'
            ], 404);
        }
    }
}
