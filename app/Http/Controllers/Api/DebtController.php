<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DebtResource;
use App\Models\Debt;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $q = Debt::query()->with(['farmer', 'transaction']);

        if ($request->filled('farmer_id')) {
            $q->where('farmer_id', $request->query('farmer_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->boolean('open_only')) {
            $q->whereIn('status', [Debt::STATUS_OPEN, Debt::STATUS_PARTIALLY_PAID]);
        }
        if ($request->boolean('overdue')) {
            $q->whereIn('status', [Debt::STATUS_OPEN, Debt::STATUS_PARTIALLY_PAID])
                ->whereNotNull('due_at')
                ->where('due_at', '<', now());
        }

        $sort = $request->query('sort', 'fifo');
        match ($sort) {
            'newest' => $q->orderByDesc('issued_at'),
            'amount' => $q->orderByDesc('remaining_amount'),
            default => $q->orderBy('issued_at')->orderBy('id'), // FIFO
        };

        return $this->success(
            DebtResource::collection($q->paginate((int) $request->query('per_page', 20)))->response()->getData(true),
            'Debts list.'
        );
    }

    public function show(Debt $debt): JsonResponse
    {
        $debt->load(['farmer', 'transaction.items']);
        return $this->success(new DebtResource($debt), 'Debt detail.');
    }
}
