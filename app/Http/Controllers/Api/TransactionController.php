<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(private TransactionService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $q = Transaction::query()->with(['farmer', 'operator', 'items', 'debt']);
        if ($request->filled('farmer_id')) {
            $q->where('farmer_id', $request->query('farmer_id'));
        }
        if ($request->filled('type')) {
            $q->where('type', $request->query('type'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->filled('from')) {
            $q->where('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->where('created_at', '<=', $request->query('to'));
        }
        return $this->success(
            TransactionResource::collection($q->latest()->paginate((int) $request->query('per_page', 20)))->response()->getData(true),
            'Transactions list.'
        );
    }

    public function checkout(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $tx = $this->service->checkout($request->validated(), $request->user());
            return $this->success(
                new TransactionResource($tx->load(['farmer', 'operator', 'items', 'debt'])),
                'Transaction completed.',
                201
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);
            return $this->error('Transaction failed.', 500);
        }
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['farmer', 'operator', 'items', 'debt']);
        return $this->success(new TransactionResource($transaction), 'Transaction detail.');
    }
}
