<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\StoreResource;
use App\Models\Store;
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
    public function __construct(StoreRepository $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }

    public function getMyStores(Request $request)
    {
        $items = $request->query('items', 20);
        $page=$request->query('page', 1);
        $stores = $this->storeRepository->getAll($items,$page);

        $hasMorePages = $stores->hasMorePages();

        $data = [
            'Stores' => StoreResource::collection($stores),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Stores retrieved successfully');
    }

    public function getMyStoreById(Store $store)
    {
        $this->checkOwnership($store,'Store','show','admin');
        $data = ['Store' => StoreResource::make($store)];

        return ResponseHelper::jsonResponse($data, 'Store retrieved successfully!');
    }

    public function createStore(array $data, Request $request): JsonResponse
    {
        $data['user_id'] = auth()->id();
        $this->validateStoreData($data);
        $this->checkOwnership(null, 'Store', 'create', 'admin');
        $store = $this->storeRepository->create($data);

        $data = [
            'Store' => StoreResource::make($store),
        ];

        return ResponseHelper::jsonResponse($data, 'Store created successfully!', 201);
    }

    public function getStoresOrderedBy($column, $direction, Request $request)
    {
        $validColumns = ['name', 'created_at', 'updated_at'];
        $validDirections = ['asc', 'desc'];

        if (! in_array($column, $validColumns) || ! in_array($direction, $validDirections)) {
            return ResponseHelper::jsonResponse([], 'Invalid column or direction', 400, false);
        }
        $page = $request->query('page', 1);
        $items = $request->query('items', 20);
        $this->checkOwnership(null, 'Store', 'order', 'admin');
        $stores = $this->storeRepository->orderBy($column, $direction, $page, $items);
        $hasMorePages = $stores->hasMorePages();
        $data = [
            'Stores' => StoreResource::collection($stores),
            'hasMorePages' => $hasMorePages,
        ];

        return ResponseHelper::jsonResponse($data, 'Stores ordered successfully!');
    }

    public function updateStore(Store $store, array $data)
    {
        try {
            $this->validateStoreData($data, 'sometimes');
            $this->checkOwnership($store, 'Store', 'update','admin');
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
            $this->checkOwnership($store, 'Store', 'delete','admin');
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
            'location'=>"$rule|nullable",
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
