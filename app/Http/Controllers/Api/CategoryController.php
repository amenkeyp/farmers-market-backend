<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('tree')) {
            $roots = Category::with('descendants')->whereNull('parent_id')->orderBy('name')->get();
            return $this->success(CategoryResource::collection($roots), 'Categories tree.');
        }

        $q = Category::query();
        if ($request->filled('parent_id')) {
            $q->where('parent_id', $request->query('parent_id'));
        }
        if ($search = $request->query('search')) {
            $q->where('name', 'like', "%$search%");
        }
        return $this->success(
            CategoryResource::collection($q->orderBy('name')->paginate((int) $request->query('per_page', 20)))->response()->getData(true),
            'Categories list.'
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $cat = Category::create($request->validated());
        return $this->success(new CategoryResource($cat), 'Category created.', 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('children', 'parent');
        return $this->success(new CategoryResource($category), 'Category detail.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        // Prevent setting parent to self or to one of its descendants (cycle).
        if ($request->filled('parent_id')) {
            $newParent = (int) $request->input('parent_id');
            if ($newParent === $category->id) {
                return $this->error('A category cannot be its own parent.', 422);
            }
            if ($this->isDescendant($category->id, $newParent)) {
                return $this->error('Cannot move category under one of its descendants.', 422);
            }
        }
        $category->update($request->validated());
        return $this->success(new CategoryResource($category), 'Category updated.');
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists() || $category->products()->exists()) {
            return $this->error('Cannot delete category with children or products.', 422);
        }
        $category->delete();
        return $this->success(null, 'Category deleted.');
    }

    private function isDescendant(int $ancestorId, int $candidateId): bool
    {
        $current = Category::find($candidateId);
        while ($current && $current->parent_id) {
            if ($current->parent_id === $ancestorId) {
                return true;
            }
            $current = Category::find($current->parent_id);
        }
        return false;
    }
}
