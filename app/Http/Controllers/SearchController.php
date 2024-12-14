<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\Cart\CartItemsResource;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Store\StoreResource;
use App\Http\Resources\User\UserResource;
use App\Models\Cart\Cart_items;
use App\Models\Category\Category;
use App\Models\Product\Product;
use App\Models\Store\Store;
use App\Models\User\User;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchController extends Controller
{
    use AuthTrait;

    public function search(SearchRequest $request, $model)
    {
        $numericFields = ['price', 'amount', 'quantity'];
        $user = auth()->user();
        $items = $request->query('items', 20);
        $column = $request->input('column');
        $value = $request->input('value');
        $min = $request->input('min');
        $max = $request->input('max');

        $models = $this->getModelsBasedOnRole($user);

        if (! array_key_exists($model, $models)) {
            $this->checkGuest();
            throw new HttpResponseException(
                ResponseHelper::jsonResponse(
                    [],
                    "You are not authorized to search in $model.",
                    403,
                    false
                )
            );
        }
        if (in_array($column, $numericFields)) {
            if (! $min && ! $max) {
                return ResponseHelper::jsonResponse(
                    [],
                    "For {$column}, at least one of min or max must be provided.",
                    400,
                    false
                );
            }
        } else {
            if (! $value) {
                return ResponseHelper::jsonResponse(
                    [],
                    "For {$column}, the value must be provided.",
                    400,
                    false
                );
            }
        }

        $query = $models[$model];

        if ($model === 'cart_items') {
            if ($user->role->role === 'super_admin') {
                $user_id = $request->query('user_id');

                if ($user_id) {
                    if (! User::find($user_id)) {
                        return ResponseHelper::jsonResponse(
                            [],
                            "The user_id '{$user_id}' does not exist.",
                            400,
                            false
                        );
                    }
                } else {
                    $user_id = $user->id;
                }

                $query->whereHas('cart', function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                });
            } elseif ($user->role->role === 'user' || $user->role->role === 'admin') {
                $user_id = $user->id;

                if ($request->query('user_id')) {
                    $this->checkSuperAdmin();
                }

                $query->whereHas('cart', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            } else {
                return ResponseHelper::jsonResponse(
                    [],
                    'You are not authorized to search in cart_items.',
                    403,
                    false
                );
            }
        }

        if ($model === 'users' && $column === 'full_name' && $value) {
            $this->checkSuperAdmin();
            $query->where(function ($q) use ($value) {
                $q->where('first_name', 'LIKE', "%{$value}%")
                    ->orWhere('last_name', 'LIKE', "%{$value}%");
            });
        } elseif ($column && $value) {
            $query->where($column, 'LIKE', "%{$value}%");
        }

        if (in_array($model, ['products', 'cart_items']) && ($min || $max)) {
            $query->when($min, fn ($q) => $q->where($column, '>=', $min))
                ->when($max, fn ($q) => $q->where($column, '<=', $max));
        }

        $results = $query->paginate($items);

        $resourceClass = $this->getResourceClass($model);

        $data = [
            $model => $resourceClass::collection($results->items()),
            'current_page' => $results->currentPage(),
            'total_pages' => $results->lastPage(),
            'hasMorePages' => $results->hasMorePages(),
        ];

        return ResponseHelper::jsonResponse(
            $data,
            "$model fetched successfully"
        );
    }

    private function getModelsBasedOnRole($user)
    {
        if (! $user) {
            return [
                'products' => Product::query(),
                'categories' => Category::query(),
            ];
        }

        switch ($user->role->role) {
            case 'user':
            case 'admin':
                return [
                    'products' => Product::query(),
                    'categories' => Category::query(),
                    'stores' => Store::query(),
                    'cart_items' => Cart_items::query(),
                ];
            case 'super_admin':
                return [
                    'products' => Product::query(),
                    'categories' => Category::query(),
                    'stores' => Store::query(),
                    'cart_items' => Cart_items::query(),
                    'users' => User::query(),
                ];
            default:
                return [];
        }
    }

    private function getResourceClass($model)
    {
        $resources = [
            'users' => UserResource::class,
            'categories' => CategoryResource::class,
            'stores' => StoreResource::class,
            'products' => ProductResource::class,
            'cart_items' => CartItemsResource::class,
        ];

        return $resources[$model];
    }
}
