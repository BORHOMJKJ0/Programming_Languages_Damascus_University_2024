<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product\Product;
use App\Models\Store\Store;
use App\Repositories\ProductRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductService
{
    use AuthTrait;

    protected ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts(Request $request)
    {
        $items = $request->query('items', 20);
        $page = $request->query('page', 1);
        $products = $this->productRepository->getAll($items, $page);

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

    public function createProduct(array $data): JsonResponse
    {
        try {
            $this->checkGuest();
            $this->checkAdmin('Product', 'create');
            $this->validateProductData($data);
            $store = Store::where('user_id', auth()->id())->first();
            if (! $store) {
                return ResponseHelper::jsonResponse([], 'No store found for this user', 404, false);
            }
            $data['store_id'] = $store->id;
            $product = $this->productRepository->create($data);
            $data = [
                'Product' => ProductResource::make($product),
            ];

            $response = ResponseHelper::jsonResponse($data, 'Product created successfully!', 201);
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
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
            $this->checkGuest();
            $this->validateProductData($data, 'sometimes');
            $this->checkAdmin('Product', 'update');
            $this->checkOwnership($product->store, 'Product', 'update');
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
            $this->checkGuest();
            $this->checkAdmin('Product', 'delete');
            $this->checkOwnership($product->store, 'Product', 'delete');
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
            'description' => "$rule",
            'amount' => "$rule",
            'price' => "$rule",
            'category_id' => "$rule|exists:categories,id",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new HttpResponseException(
                response()->json([
                    'successful' => false,
                    'message' => $errors,
                    'status_code' => 400,
                ], 400)
            );
        }
    }
}
