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
        \Log::info($body);

        return $this->messaging->send($message);
    }

    public function notifyUsers(Store $store, $lang = 'en')
    {
        $user = auth()->user();
        $title = $lang === 'ar' ? 'تمت إضافة متجر جديد' : 'New Store Added';
        $user_name = $user->first_name.' '.$user->last_name;
        $role = $user->role->role;
        $roleLabel = $role === 'super_admin' ? ($lang === 'ar' ? 'المسؤول العام' : 'SuperAdmin') : ucfirst($role);

        if ($role === 'super_admin') {
            $body = $lang === 'ar'
                ? " قام المسؤول العام بإضافة متجر جديد {$store->name_ar}."
                : "SuperAdmin has added a new Store {$store->name_en}.";
        } else {
            $body = $lang === 'ar'
                ? "{$roleLabel} {$user_name} قام بإضافة متجر جديد {$store->name_ar}."
                : "{$roleLabel} {$user_name} has added a new Store {$store->name_en}.";
        }

        $users = $this->userRepository->getAllUsersHasFcmToken();
        foreach ($users as $user) {
            $this->sendNotification($user->fcm_token, $title, $body, ['store_id' => $store->id]);
        }
    }

    public function notifyFavoriteProductUsers(Product $product, $action, $lang = 'en')
    {
        $user = auth()->user();
        $title = $lang === 'ar'
            ? ($action === 'updated' ? 'تم تحديث المنتج' : 'تم حذف المنتج')
            : "Product {$action}";
        $user_name = $user->first_name.' '.$user->last_name;
        $role = $user->role->role;
        $roleLabel = $role === 'super_admin' ? ($lang === 'ar' ? 'المسؤول العام' : 'SuperAdmin') : ucfirst($role);

        $actionText = $lang === 'ar'
            ? ($action === 'updated' ? 'بتحديث' : 'بحذف')
            : $action;

        if ($role === 'super_admin') {
            $body = $lang === 'ar'
                ? " قام المسؤول العام {$actionText} المنتج {$product->name_ar}."
                : "SuperAdmin has {$action} the product {$product->name_en}.";
        } else {
            $body = $lang === 'ar'
                ? "{$roleLabel} {$user_name} قام {$actionText} المنتج {$product->name_ar}."
                : "{$user_name} has {$action} the product {$product->name_en}.";
        }
        \Log::info($body);
        $users = $this->userRepository->getAllUsersHasFcmToken()->filter(function ($user) use ($product) {
            return $user->favoriteProducts->contains($product->id);
        });
        \Log::info($users);
        foreach ($users as $user) {
            $this->sendNotification($user->fcm_token, $title, $body, [
                'product_id' => $product->id,
                'action' => $action,
            ]);
        }
    }
}
