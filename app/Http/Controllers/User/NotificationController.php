<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        return $this->notificationService->getAllNotifications($request);
    }

    public function show(Notification $notification): JsonResponse
    {
        return $this->notificationService->getNotificationById($notification);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        return $this->notificationService->deleteNotification($notification);
    }

    public function destroy_all(): JsonResponse
    {
        return $this->notificationService->deleteAllNotification();
    }
}
