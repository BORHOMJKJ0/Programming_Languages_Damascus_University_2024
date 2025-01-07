<?php

namespace App\Repositories;

use App\Models\User\User;
use App\Traits\Lockable;

class UserRepository
{
    use Lockable;

    public function getAllUsersHasFcmToken()
    {
        return User::where('id', '!=', auth()->id())
            ->whereNotNull('fcm_token')
            ->get();
    }

    public function getSuperAdmin()
    {
        return User::whereHas('role', function ($query) {
            $query->where('role', 'super_admin');
        })->first();
    }

    public function delete(User $user)
    {
        return $this->lockForDelete(User::class, $user->id, function ($lockedUser) {
            return $lockedUser->delete();
        });
    }
}
