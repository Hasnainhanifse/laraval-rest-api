<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Http\Resources\OrderItemResource;
use App\Models\OrderItem;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class OrderItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $orderItems = OrderItem::query()
            ->when($request->purchase_order_id, function ($query, $purchaseOrderId) {
                $query->where('purchase_order_id', $purchaseOrderId);
            })
            ->when($request->sort_by && $request->sort_direction, function ($query) use ($request) {
                $query->orderBy($request->sort_by, $request->sort_direction);
            }, function ($query) {
                $query->latest();
            })
            ->paginate($perPage);
        return new OrderItemResource($orderItems);
    }
    public function store(StoreOrderItemRequest $request): JsonResponse
    {
        $orderItem = OrderItem::create($request->validated());
        return response()->json($orderItem);
    }
    public function show($purchase_order_id, $order_item_id): JsonResponse
    {
        try {
            $purchase_order = PurchaseOrder::findOrFail($purchase_order_id);
            $order_item = OrderItem::findOrFail($order_item_id);

            if ($order_item->purchase_order_id !== $purchase_order->id) {
                return response()->json(['message' => 'Order item not found in this purchase order'], 404);
            }

            return response()->json($order_item);
        } catch (ModelNotFoundException $e) {
            if (str_contains($e->getMessage(), 'PurchaseOrder')) {
                return response()->json(['message' => 'Purchase order not found'], 404);
            }
            return response()->json(['message' => 'Order item not found'], 404);
        }
    }
    public function update(UpdateOrderItemRequest $request, OrderItem $order_item): JsonResponse
    {
        $order_item->update($request->validated());
        return response()->json($order_item);
    }
    public function destroy(OrderItem $order_item): JsonResponse
    {
        $order_item->delete();
        return response()->json(null, 204);
    }
}
