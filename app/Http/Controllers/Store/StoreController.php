<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->middleware('auth:api');
        $this->storeService = $storeService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->storeService->getMyStores($request);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->storeService->createStore($request->all(), $request);
    }

    public function show(Store $store): JsonResponse
    {
        return $this->storeService->getMyStoreById($store);
    }

    public function orderBy($column, $direction, Request $request): JsonResponse
    {
        return $this->storeService->getStoresOrderedBy($column, $direction, $request);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        return $this->storeService->updateStore($store, $request->all());
    }

    public function destroy(Store $store): JsonResponse
    {
        return $this->storeService->deleteStore($store);
    }
}
