<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\User\User;
use App\Services\FavoriteProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteProductController extends Controller
{
    private $favoriteProductService;

    public function __construct(FavoriteProductService $favoriteProductService)
    {
        $this->favoriteProductService = $favoriteProductService;
    }

    public function index(Request $request, ?User $user = null): JsonResponse
    {
        return $this->favoriteProductService->index($request, $user);
    }

    public function store(Product $product, ?User $user = null): JsonResponse
    {
        return $this->favoriteProductService->store($product, $user);
    }

    public function destroy(Product $product, ?User $user = null): JsonResponse
    {
        return $this->favoriteProductService->destroy($product, $user);
    }
}
