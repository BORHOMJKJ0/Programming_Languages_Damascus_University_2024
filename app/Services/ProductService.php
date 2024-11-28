<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product\Product;
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
        $product->load('stores');
        $data = ['product' => ProductResource::make($product)];

        return ResponseHelper::jsonResponse($data, 'Product retrieved successfully!');
    }

    public function createProduct(array $data, Request $request): JsonResponse
    {
        try {
            $this->checkAccount(null, 'Product', 'create');
            $this->validateProductData($data);
            $path = $request->file('image')->store('images', 'public');
            $data['image'] = $path;
            $userStore = auth()->user()->store;
            if (! $userStore) {
                throw new HttpResponseException(ResponseHelper::jsonResponse(
                    null,
                    'User does not have an associated store.',
                    403
                ));
            }
            $storeId = $userStore->id;
            $price = $data['price'];
            $amount = $data['amount'];
            unset($data['price'], $data['amount']);
            $product = $this->productRepository->create($data);
            $product->stores()->attach($storeId, [
                'price' => $price,
                'amount' => $amount,
            ]);
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
            $userStore = auth()->user()->store;
            if (! $userStore) {
                throw new HttpResponseException(ResponseHelper::jsonResponse(
                    null,
                    'User does not have an associated store.',
                    403
                ));
            }

            $storeId = $userStore->id;

            $this->validateProductData($data, 'sometimes');
            $this->checkOwnership($product, 'Product', 'update', 'admin');
            $this->checkAccount($product, 'Product', 'update');

            if (isset($data['image'])) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $path = $data['image']->store('images', 'public');
                $data['image'] = $path;
            }

            $price = $data['price'] ?? null;
            $amount = $data['amount'] ?? null;

            unset($data['price'], $data['amount']);

            $product = $this->productRepository->update($product, $data);

            $product->stores()->updateExistingPivot($storeId, array_filter([
                'price' => $price,
                'amount' => $amount,
            ]));

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
            $this->checkOwnership($product, 'Product', 'delete', 'admin');
            $this->checkAccount($product, 'Product', 'delete');
            $product->stores()->detach();
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
            'image' => "$rule",
            'category_id' => "$rule|exists:categories,id",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
