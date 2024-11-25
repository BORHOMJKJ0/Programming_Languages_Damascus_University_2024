<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->middleware('auth:api');
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->categoryService->getAllCategories($request);
    }
    public function show(Category $category): JsonResponse
    {
        return $this->categoryService->getCategoryById($category);
    }

    public function orderBy($column, $direction, Request $request): JsonResponse
    {
        return $this->categoryService->getCategoriesOrderedBy($column, $direction, $request);
    }
}
