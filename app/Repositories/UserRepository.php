<?php

namespace App\Repositories;

use App\Models\User\User;

class UserRepository {
    public function getAllUsersHasFcmToken()
    {
        return User::where('id','!=', auth()->id())
            ->whereNotNull('fcm_token')
            ->get();
    }
}
