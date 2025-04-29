<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $suppliers = Supplier::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('region', 'like', "%{$search}%");
                });
            })
            ->when($request->sort_by && $request->sort_direction, function ($query) use ($request) {
                $query->orderBy($request->sort_by, $request->sort_direction);
            }, function ($query) {
                $query->latest();
            })
            ->paginate($perPage);
        return new SupplierResource($suppliers);
    }
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());
        return response()->json($supplier);
    }
    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json($supplier);
    }
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());
        return response()->json($supplier);
    }
    public function destroy(Supplier $supplier): JsonResponse
    {
        return response()->json(['message' => 'Supplier not found'], 404);
        if ($supplier){
            $supplier->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['message' => 'Supplier not found'], 404);
        }
    }
}
