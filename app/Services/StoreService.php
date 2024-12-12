<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Store\MyStoreResource;
use App\Http\Resources\Store\StoreResource;
use App\Models\Store\Store;
use App\Repositories\StoreRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
            }
            $items = $request->query('items', 20);
            $page = $request->query('page', 1);
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

    public function getMyStoreById()
    {
        try {
            $this->checkGuest();
            if (! $this->checkSuperAdmin()) {
                $this->checkAdmin('Store', 'perform');
            }
            $store = Store::where('user_id', auth()->id())->first();
            if (! $store) {
                throw new HttpResponseException(
                    ResponseHelper::jsonResponse(
                        [],
                        "I don't have a store ): .",
                        404,
                        false
                    )
                );
            }
            $data = ['Store' => MyStoreResource::make($store)];
            $response = ResponseHelper::jsonResponse($data, 'Store retrieved successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function getStoreById(Store $store): JsonResponse
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();
        }
        $data = ['Store' => StoreResource::make($store)];

        return ResponseHelper::jsonResponse($data, 'Store retrieved successfully!');
    }

    public function createStore(array $data, Request $request): JsonResponse
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();

            if (auth()->user()->role->role === 'user') {
                $this->userService->update_role(auth()->id(), 'admin');
            } else {
                $this->checkAdmin('Store', 'create');
            }
            $data['user_id'] = auth()->id();
        } else {
            $data['user_id'] = $data['user_id'] ?? auth()->id();
        }

        $path = $request->hasFile('image') ? $request->file('image')->store('images', 'public') : null;
        $data['image'] = $path;

        $stores = $this->storeRepository->findByUserId();
        if ($stores->isEmpty()) {
            $this->validateStoreData($data);
            $store = $this->storeRepository->create($data);

            $data = [
                'Store' => StoreResource::make($store),
            ];

            return ResponseHelper::jsonResponse($data, 'Store created successfully!');
        }

        if ($data['user_id'] == auth()->id()) {
            return ResponseHelper::jsonResponse([], 'You already own a store. You cannot create another one.', 403, false);
        } else {
            return ResponseHelper::jsonResponse([], 'Mr. Super Admin: This user already owns a store. You cannot create another one.', 403, false);
        }
    }

    public function getStoresOrderedBy($column, $direction, Request $request)
    {
        try {
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
            }
            $validColumns = ['name', 'location', 'created_at', 'updated_at'];
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
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkOwnership($store, 'Store', 'update');
                $this->checkAdmin('Store', 'update');
            }
            $this->validateStoreData($data, 'sometimes');
            if (isset($data['image'])) {
                if ($store->image && Storage::disk('public')->exists($store->image)) {
                    Storage::disk('public')->delete($store->image);
                }
                $path = $data['image']->store('images', 'public');
                $data['image'] = $path;
            }
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
            if (! $this->checkSuperAdmin()) {
                $this->checkOwnership($store, 'Store', 'delete');
                $this->checkAdmin('Store', 'delete');
            }
            $this->storeRepository->delete($store);
            $response = ResponseHelper::jsonResponse([], 'Store deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function validateStoreData(array $data, $rule = 'required'): void
    {
        $allowedAttributes = ['name', 'image', 'location', 'user_id'];

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
            'name' => "$rule|unique:stores,name",
            'image' => 'sometimes|nullable',
            'location' => 'sometimes|nullable',
            'user_id' => 'sometimes|exists:users,id',
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
