<?php

namespace App\Services;

use App\Models\Product\Product;
use App\Models\Store\Store;
use App\Repositories\UserRepository;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    protected $messaging;

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $firebase = (new Factory)
            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));

        $this->messaging = $firebase->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, array $data = [])
    {
        $notification = Notification::create($title, $body);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification)
            ->withData($data);
        \Log::info($title);
        return $this->messaging->send($message);
    }

    public function notifyUsers(Store $store)
    {
        $user = auth()->user();

        $title = 'New Store Added';
        $user_name = $user->first_name . ' ' . $user->last_name;
        $role = $user->role->role;
        $roleLabel = $role === 'super_admin' ? 'SuperAdmin' : ucfirst($role);
        $body = "{$roleLabel} {$user_name} has added a new Store {$store->name}.";
        \Log::info($body);
        $users = $this->userRepository->getAllUsersHasFcmToken();
        foreach ($users as $user) {
            $this->sendNotification($user->fcm_token, $title, $body,['store_id' => $store->id]);
        }
    }
    public function notifyFavoriteProductUsers(Product $product, $action)
    {
        $user = auth()->user();

        $title = "Product {$action}";
        $user_name = $user->first_name . ' ' . $user->last_name;
        $role = $user->role->role;
        $roleLabel = $role === 'super_admin' ? 'SuperAdmin' : ucfirst($role);
        $body = "{$roleLabel} {$user_name} has {$action} the product {$product->name}.";
        \Log::info($body);

        $users = $this->userRepository->getAllUsersHasFcmToken()->filter(function ($user) use ($product) {
            return $user->favoriteProducts->contains($product->id);
        });

        foreach ($users as $user) {
            $this->sendNotification($user->fcm_token, $title, $body, [
                'product_id' => $product->id,
                'action' => $action,
            ]);
        }
    }
}
