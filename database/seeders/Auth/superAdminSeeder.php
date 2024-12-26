<?php

namespace Database\Seeders\Auth;

use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class superAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'Password@123';

        DB::transaction(function () use ($password) {
            $superAdminRole = Role::firstOrCreate(
                ['role' => 'super_admin'],
                ['created_at' => now(), 'updated_at' => now()]
            );

            User::create([
                'role_id' => $superAdminRole->id,
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'mobile_number' => '0912345678',
                'password' => Hash::make($password),
                'password_confirmation' => Hash::make($password),
                'location' => 'Headquarters',
                'image' => null,
                'fcm_token' => null,
            ]);
        });
    }
}
