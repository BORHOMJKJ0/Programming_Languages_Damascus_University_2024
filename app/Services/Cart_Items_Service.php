<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\CartItemsResource;
use App\Models\Cart;
use App\Models\Cart_items;
use App\Models\Product;
use App\Repositories\CartItemsRepository;
use App\Traits\AuthTrait;
use App\Traits\ValidationTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Cart_Items_Service
{
    use AuthTrait,ValidationTrait;

    protected $cartItemsRepository;

    public function __construct(CartItemsRepository $cartItemsRepository)
    {
        $this->cartItemsRepository = $cartItemsRepository;
    }
    public function getAllCart_items(Request $request)
    {
        $page = $request->query('page', 1);
        $items = $request->query('items', 20);

        $cart_item = $this->cartItemsRepository->getAll($items, $page);
        $hasMorePages = $cart_item->hasMorePages();

        $data = [
            'Cart_items' => CartItemsResource::collection($cart_item),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Cart_items retrieved successfully!');
    }
    public function getCart_itemById(Cart_items $cart_item)
    {
        try {
            $cart = Cart::where('id', $cart_item->cart_id)->first();

            $this->checkOwnership($cart, 'Cart_items', 'perform');

            $data = ['Cart_items' => CartItemsResource::make($cart_item)];

            $response = ResponseHelper::jsonResponse($data, 'Cart_item retrieved successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function createCart_items(array $data)
    {
        try {
            $this->validate_Cart_items_Data($data);
            $product = Product::where('id', $data['product_id'])->first();
            $this->checkAmount($data, $product);
            $cart_item = $this->cartItemsRepository->create($data);
            $data = ['Cart_items' => CartItemsResource::make($cart_item)];
            $response = ResponseHelper::jsonResponse($data, 'Cart_item created successfully!', 201);
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }
    public function getCart_items_OrderedBy($column, $direction, Request $request)
    {
        $validColumns = ['quantity', 'created_at', 'updated_at'];
        $validDirections = ['asc', 'desc'];

        if (! in_array($column, $validColumns) || ! in_array($direction, $validDirections)) {
            return ResponseHelper::jsonResponse([], 'Invalid column or direction', 400, false);
        }

        $page = $request->query('page', 1);
        $items = $request->query('items', 20);
        $cart_item = $this->cartItemsRepository->orderBy($column, $direction, $page, $items);
        $hasMorePages = $cart_item->hasMorePages();

        $data = [
            'Cart_items' => CartItemsResource::collection($cart_item),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Cart_items ordered successfully');

    }
    public function updateCart_items(Cart_items $cart_item, array $data)
    {
        try {
            $this->validate_Cart_items_Data($data, 'sometimes');
            $cart = Cart::where('id', $cart_item->cart_id)->first();
            $this->checkOwnership($cart, 'Cart_items', 'update');
            $product_id = $data['product_id'] ?? $cart_item->product->id;
            $product = Product::where('id', $product_id)->first();
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
            $cart = Cart::where('id', $cart_item->cart_id)->first();
            $this->checkOwnership($cart, 'Cart_items', 'delete');
            $this->cartItemsRepository->delete($cart_item);
            $response = ResponseHelper::jsonResponse([], 'Cart_item deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    protected function validate_Cart_items_Data(array $data, $rule = 'required')
    {
        $validator = Validator::make($data, [
            'quantity' => "$rule",
            'product_id' => "$rule|exists:products,id",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
