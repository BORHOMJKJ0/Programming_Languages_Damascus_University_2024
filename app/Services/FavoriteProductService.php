<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product\Product;
use App\Models\User\User;
use App\Repositories\FavoriteProductRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteProductService
{
    use AuthTrait;

    private FavoriteProductRepository $favoriteProductRepository;

    public function __construct(FavoriteProductRepository $favoriteProductRepository)
    {
        $this->favoriteProductRepository = $favoriteProductRepository;
    }

    public function index(Request $request, ?User $user): JsonResponse
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();
        }

        $items = $request->query('items', 20);
        $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();

        $favoriteProducts = $this->favoriteProductRepository->getAll($user, $items);

        $responseData = [
            'products' => ProductResource::collection($favoriteProducts),
            'total_pages' => $favoriteProducts->lastPage(),
            'current_page' => $favoriteProducts->currentPage(),
            'hasMorePages' => $favoriteProducts->hasMorePages(),
        ];

        return ResponseHelper::jsonResponse($responseData, 'Retrieved all favorite products successfully.');
    }

    public function store(Product $product, ?User $user): JsonResponse
    {
        $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();

        if ($user->favoriteProducts()->where('product_id', $product->id)->exists()) {
            if ($user->id == auth()->id()) {
                return ResponseHelper::jsonResponse([], 'Product is already in favorites.', 400, false);
            } else {
                return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : Product is already in this user favorites.', 400, false);
            }
        }

        $this->favoriteProductRepository->create($user, $product);

        return ResponseHelper::jsonResponse([], 'Product added to favorites successfully.');
    }

    public function destroy(Product $product, ?User $user): JsonResponse
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();
        }
        $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();

        if (! $user->favoriteProducts()->where('product_id', $product->id)->exists()) {
            if ($user->id == auth()->id()) {
                return ResponseHelper::jsonResponse([], 'This product is not in your favorites.', 404, false);
            } else {
                return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : this Product is not in this user favorites.', 400, false);
            }

        }

        $this->favoriteProductRepository->delete($user, $product);

        return ResponseHelper::jsonResponse([], 'Product removed from favorites successfully.');
    }
}
