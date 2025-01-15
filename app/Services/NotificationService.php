<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\User\NotificationResource;
use App\Models\User\Notification;
use App\Repositories\NotificationRepository;
use App\Traits\AuthTrait;

class NotificationService
{
    use AuthTrait;

    protected NotificationRepository $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function getAllNotifications()
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();
        }
        $notifications = $this->notificationRepository->getAll();

        $data = [
            'Notifications' => NotificationResource::collection($notifications),
        ];

        return ResponseHelper::jsonResponse($data, 'Notifications retrieved successfully');
    }

    public function getNotificationById(Notification $notification)
    {
        $this->checkGuest();
        $this->checkOwnership($notification, 'Notification', 'perform');
        $data = ['notification' => Notification::make($notification)];

        return ResponseHelper::jsonResponse($data, 'Notification retrieved successfully!');
    }

    public function deleteNotification(Notification $notification)
    {
        $this->checkGuest();
        $this->checkOwnership($notification, 'Notification', 'delete');
        $this->notificationRepository->delete($notification);

        return ResponseHelper::jsonResponse([], 'Notification deleted successfully!');
    }

    public function deleteAllNotification()
    {
        $this->checkGuest();
        $this->notificationRepository->deleteAll();

        return ResponseHelper::jsonResponse([], 'Notification deleted successfully!');
    }
}
