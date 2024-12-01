<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Store\StoreResource;
use App\Models\Store\Store;
use App\Repositories\StoreRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreService
{
    use AuthTrait;

    protected StoreRepository $storeRepository;

    protected UserService $userService;

    public function __construct(StoreRepository $storeRepository, UserService $userService)
    {
        $this->storeRepository = $storeRepository;
        $this->userService = $userService;
    }

    public function getAllStores(Request $request)
    {
        try {
            $items = $request->query('items', 20);
            $page = $request->query('page', 1);
            $this->checkGuest('Store', 'perform');
            $stores = $this->storeRepository->getAll($items, $page);

            $hasMorePages = $stores->hasMorePages();

            $data = [
                'Stores' => StoreResource::collection($stores),
                'hasMorePages' => $hasMorePages,
            ];
            $response = ResponseHelper::jsonResponse($data, 'Stores retrieved successfully');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }

    public function getMyStoreById(Store $store)
    {
        try {
            $this->checkOwnership($store, 'Store', 'perform');
            $this->checkAdmin('Store', 'perform');
            $data = ['Store' => StoreResource::make($store)];
            $response = ResponseHelper::jsonResponse($data, 'Store retrieved successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }

    public function getStoreById(Store $store)
    {
        $this->checkGuest('Store', 'perform');
        $data = ['Store' => StoreResource::make($store)];

        return ResponseHelper::jsonResponse($data, 'Store retrieved successfully!');
    }

    public function createStore(array $data): JsonResponse
    {
        $this->checkGuest('Store', 'create');
        $data['user_id'] = auth()->id();
        if (auth()->user()->role->role == 'user') {
            $this->userService->update_role(auth()->id(), 'admin');
        } else {
            $this->checkAdmin('Store', 'create');
        }
        $stores = $this->storeRepository->findByUserId();
        if ($stores->isEmpty()) {
            $this->validateStoreData($data);
            $store = $this->storeRepository->create($data);
            $data = [
                'Store' => StoreResource::make($store),
            ];

            return ResponseHelper::jsonResponse($data, 'Store created successfully!');

        }

        return ResponseHelper::jsonResponse([], 'You already own a store. You cannot create another one.', 403, false);
    }

    public function getStoresOrderedBy($column, $direction, Request $request)
    {
        try {
            $this->checkGuest('Store', 'order');
            $validColumns = ['name', 'created_at', 'updated_at'];
            $validDirections = ['asc', 'desc'];

            if (! in_array($column, $validColumns) || ! in_array($direction, $validDirections)) {
                return ResponseHelper::jsonResponse([], 'Invalid column or direction', 400, false);
            }
            $page = $request->query('page', 1);
            $items = $request->query('items', 20);
            $stores = $this->storeRepository->orderBy($column, $direction, $page, $items);
            $hasMorePages = $stores->hasMorePages();
            $data = [
                'Stores' => StoreResource::collection($stores),
                'hasMorePages' => $hasMorePages,
            ];

            $response = ResponseHelper::jsonResponse($data, 'Stores ordered successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function updateStore(Store $store, array $data)
    {
        try {
            $this->checkGuest('Store', 'perform');
            $this->checkOwnership($store, 'Store', 'update');
            $this->checkAdmin('Store', 'update');
            $this->validateStoreData($data, 'sometimes');
            $store = $this->storeRepository->update($store, $data);
            $data = [
                'Store' => StoreResource::make($store),
            ];

            $response = ResponseHelper::jsonResponse($data, 'Store updated successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function deleteStore(Store $store)
    {
        try {
            $this->checkOwnership($store, 'Store', 'delete');
            $this->checkAdmin('Store', 'delete');
            $this->storeRepository->delete($store);
            $response = ResponseHelper::jsonResponse([], 'Store deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    protected function validateStoreData(array $data, $rule = 'required'): void
    {
        $validator = Validator::make($data, [
            'name' => "$rule|unique:stores,name",
            'location' => "$rule|nullable",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
