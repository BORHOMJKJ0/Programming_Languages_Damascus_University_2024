<?php

namespace App\Repositories;

use App\Models\User\User;

class UserRepository
{
    public function findById($id)
    {
        return User::where('id', $id)->first();
    }
}
