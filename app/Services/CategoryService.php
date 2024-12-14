<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Category\CategoryResource;
use App\Models\Category\Category;
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
        $items = $request->query('items', 20);
        $categories = $this->categoryRepository->getAll($items);

        $data = [
            'Categories' => CategoryResource::collection($categories),
            'total_pages' => $categories->lastPage(),
            'current_page' => $categories->currentPage(),
            'hasMorePages' => $categories->hasMorePages(),
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
            return ResponseHelper::jsonResponse(
                [],
                'Invalid sort column or direction. Allowed columns: '.implode(', ', $validColumns).
                '. Allowed directions: '.implode(', ', $validDirections).'.',
                400,
                false
            );
        }
        $items = $request->query('items', 20);

        $categories = $this->categoryRepository->orderBy($column, $direction, $items);
        $data = [
            'Categories' => CategoryResource::collection($categories),
            'total_pages' => $categories->lastPage(),
            'current_page' => $categories->currentPage(),
            'hasMorePages' => $categories->hasMorePages(),
        ];

        return ResponseHelper::jsonResponse($data, 'Categories ordered successfully!');
    }
}
