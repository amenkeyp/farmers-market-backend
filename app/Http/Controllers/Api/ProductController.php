<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $q = Product::query()->with('category');
        if ($request->filled('category_id')) {
            $q->where('category_id', $request->query('category_id'));
        }
        if ($search = $request->query('search')) {
            $like = "%$search%";
            $q->where(fn ($qq) => $qq->where('name', 'like', $like)->orWhere('sku', 'like', $like));
        }
        if ($request->filled('is_active')) {
            $q->where('is_active', $request->boolean('is_active'));
        }
        return $this->success(
            ProductResource::collection($q->orderBy('name')->paginate((int) $request->query('per_page', 20)))->response()->getData(true),
            'Products list.'
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $p = Product::create($request->validated());
        return $this->success(new ProductResource($p->load('category')), 'Product created.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success(new ProductResource($product->load('category')), 'Product detail.');
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());
        return $this->success(new ProductResource($product->fresh('category')), 'Product updated.');
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->transactionItems()->exists()) {
            // Soft delete to preserve history.
            $product->delete();
            return $this->success(null, 'Product archived (had history).');
        }
        $product->delete();
        return $this->success(null, 'Product deleted.');
    }
}
