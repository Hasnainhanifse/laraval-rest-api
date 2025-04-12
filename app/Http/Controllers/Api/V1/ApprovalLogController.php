<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApprovalLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalLogController extends Controller
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
     * Display a listing of approval logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $logs = $this->approvalLogService->getLogs();
        
        // Apply filters if provided
        if ($request->has('purchase_order_id')) {
            $logs = array_filter($logs, function($log) use ($request) {
                return $log['purchase_order_id'] == $request->purchase_order_id;
            });
        }
        
        if ($request->has('supplier_id')) {
            $logs = array_filter($logs, function($log) use ($request) {
                return $log['supplier_id'] == $request->supplier_id;
            });
        }
        
        if ($request->has('user_id')) {
            $logs = array_filter($logs, function($log) use ($request) {
                return $log['user_id'] == $request->user_id;
            });
        }
        
        // Sort logs by timestamp (newest first)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Paginate the results
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedLogs = array_slice($logs, $offset, $perPage);
        
        return response()->json([
            'data' => $paginatedLogs,
            'meta' => [
                'total' => count($logs),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil(count($logs) / $perPage),
            ]
        ]);
    }
} 