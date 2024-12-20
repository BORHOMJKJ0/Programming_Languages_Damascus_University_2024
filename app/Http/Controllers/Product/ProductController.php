<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->productService->getAllProducts($request);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->productService->createProduct($request->all());
    }

    public function create_product_with_images(Request $request): JsonResponse
    {
        return $this->productService->create_product_with_images($request);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->productService->getProductById($product);
    }

    public function orderBy($column, $direction, Request $request): JsonResponse
    {
        return $this->productService->getProductsOrderedBy($column, $direction, $request);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        return $this->productService->updateProduct($product, $request->all());
    }

    public function destroy(Product $product): JsonResponse
    {
        return $this->productService->deleteProduct($product);
    }
}
