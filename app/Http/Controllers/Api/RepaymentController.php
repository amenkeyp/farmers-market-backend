<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repayment\StoreRepaymentRequest;
use App\Http\Resources\RepaymentResource;
use App\Models\Repayment;
use App\Services\RepaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class RepaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private RepaymentService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $q = Repayment::query()->with(['farmer', 'operator', 'debts']);
        if ($request->filled('farmer_id')) {
            $q->where('farmer_id', $request->query('farmer_id'));
        }
        return $this->success(
            RepaymentResource::collection($q->latest('paid_at')->paginate((int) $request->query('per_page', 20)))->response()->getData(true),
            'Repayments list.'
        );
    }

    public function store(StoreRepaymentRequest $request): JsonResponse
    {
        try {
            $r = $this->service->repay($request->validated(), $request->user());
            return $this->success(new RepaymentResource($r), 'Repayment recorded.', 201);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);
            return $this->error('Repayment failed.', 500);
        }
    }

    public function show(Repayment $repayment): JsonResponse
    {
        $repayment->load(['farmer', 'operator', 'debts']);
        return $this->success(new RepaymentResource($repayment), 'Repayment detail.');
    }
}
