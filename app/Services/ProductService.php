<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductService
{
    use AuthTrait;
    protected ProductRepository $productRepository;
    private CategoryService $categoryService;

    public function __construct(ProductRepository $productRepository, CategoryService $categoryService)
    {
        $this->productRepository = $productRepository;
        $this->categoryService = $categoryService;
    }
    public function getAllProducts(Request $request)
    {
        $items = $request->query('items', 20);
        $products = $this->productRepository->getAll($items);

        $hasMorePages = $products->hasMorePages();

        $data = [
            'Products' => ProductResource::collection($products),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Products retrieved successfully');
    }

    public function getProductById(Product $product)
    {
        $data = ['product' => ProductResource::make($product)];

        return ResponseHelper::jsonResponse($data, 'Product retrieved successfully!');
    }
    public function createProduct(array $data,Request $request): JsonResponse
    {
        $data['user_id'] = auth()->id();
        $this->validateProductData($data);
        $path = $request->file('image')->store('images', 'public');
        $data['image'] = $path;
        $product = $this->productRepository->create($data);

        $data = [
            'Product' => ProductResource::make($product),
        ];

        return ResponseHelper::jsonResponse($data, 'Product created successfully!', 201);
    }
    public function getProductsOrderedBy($column, $direction, Request $request)
    {
        $validColumns = ['name', 'price', 'created_at', 'updated_at'];
        $validDirections = ['asc', 'desc'];

        if (! in_array($column, $validColumns) || ! in_array($direction, $validDirections)) {
            return ResponseHelper::jsonResponse([], 'Invalid column or direction', 400, false);
        }
        $page = $request->query('page', 1);
        $items = $request->query('items', 20);

        $products = $this->productRepository->orderBy($column, $direction, $page, $items);
        $hasMorePages = $products->hasMorePages();
        $data = [
            'Products' => ProductResource::collection($products),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Products ordered successfully!');
    }
    public function updateProduct(Product $product, array $data)
    {
        try {
            $this->validateProductData($data, 'sometimes');
            $this->checkOwnership($product, 'Product', 'update');
            if (isset($data['image'])) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $path = $data['image']->store('images', 'public');
                $data['image'] = $path;
            }
            $product = $this->productRepository->update($product, $data);
            $data = [
                'Product' => ProductResource::make($product),
            ];

            $response = ResponseHelper::jsonResponse($data, 'Product updated successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }
    public function deleteProduct(Product $product)
    {
        try {
            $this->checkOwnership($product, 'Product', 'delete');
            $this->productRepository->delete($product);
            $response = ResponseHelper::jsonResponse([], 'Product deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    protected function validateProductData(array $data, $rule = 'required'): void
    {
        $validator = Validator::make($data, [
            'name' => "$rule|unique:products,name",
            'price' => "$rule",
            'amount'=>"$rule",
            'description' => "$rule",
            'image' => "$rule",
            'category_id' => "$rule|exists:categories,id",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
