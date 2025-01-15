<?php

namespace App\Repositories;

use App\Models\User\Notification;
use App\Traits\Lockable;

class NotificationRepository
{
    use Lockable;

    public function getAll()
    {
        return Notification::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get();
    }

    public function create(array $data)
    {
        return $this->lockForCreate(function () use ($data) {
            return Notification::create($data);
        });
    }

    public function delete(Notification $notification)
    {
        return $this->lockForDelete(Notification::class, $notification->id, function ($lockedNotification) {
            return $lockedNotification->delete();
        });
    }

    public function deleteAll()
    {
        return $this->lockForDeleteAll(Notification::class, function ($lockedNotifications) {
            return $lockedNotifications->where('user_id', auth()->id())->delete();
        });
    }
}
