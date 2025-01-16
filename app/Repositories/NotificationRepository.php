<?php

namespace App\Repositories;

use App\Models\User\Notification;
use App\Models\User\User;
use App\Traits\Lockable;

class NotificationRepository
{
    use Lockable;

    public function getAll($items)
    {
        $user=User::where('id', auth()->id())->first();
        return Notification::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate($items);
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
            $user = User::find(auth()->id());
            $userNotifications = $lockedNotifications->where('user_id', $user->id);
           return Notification::whereIn('id', $userNotifications->pluck('id'))->delete();

        });
    }
}
