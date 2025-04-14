<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalLogResource;
use App\Services\ApprovalLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ApprovalLogController extends Controller
{
    protected $approvalLogService;
    public function __construct(ApprovalLogService $approvalLogService)
    {
        $this->approvalLogService = $approvalLogService;
    }
    public function index(Request $request)
    {
        $logs = $this->approvalLogService->getLogs();
        if ($request->has('purchase_order_id')) {
            $logs = array_filter($logs, function($log) use ($request) {
                return $log['purchase_order_id'] == $request->purchase_order_id;
            });
        }
        if ($request->has('user_id')) {
            $logs = array_filter($logs, function($log) use ($request) {
                return $log['user_id'] == $request->user_id;
            });
        }
        if ($request->has('action')) {
            $logs = array_filter($logs, function($log) use ($request) {
                return $log['action'] == $request->action;
            });
        }
        if ($request->has('status')) {
            $logs = array_filter($logs, function($log) use ($request) {
                return $log['status'] == $request->status;
            });
        }
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedLogs = array_slice($logs, $offset, $perPage);
        $collection = collect($paginatedLogs);
        return response()->json([
            'data' => $collection,
            'meta' => [
                'total' => count($logs),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil(count($logs) / $perPage),
            ]
        ]);
    }
} 