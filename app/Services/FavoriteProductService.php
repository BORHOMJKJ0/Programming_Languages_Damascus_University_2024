<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Product\ProductResource;
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

    public function index(Request $request): JsonResponse
    {
        $this->checkGuest();
        $page = $request->query('page', 1);
        $items = $request->query('items', 20);

        $user = $this->userRepository->findById(auth()->user()->id);
        $favoriteProducts = $user->favoriteProducts()->with(['category'])->paginate($items, ['*'], 'page', $page);
        $hasMorePages = $favoriteProducts->hasMorePages();
        $data = [
            'products' => ProductResource::collection($favoriteProducts),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'retrieve all favorite products');
    }

    public function store($product_id): JsonResponse
    {
        $this->checkGuest();
        $user = $this->userRepository->findById(auth()->user()->id);
        $user->favoriteProducts()->attach($product_id);

        return ResponseHelper::jsonResponse([], 'Product added to favorites');
    }

    public function destroy($product_id): JsonResponse
    {
        $this->checkGuest();
        $user = $this->userRepository->findById(auth()->user()->id);
        if (! $user->favoriteProducts()->where('product_id', $product_id)->exists()) {
            return ResponseHelper::jsonResponse([], 'This product is not in your favorites', 404, false);
        }
        $user->favoriteProducts()->detach($product_id);

        return ResponseHelper::jsonResponse([], 'Product removed from favorites');
    }
}
