<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\User\ValidateUserIdRequest;
use App\Http\Resources\Cart\CartItemsResource;
use App\Models\Cart\Cart;
use App\Models\Cart\Cart_items;
use App\Models\Product\Product;
use App\Repositories\CartItemsRepository;
use App\Traits\AuthTrait;
use App\Traits\ValidationTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

class Cart_Items_Service
{
    use AuthTrait,ValidationTrait;

    protected $cartItemsRepository;

    public function __construct(CartItemsRepository $cartItemsRepository)
    {
        $this->cartItemsRepository = $cartItemsRepository;
    }

    public function getAllCart_items(ValidateUserIdRequest $request)
    {
        $isSuperAdmin = $this->checkSuperAdmin();
        if (! $isSuperAdmin) {
            $this->checkGuest();
        }

        $validated = $request->validated();
        $user_id = $isSuperAdmin && isset($validated['user_id'])
            ? $validated['user_id']
            : auth()->id();
        $cart = $this->checkCart($user_id);
        $items = $request->query('items', 20);
        $cart_items = $this->cartItemsRepository->getAll($items, $user_id);

        $data = [
            'Cart_items' => CartItemsResource::collection($cart_items),
            'total_pages' => $cart_items->lastPage(),
            'current_page' => $cart_items->currentPage(),
            'hasMorePages' => $cart_items->hasMorePages(),
        ];

        return ResponseHelper::jsonResponse($data, 'Cart_items retrieved successfully!');
    }

    public function getCart_itemById(Cart_items $cart_item)
    {
        try {
            if (! $this->checkSuperAdmin()) {
                $cart = Cart::where('id', $cart_item->cart_id)->first();
                $this->checkGuest();
                $this->checkOwnership($cart, 'Cart_items', 'perform');
            }
            $data = ['Cart_items' => CartItemsResource::make($cart_item)];

            $response = ResponseHelper::jsonResponse($data, 'Cart_item retrieved successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function createCart_items(array $data, ValidateUserIdRequest $request)
    {
        try {
            $isSuperAdmin = $this->checkSuperAdmin();
            if (! $isSuperAdmin) {
                $this->checkGuest();
            }

            $validated = $request->validated();
            $user_id = $isSuperAdmin && isset($validated['user_id'])
                ? $validated['user_id']
                : auth()->id();
            if (isset($data['user_id'])) {
                unset($data['user_id']);
            }
            $this->validate_Cart_items_Data($data);
            $cart = $this->checkCart($user_id);
            $data['cart_id'] = $cart->id;
            $cart_item = $this->cartItemsRepository->create($data);
            $data = ['Cart_items' => CartItemsResource::make($cart_item)];
            $response = ResponseHelper::jsonResponse($data, 'Cart_item created successfully!', 201);
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function getCart_items_OrderedBy($column, $direction, ValidateUserIdRequest $request)
    {
        $isSuperAdmin = $this->checkSuperAdmin();
        if (! $isSuperAdmin) {
            $this->checkGuest();
        }
        $validated = $request->validated();
        $user_id = $isSuperAdmin && isset($validated['user_id'])
            ? $validated['user_id']
            : auth()->id();
        $cart = $this->checkCart($user_id);
        $validColumns = ['quantity', 'created_at', 'updated_at'];
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
        $cart_items = $this->cartItemsRepository->orderBy($column, $direction, $items, $user_id);

        $data = [
            'Cart_items' => CartItemsResource::collection($cart_items),
            'total_pages' => $cart_items->lastPage(),
            'current_page' => $cart_items->currentPage(),
            'hasMorePages' => $cart_items->hasMorePages(),
        ];

        return ResponseHelper::jsonResponse($data, 'Cart_items ordered successfully');

    }

    public function updateCart_items(Cart_items $cart_item, array $data)
    {
        try {
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $cart = $this->checkCart($cart_item->cart->user->id);
                $this->checkOwnership($cart, 'Cart_items', 'update');
            } else {
                $user_id = $data['user_id'] ?? $cart_item->cart->user->id;
                $cart = $this->checkCart($user_id);
            } if (isset($data['user_id'])) {
                unset($data['user_id']);
            }
            $this->validate_Cart_items_Data($data, 'sometimes');
            $data['product_id'] = $data['product_id'] ?? $cart_item->product->id;
            $product = Product::where('id', $data['product_id'])->first();
            $data['quantity'] = $data['quantity'] ?? $cart_item->quantity;
            $data['cart_id'] = $cart->id;
            $this->checkAmount($data, $product);
            $cart_item = $this->cartItemsRepository->update($cart_item, $data);
            $data = ['Cart_items' => CartItemsResource::make($cart_item)];
            $response = ResponseHelper::jsonResponse($data, 'Cart_item updated successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function deleteCart_items(Cart_items $cart_item)
    {
        try {
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $cart = Cart::where('id', $cart_item->cart_id)->first();
                $this->checkOwnership($cart, 'Cart_items', 'delete');
            }
            $this->cartItemsRepository->delete($cart_item);
            $response = ResponseHelper::jsonResponse([], 'Cart_item deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    protected function validate_Cart_items_Data(array $data, $rule = 'required')
    {
        $allowedAttributes = ['quantity', 'product_id'];

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
            'quantity' => "$rule|numeric|min:1",
            'product_id' => "$rule|exists:products,id",
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
