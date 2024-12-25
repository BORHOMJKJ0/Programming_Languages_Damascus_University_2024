<?php

namespace App\Services;

use App\Models\Order\Order;
use App\Models\Order\Order_items;
use App\Models\Product\Product;
use App\Models\Store\Store;
use App\Repositories\StoreRepository;
use App\Repositories\UserRepository;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    protected $messaging;

    protected $userRepository;
    protected $storeRepository;

    public function __construct(UserRepository $userRepository, StoreRepository $storeRepository)
    {
        $this->userRepository = $userRepository;
        $this->storeRepository = $storeRepository;
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

    public function notifyPlaceOrder(Order $order, $lang = 'en')
    {
        $user = auth()->user();
        $user_name = $user->first_name.' '.$user->last_name;

        if($lang === 'en') {
            $title = 'New Order';
            $body = "A new order has been received from the customer {$user_name}.";
        }
        else{
            $title = 'طلب جديد';
            $body = "تم استلام طلب جديد من العميل {$user_name}.";
        }

        $data = [
            'order_number' => $order->id,
            'location' => $user->location
        ];

        $deviceToken = $order->store()->user->fcm_token;

        $this->sendNotification($deviceToken, $title, $body, $data);
    }

    public function notifyٍStoreItem(Order_items $item, $action, $lang = 'en')
    {
        $user = auth()->user();
        $user_name = $user->first_name.' '.$user->last_name;

        if($lang === 'en') {
            $title = ucfirst($action)." Item";
            $body = "The customer {$user_name} has {$action}d the item \"{$item->product->name_en}\" in order number {$item->order->id}.";
        }
        else{
            if($action === 'update') {
                $title = "تعديل عنصر";
                $body = "قام العميل {$user_name} بتعديل العنصر \"{$item->product->name_ar}\" في الطلب رقم {$item->order->id}.";
            }
            else{
                $title = "حذف عنصر";
                $body = "قام العميل {$user_name} بحذف العنصر \"{$item->product->name_ar}\" في الطلب رقم {$item->order->id}.";
            }

        }

        $data = [
            'order_number' => $item->order->id,
        ];
        $deviceToken = $item->order->store()->user->fcm_token;

        $this->sendNotification($deviceToken, $title, $body, $data);
    }

    public function notifyCustomerItem(Order_items $item, $action, $lang = 'en')
    {
        $store = $item->product->store;

        if($lang === 'en') {
        $title = $store->name_en;
        switch ($action){
            case 'accept' : $body = "The item \"{$item->product->name_en}\" has been accepted, and it is being prepared.";break;
            case 'reject' : $body = "We apologize, the item \"{$item->product->name_en}\" has been rejected."; break;
            case 'not available' : $body = "We apologize, the requested quantity of the item \"{$item->product->name_en}\" is not available."; break;
            case 'ship' : $body = "The item \"{$item->product->name_en}\" has been shipped and is on its way to the specified location."; break;
            case 'deliver' : $body = "The item \"{$item->product->name_en}\" has been delivered. Thank you for your order."; break;
            case 'cancel' : $body = "We apologize, the item \"{$item->product->name_en}\"has been canceled."; break;
        }
    }
        else{
            $title = $store->name_ar;
            switch ($action){
                case 'accept' : $body = "تم قبول العنصر \"{$item->product->name_ar}\", وجار العمل على تجهيزه."; break;
                case 'reject' : $body = "نعتذر, تم رفض العنصر \"{$item->product->name_ar}\"."; break;
                case 'not available' : $body = "نعتذر، الكمية المطلوبة من العنصر \"{$item->product->name_ar}\" غير متوفرة."; break;
                case 'ship' : $body = "تم شحن العنصر \"{$item->product->name_ar}\", وهو في الطريق إلى العنوان المحدد."; break;
                case 'deliver' : $body = "تم توصيل العنصر \"{$item->product->name_ar}\", شكراً لطلبك."; break;
                case 'cancel' : $body = "نعتذر، تم إلغاء العنصر \"{$item->product->name_ar}\"."; break;
            }
        }
        $data = [
            'order_id' => $item->order->id,
        ];

        $deviceToken = $item->order->user->fcm_token;

        $this->sendNotification($deviceToken, $title, $body, $data);
    }
}
