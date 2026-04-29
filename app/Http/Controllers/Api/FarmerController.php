<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreFarmerRequest;
use App\Http\Requests\Farmer\UpdateFarmerRequest;
use App\Http\Resources\FarmerResource;
use App\Models\Farmer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $q = Farmer::query()->search($request->query('search'));
        if ($request->filled('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('region')) {
            $q->where('region', $request->query('region'));
        }
        return $this->success(
            FarmerResource::collection($q->orderBy('last_name')->paginate((int) $request->query('per_page', 20)))->response()->getData(true),
            'Farmers list.'
        );
    }

    public function store(StoreFarmerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $farmer = Farmer::create($data);
        return $this->success(new FarmerResource($farmer), 'Farmer created.', 201);
    }

    public function show(Farmer $farmer): JsonResponse
    {
        return $this->success(new FarmerResource($farmer), 'Farmer detail.');
    }

    public function update(UpdateFarmerRequest $request, Farmer $farmer): JsonResponse
    {
        // Don't allow lowering credit_limit below current_debt.
        if ($request->filled('credit_limit')
            && (float) $request->input('credit_limit') < (float) $farmer->current_debt) {
            return $this->error(
                'New credit_limit cannot be lower than current_debt (' . $farmer->current_debt . ').',
                422
            );
        }
        $farmer->update($request->validated());
        return $this->success(new FarmerResource($farmer), 'Farmer updated.');
    }

    public function destroy(Farmer $farmer): JsonResponse
    {
        if ((float) $farmer->current_debt > 0) {
            return $this->error('Cannot delete farmer with outstanding debt.', 422);
        }
        $farmer->delete();
        return $this->success(null, 'Farmer deleted.');
    }
}
