<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\RequestNotification;
use App\Http\Resources\Store\MyStoreResource;
use App\Http\Resources\Store\PendingStoresResource;
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

    protected FcmService $fcmService;

    public function __construct(StoreRepository $storeRepository, UserService $userService, FcmService $fcmService)
    {
        $this->storeRepository = $storeRepository;
        $this->userService = $userService;
        $this->fcmService = $fcmService;
    }

    public function getAllStores(Request $request)
    {
        try {
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
            }
            $items = $request->query('items', 20);
            $stores = $this->storeRepository->getAll($items);

            $data = [
                'Stores' => StoreResource::collection($stores),
                'total_pages' => $stores->lastPage(),
                'current_page' => $stores->currentPage(),
                'hasMorePages' => $stores->hasMorePages(),
            ];
            $response = ResponseHelper::jsonResponse($data, 'Stores retrieved successfully');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }

    public function getPendingStores(Request $request)
    {
        try {
            if (! $this->checkSuperAdmin()) {
                return ResponseHelper::jsonResponse([], "You aren't Super Admin", 403, false);
            }
            $items = $request->query('items', 20);
            $stores = $this->storeRepository->getPending($items);

            $data = [
                'Stores' => PendingStoresResource::collection($stores),
                'total_pages' => $stores->lastPage(),
                'current_page' => $stores->currentPage(),
                'hasMorePages' => $stores->hasMorePages(),
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
            if ($store->status === 'pending') {
                if ($this->checkSuperAdmin()) {
                    return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : We are waiting your response for creating this store .', 404, false);
                } else {
                    return ResponseHelper::jsonResponse([], 'We are waiting for Super Admin response to create this store .', 404, false);
                }
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
        if ($store->status === 'pending') {
            if ($this->checkSuperAdmin()) {
                return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : We are waiting your response for creating this store .', 404, false);
            } else {
                return ResponseHelper::jsonResponse([], 'We are waiting for Super Admin response to create this store .', 404, false);
            }
        }
        $data = ['Store' => StoreResource::make($store)];

        return ResponseHelper::jsonResponse($data, 'Store retrieved successfully!');
    }

    public function createStore(array $data, Request $request): JsonResponse
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();

            if (auth()->user()->role->role_id === 'user') {

                $data['user_id'] = auth()->id();
                $data['status'] = 'pending';
                $path = $request->hasFile('image') ? $request->file('image')->store('images', 'public') : null;
                $data['image'] = $path;
                $store = $this->storeRepository->create($data);
                $this->fcmService->notifySuperAdminForApproval($store, $request->header('lang', 'en'));

                return ResponseHelper::jsonResponse([], 'We have sent your request to the SuperAdmin and are waiting for his response.', 202, true);
            } else {
                $this->checkAdmin('Store', 'create');
            }
            $data['user_id'] = auth()->id();
        } else {
            $data['user_id'] = $data['user_id'] ?? auth()->id();
        }

        $path = $request->hasFile('image') ? $request->file('image')->store('images', 'public') : null;
        $data['image'] = $path;

        $stores = $this->storeRepository->findByUserId($data['user_id']);
        if ($stores->isEmpty()) {
            $this->validateStoreData($data);

            $store = $this->storeRepository->create($data);
            $this->fcmService->notifyUsers($store, $request->header('lang', 'en'));
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
            $lang = $request->header('lang', 'en');
            $nameColumn = $lang === 'ar' ? 'name_ar' : 'name_en';
            $validColumns = [$nameColumn, 'location', 'created_at', 'updated_at'];
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
            $stores = $this->storeRepository->orderBy($column, $direction, $items);
            $data = [
                'Stores' => StoreResource::collection($stores),
                'total_pages' => $stores->lastPage(),
                'current_page' => $stores->currentPage(),
                'hasMorePages' => $stores->hasMorePages(),
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
                if (isset($data['user_id'])) {
                    unset($data['user_id']);
                }
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
            if ($store->status === 'pending') {
                if ($this->checkSuperAdmin()) {
                    return ResponseHelper::jsonResponse([], "Mr.SuperAdmin : You can't delete this store before receive your response about creating this store .", 404, false);
                } else {
                    return ResponseHelper::jsonResponse([], "You can't delete this store before receive Super Admin response about creating this store .", 404, false);
                }
            }
            $this->storeRepository->delete($store);
            $response = ResponseHelper::jsonResponse([], 'Store deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function handleStoreApproval(Store $store, RequestNotification $request)
    {
        if ($store->status === 'approved') {
            return ResponseHelper::jsonResponse([], 'Mr. Super Admin : We have this store in our System.', 404, false);
        }
        $data = $request->validated();
        $lang = $request->header('lang', 'en');

        if ($data['response'] === 'approved') {
            $updateData = [
                'status' => 'approved',
            ];
            $this->storeRepository->update($store, $updateData);

            $title = $lang === 'ar' ? 'تمت الموافقة على إنشاء المتجر' : 'Approved to create a store';
            $body = $lang === 'ar'
                ? " تمت الموافقة على إنشاء المتجر الخاص بك: {$store->name_ar}. يمكنك الآن رؤيته وإضافة المنتجات إليه."
                : "Your request to create your store {$store->name_en} has been approved. You can now view it and add products to it.";
        } elseif ($data['response'] === 'rejected') {
            $reason = $lang === 'ar' ? $data['reason_ar'] : $data['reason_en'];
            $this->storeRepository->delete($store);

            $title = $lang === 'ar' ? 'تم رفض إنشاء المتجر' : 'Rejected to create a store';
            $body = $lang === 'ar'
                ? " تم رفض إنشاء المتجر الخاص بك: {$store->name_ar}. السبب: {$reason}."
                : "Your request to create your store {$store->name_en} has been rejected. Reason: {$reason}.";
        }

        $this->fcmService->sendNotification($store->user->fcm_token, $title, $body,
            ['store_id' => $store->id, 'status' => $store->status]
        );

        return ResponseHelper::jsonResponse([], 'Response recorded successfully');
    }

    public function validateStoreData(array $data, $rule = 'required'): void
    {
        $allowedAttributes = ['name_ar', 'name_en', 'image', 'location', 'user_id'];

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
            'name_en' => "$rule|string|unique:stores,name_en",
            'name_ar' => "$rule|string|unique:stores,name_ar",
            'image' => 'sometimes|nullable',
            'location' => 'sometimes|string|nullable',
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
