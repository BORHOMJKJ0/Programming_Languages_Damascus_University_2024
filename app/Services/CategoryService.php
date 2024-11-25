<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;
class CategoryService
{
    use AuthTrait;

    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }
    public function getAllCategories(Request $request)
    {
        $page = $request->query('page', 1);
        $items = $request->query('items', 20);
        $categories = $this->categoryRepository->getAll($items, $page);
        $hasMorePages = $categories->hasMorePages();

        $data = [
            'Categories' => CategoryResource::collection($categories),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Categories retrieved successfully');
    }
    public function getCategoryById(Category $category)
    {
        $data = ['category' => CategoryResource::make($category)];

        return ResponseHelper::jsonResponse($data, 'Category retrieved successfully!');
    }
    public function getCategoriesOrderedBy($column, $direction, Request $request)
    {
        $validColumns = ['name', 'created_at', 'updated_at'];
        $validDirections = ['asc', 'desc'];
        if (! in_array($column, $validColumns) || ! in_array($direction, $validDirections)) {
            return ResponseHelper::jsonResponse([], 'Invalid column or direction', 400, false);
        }
        $page = $request->query('page', 1);
        $items = $request->query('items', 20);

        $products = $this->categoryRepository->orderBy($column, $direction, $page, $items);
        $hasMorePages = $products->hasMorePages();
        $data = [
            'Categories' => CategoryResource::collection($products),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Categories ordered successfully!');
    }

}
