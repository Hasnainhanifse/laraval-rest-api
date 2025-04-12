<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
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
    public function index(Request $request): JsonResponse
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
            ->when($request->sort_by && $request->sort_direction, function ($query) use ($request) {
                $query->orderBy($request->sort_by, $request->sort_direction);
            }, function ($query) {
                $query->latest();
            })
            ->paginate($perPage);

        return response()->json($purchaseOrders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        try {
            DB::beginTransaction();

            // Calculate total amount from items
            $totalAmount = collect($validated['items'])->sum(function ($item) {
                return $item['qty'] * $item['unit_price'];
            });

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'order_no' => $validated['order_no'],
                'status' => $validated['status'],
                'total_amount' => $totalAmount,
            ]);

            $purchaseOrder->orderItems()->createMany($validated['items']);

            DB::commit();

            return response()->json($purchaseOrder->load(['supplier', 'orderItems']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::with(['supplier', 'orderItems'])->findOrFail($id);
            return response()->json($purchaseOrder);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Purchase order not found',
                'error' => 'The requested purchase order does not exist or has been deleted.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseOrderRequest $request, $id): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            
            if ($purchaseOrder->isImmutable()) {
                return response()->json(['message' => 'Purchase order cannot be modified in its current status.'], 422);
            }

            $validated = $request->validated();

            try {
                DB::beginTransaction();

                // Only update status, total_amount will be calculated from items
                $purchaseOrder->update([
                    'status' => $validated['status'] ?? $purchaseOrder->status,
                ]);

                if (isset($validated['items'])) {
                    // Get existing items
                    $existingItems = $purchaseOrder->orderItems->keyBy('id');
                    $updatedItems = collect($validated['items']);
                    
                    // Log for debugging
                    Log::info('Existing Items:', ['items' => $existingItems->pluck('id')->toArray()]);
                    Log::info('Updated Items:', ['items' => $updatedItems->pluck('id')->toArray()]);
                    
                    // Items to delete (items that exist in DB but not in the request)
                    $itemsToDelete = $existingItems->diffKeys($updatedItems->pluck('id')->filter());
                    $itemsToDelete->each->delete();
                    
                    // Update or create items
                    foreach ($updatedItems as $item) {
                        $itemId = $item['id'] ?? null;
                        
                        if ($itemId && $existingItems->has($itemId)) {
                            // Update existing item
                            $existingItems->get($itemId)->update([
                                'sku' => $item['sku'],
                                'description' => $item['description'],
                                'qty' => $item['qty'],
                                'unit_price' => $item['unit_price'],
                            ]);
                            Log::info('Updating item:', ['id' => $itemId]);
                        } else {
                            // Create new item
                            $purchaseOrder->orderItems()->create([
                                'sku' => $item['sku'],
                                'description' => $item['description'],
                                'qty' => $item['qty'],
                                'unit_price' => $item['unit_price'],
                            ]);
                            Log::info('Creating new item');
                        }
                    }
                    
                    // Recalculate total amount based on all items
                    $totalAmount = $purchaseOrder->orderItems->sum(function ($item) {
                        return $item->qty * $item->unit_price;
                    });
                    
                    // Update the total amount
                    $purchaseOrder->update(['total_amount' => $totalAmount]);
                    Log::info('Updated total amount:', ['total' => $totalAmount]);
                }

                DB::commit();

                return response()->json($purchaseOrder->load(['supplier', 'orderItems']));
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating purchase order:', ['error' => $e->getMessage()]);
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
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);
            
            if ($purchaseOrder->isImmutable()) {
                return response()->json(['message' => 'Purchase order cannot be deleted in its current status.'], 422);
            }

            $purchaseOrder->delete();

            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Purchase order not found',
                'error' => 'The requested purchase order does not exist or has been deleted.'
            ], 404);
        }
    }

    /**
     * Send the purchase order for approval
     *
     * @param int $id
     * @return JsonResponse
     */
    public function sendApproval($id): JsonResponse
    {
        try {
            $purchaseOrder = PurchaseOrder::with(['supplier', 'orderItems'])->findOrFail($id);
            
            if ($purchaseOrder->status !== PurchaseOrder::STATUS_PENDING) {
                return response()->json(['message' => 'Only pending purchase orders can be sent for approval.'], 422);
            }

            // Log the approval action
            $logData = [
                'purchase_order_id' => $purchaseOrder->id,
                'order_no' => $purchaseOrder->order_no,
                'supplier_id' => $purchaseOrder->supplier_id,
                'supplier_name' => $purchaseOrder->supplier->name,
                'total_amount' => $purchaseOrder->total_amount,
                'items_count' => $purchaseOrder->orderItems->count(),
                'status' => $purchaseOrder->status,
                'action' => 'sent_for_approval',
                'user_id' => auth()->id(),
            ];
            
            $this->approvalLogService->logApproval($logData);

            // Simulate sending email by logging
            Log::info('Sending approval email for Purchase Order', [
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_email' => $purchaseOrder->supplier->email,
                'total_amount' => $purchaseOrder->total_amount,
                'items_count' => $purchaseOrder->orderItems->count(),
            ]);

            return response()->json(['message' => 'Approval email has been sent.']);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Purchase order not found',
                'error' => 'The requested purchase order does not exist or has been deleted.'
            ], 404);
        }
    }
}
