<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product\Product;
use App\Models\User\User;
use App\Repositories\UserRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteProductService
{
    use AuthTrait;

    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index(Request $request, ?User $user): JsonResponse
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();
        }

        $page = $request->query('page', 1);
        $items = $request->query('items', 20);
        $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();

        $favoriteProducts = $user->favoriteProducts()->with(['category'])->paginate($items, ['*'], 'page', $page);
        $hasMorePages = $favoriteProducts->hasMorePages();

        $responseData = [
            'products' => ProductResource::collection($favoriteProducts),
            'hasMorePages' => $hasMorePages,
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

        $user->favoriteProducts()->attach($product->id);

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

        $user->favoriteProducts()->detach($product->id);

        return ResponseHelper::jsonResponse([], 'Product removed from favorites successfully.');
    }
}
