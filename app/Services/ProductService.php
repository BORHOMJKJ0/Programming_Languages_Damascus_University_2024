<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Product\ProductResource;
use App\Models\Category\Category;
use App\Models\Image\Image;
use App\Models\Product\Product;
use App\Models\Store\Store;
use App\Repositories\CategoryRepository;
use App\Repositories\ImageRepository;
use App\Repositories\ProductRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductService
{
    use AuthTrait;

    protected ProductRepository $productRepository;

    protected CategoryRepository $categoryRepository;

    protected ImageRepository $imageRepository;

    public function __construct(ProductRepository $productRepository, CategoryRepository $categoryRepository, ImageRepository $imageRepository)
    {
        $this->productRepository = $productRepository;
        $this->imageRepository = $imageRepository;
        $this->categoryRepository = $categoryRepository;
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

    public function create_product_with_details(Request $request): JsonResponse
    {
        $this->validateCreateProductRequest($request);

        return DB::transaction(function () use ($request) {
            $category_id = Category::where('name', $request->input('category_name'))->value('id');
            if (! $category_id) {
                return ResponseHelper::jsonResponse([], 'Category Not Found', 404, false);
            }

            $productData = [
                'name' => $request->input('product_name'),
                'description' => $request->input('product_description'),
                'price' => $request->input('product_price'),
                'amount' => $request->input('product_amount'),
                'category_id' => $category_id,
            ];

            if ($request->has('store_id')) {
                $productData['store_id'] = $request->input('store_id');
            }

            if ($request->has('images')) {
                $images = $request->input('images');

                $hasMainImage = collect($images)->contains(function ($image) {
                    return isset($image['main']) && $image['main'] == 1;
                });

                if (! $hasMainImage) {
                    return ResponseHelper::jsonResponse([], 'You must choose at least one main image for the product.', 400, false);
                }

                foreach ($images as $key => $image) {
                    if (! $request->hasFile("images.$key.image") || ! isset($image['main'])) {
                        return ResponseHelper::jsonResponse([], 'Each image must have an image file and a main flag.', 400, false);
                    }
                }
            }

            $response = $this->createProduct($productData);

            if ($response->getStatusCode() !== 201) {
                return $response;
            }

            $responseData = $response->getData(true);
            $productId = $responseData['data']['Product']['id'] ?? null;
            if (! $productId) {
                return ResponseHelper::jsonResponse([], 'Failed to retrieve the created product.', 500, false);
            }

            $product = Product::where('id', $productId)->first();

            if ($request->has('images')) {
                $processResult = $this->processProductImages($request, $product);

                if ($processResult->getStatusCode() !== 201) {
                    return $processResult;
                }
            }

            $data = [
                'Product' => ProductResource::make($product),
            ];

            return ResponseHelper::jsonResponse(
                $data, 'Product and its images added successfully!',
                201
            );
        });
    }

    private function validateCreateProductRequest(Request $request): void
    {
        $request->validate([
            'category_name' => 'required|string',
            'product_name' => 'required|string',
            'product_description' => 'required|string',
            'product_price' => 'required|numeric',
            'product_amount' => 'required|numeric',
            'store_id' => 'sometimes|exists:stores,id',
            'images' => 'required|array',
            'images.*.image' => 'required_with:images|file',
            'images.*.main' => 'required_with:images|boolean',
        ]);
    }

    private function processProductImages(Request $request, Product $product): JsonResponse
    {
        $createdImages = [];
        $failedImages = [];

        foreach ($request->input('images') as $key => $imageData) {
            try {
                $imageFile = $request->file("images.$key.image");
                if (! $imageFile || ! $imageFile->isValid()) {
                    throw new \Exception("No valid file uploaded for image $key.");
                }

                $data = [
                    'main' => isset($imageData['main']) ? filter_var($imageData['main'], FILTER_VALIDATE_BOOLEAN) : false,
                    'product_id' => $product->id,
                ];

                $hasMainImage = Image::where('product_id', $product->id)
                    ->where('main', 1)
                    ->exists();

                if ($hasMainImage && $data['main']) {
                    throw new \Exception('This product already has a main image.');
                }

                $path = $imageFile->store('images', 'public');
                $data['image'] = $path;

                $image = $this->imageRepository->create($data);
                $createdImages[] = $image;

            } catch (\Exception $e) {
                $failedImages[] = "Image $key creation failed: ".$e->getMessage();
            }
        }

        if (count($failedImages) > 0) {
            $product->delete();

            return ResponseHelper::jsonResponse([
                'created' => $createdImages,
                'errors' => $failedImages,
            ], 'Some images failed to upload. Product has been deleted.', 400, false);
        }

        return ResponseHelper::jsonResponse([], 'All images uploaded successfully.', 201);
    }
}
