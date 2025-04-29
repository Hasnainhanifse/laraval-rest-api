<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ApprovalLogService;

class PurchaseOrderController extends Controller
{
    /**
     * @var ApprovalLogService
     */
    protected $approvalLogService;

    /**
     * Constructor
     *
     * @param ApprovalLogService $approvalLogService
     */
    public function __construct(ApprovalLogService $approvalLogService)
    {
        $this->approvalLogService = $approvalLogService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'orderItems'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->supplier_id, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->sort_by && $request->sort_direction, function ($query) use ($request) {
                $query->orderBy($request->sort_by, $request->sort_direction);
            }, function ($query) {
                $query->latest();
            })
            ->paginate($perPage);

        $purchaseOrders->getCollection()->transform(function ($purchaseOrder) {
            $purchaseOrder->total_amount = $purchaseOrder->calculateTotalAmount();
            return $purchaseOrder;
        });

        return new PurchaseOrderResource($purchaseOrders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Create the purchase order
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'order_no' => $validated['order_no'],
                'status' => $validated['status'],
                'total_amount' => $validated['total_amount'] ?? 0,
            ]);

            // Create order items
            if (isset($validated['items']) && is_array($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $purchaseOrder->orderItems()->create([
                        'sku' => $item['sku'],
                        'description' => $item['description'],
                        'qty' => $item['qty'],
                        'unit_price' => $item['unit_price'],
                    ]);
                }

                // Only recalculate if total_amount wasn't provided
                if (!isset($validated['total_amount'])) {
                    $purchaseOrder->total_amount = $purchaseOrder->calculateTotalAmount();
                    $purchaseOrder->save();
                }
            }

            DB::commit();

            // Load relationships for response
            $purchaseOrder->load(['supplier', 'orderItems']);

            return response()->json($purchaseOrder);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating purchase order: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Failed to create purchase order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchase_order): JsonResponse
    {
        try {
            $purchase_order->load(['supplier', 'orderItems']);

            $responseData = $purchase_order->toArray();
            $responseData['total_amount'] = $purchase_order->calculateTotalAmount();

            return response()->json($responseData);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchase_order): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            // Check if purchase order can be modified
            if ($purchase_order->isImmutable()) {
                return response()->json([
                    'message' => 'Purchase order cannot be modified in its current status'
                ], 422);
            }

            // Update purchase order
            $purchase_order->update([
                'supplier_id' => $validated['supplier_id'] ?? $purchase_order->supplier_id,
                'status' => $validated['status'] ?? $purchase_order->status,
                'total_amount' => $validated['total_amount'] ?? $purchase_order->total_amount,
            ]);

            // Update or create order items
            if (isset($validated['items']) && is_array($validated['items'])) {
                // Delete existing items if replace flag is set
                if (isset($validated['replace_items']) && $validated['replace_items']) {
                    $purchase_order->orderItems()->delete();
                }

                foreach ($validated['items'] as $item) {
                    if (isset($item['id'])) {
                        // Update existing item
                        $purchase_order->orderItems()->where('id', $item['id'])->update([
                            'sku' => $item['sku'],
                            'description' => $item['description'],
                            'qty' => $item['qty'],
                            'unit_price' => $item['unit_price'],
                        ]);
                    } else {
                        // Create new item
                        $purchase_order->orderItems()->create([
                            'sku' => $item['sku'],
                            'description' => $item['description'],
                            'qty' => $item['qty'],
                            'unit_price' => $item['unit_price'],
                        ]);
                    }
                }

                // Only recalculate if total_amount wasn't provided
                if (!isset($validated['total_amount'])) {
                    $purchase_order->total_amount = $purchase_order->calculateTotalAmount();
                    $purchase_order->save();
                }
            }

            DB::commit();

            // Load relationships for response
            $purchase_order->load(['supplier', 'orderItems']);

            return response()->json($purchase_order);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Purchase order not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating purchase order: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to update purchase order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchase_order): JsonResponse
    {
        try {
            $purchase_order->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting purchase order: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to delete purchase order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send purchase order for approval
     */
    public function sendApproval(PurchaseOrder $purchase_order): JsonResponse
    {
        try {
            if ($purchase_order->orderItems->isEmpty()) {
                return response()->json(['message' => 'Cannot send an empty purchase order for approval.'], 422);
            }

            try {
                DB::beginTransaction();

                // Update status to pending
                $purchase_order->status = 'pending';
                $purchase_order->save();

                // Log the approval action
                $this->approvalLogService->logApproval([
                    'purchase_order_id' => $purchase_order->id,
                    'user_id' => auth()->id,
                    'action' => 'send_approval',
                    'status' => 'pending',
                    'notes' => 'Purchase order sent for approval',
                ]);

                DB::commit();

                return response()->json($purchase_order->load(['supplier', 'orderItems']));
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
}
