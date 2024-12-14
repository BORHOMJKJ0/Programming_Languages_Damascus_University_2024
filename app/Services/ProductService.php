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
        $products = $this->productRepository->getAll($items);

        $data = [
            'Products' => ProductResource::collection($products),
            'total_pages' => $products->lastPage(),
            'current_page' => $products->currentPage(),
            'hasMorePages' => $products->hasMorePages(),
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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkAdmin('Product', 'create');
            }
            if (isset($data['store_id']) && $this->checkSuperAdmin()) {
                $store = Store::where('id', $data['store_id'])->first();
                if (! $store) {
                    return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : No store found for this user', 404, false);
                }
                unset($data['store_id']);
            } else {
                $store = Store::where('user_id', auth()->id())->first();
                if (! $store) {
                    return ResponseHelper::jsonResponse([], "I don't have a store ): .", 404, false);

                }
            }
            $this->validateProductData($data);

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
        $validColumns = ['name', 'price', 'description', 'created_at', 'updated_at'];
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

        $products = $this->productRepository->orderBy($column, $direction, $items);
        $data = [
            'Products' => ProductResource::collection($products),
            'total_pages' => $products->lastPage(),
            'current_page' => $products->currentPage(),
            'hasMorePages' => $products->hasMorePages(),
        ];

        return ResponseHelper::jsonResponse($data, 'Products ordered successfully!');
    }

    public function updateProduct(Product $product, array $data)
    {
        try {
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkAdmin('Product', 'update');
                $this->checkOwnership($product->store, 'Product', 'update');
            }
            if (isset($data['store_id']) && $this->checkSuperAdmin()) {
                $store = Store::where('id', $data['store_id'])->first();
                if (! $store) {
                    return ResponseHelper::jsonResponse([], 'No store found for this user', 404, false);
                }
                unset($data['store_id']);
            }
            $this->validateProductData($data, 'sometimes');
            $data['store_id'] = $store->id;
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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkAdmin('Product', 'delete');
                $this->checkOwnership($product->store, 'Product', 'delete');
            }
            $this->productRepository->delete($product);
            $response = ResponseHelper::jsonResponse([], 'Product deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    protected function validateProductData(array $data, $rule = 'required'): void
    {
        $allowedAttributes = ['name', 'description', 'amount', 'price', 'category_id'];

        $unexpectedAttributes = array_diff(array_keys($data), $allowedAttributes);
        if (! empty($unexpectedAttributes)) {
            throw new HttpResponseException(
                ResponseHelper::jsonResponse(
                    [],
                    'You are not allowed to send the following attributes: '.implode(', ', $unexpectedAttributes),
                    400,
                    false
                )
            );
        }
        $validator = Validator::make($data, [
            'name' => "$rule|string|unique:products,name",
            'description' => "$rule|string",
            'amount' => "$rule|numeric|min:1",
            'price' => "$rule|numeric|min:1",
            'category_id' => "$rule|exists:categories,id",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new HttpResponseException(
                ResponseHelper::jsonResponse(
                    [],
                    $errors,
                    400,
                    false
                )
            );
        }
    }
}
