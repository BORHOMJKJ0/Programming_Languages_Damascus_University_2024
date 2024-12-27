<?php

namespace App\Services;

use App\Models\Order\Order;
use App\Models\Order\Order_item;
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
        \Log::info($users);
        foreach ($users as $user) {
            $this->sendNotification($user->fcm_token, $title, $body, ['store_id' => $store->id]);
        }
    }

    public function notifySuperAdminForApproval(Store $store, $lang = 'en')
    {
        $superAdmin = $this->userRepository->getSuperAdmin();
        $title = $lang === 'ar' ? 'طلب الموافقة على انشاء المتجر' : 'Request approval to create a store';
        $user_body = $lang === 'ar'
            ? " تم طلب الموافقة على إنشاء المتجر الجديد {$store->name_ar}. نحن في انتظار رد المسؤول العام."
            : "Approval has been requested for creating the new store {$store->name_en}. Waiting for the Super Admin's response.";
        $SuperAdmin_body = $lang === 'ar'
            ? "المستخدم {$store->user->name} يريد إنشاء المتجر ID {$store->id}، الاسم {$store->name_ar} في النظام الخاص بك. هل تقبل أو ترفض طلب هذا المستخدم؟"
            : "The user {$store->user->name} wants to create a store ID {$store->id}, name {$store->name_en} in your system. Do you accept or reject this user’s request?";
        $data = [
            'store_id' => $store->id,
            'store_name' => $lang === 'ar' ? $store->name_ar : $store->name_en,
            'callback_url' => route('superAdmin.storeApprovalResponse', ['store' => $store->id]),
        ];

        $this->sendNotification($store->user->fcm_token, $title, $user_body, $data);
        unset($data['callback_url']);
        $this->sendNotification($superAdmin->fcm_token, $title, $SuperAdmin_body, $data);
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

        if ($lang === 'en') {
            $title = 'New Order';
            $body = "A new order has been received from the customer {$user_name}.";
        } else {
            $title = 'طلب جديد';
            $body = " تم استلام طلب جديد من العميل {$user_name}.";
        }

        $data = [
            'order_number' => $order->id,
            'location' => $user->location,
        ];

        $deviceToken = $order->store()->user->fcm_token;

        $this->sendNotification($deviceToken, $title, $body, $data);
    }

    public function notifyٍStoreItem(Order_item $item, $action, $lang = 'en')
    {
        $user = auth()->user();
        $user_name = $user->first_name.' '.$user->last_name;

        if ($lang === 'en') {
            $title = ucfirst($action).' Item';
            $body = "The customer {$user_name} has {$action}d the item \"{$item->product->name_en}\" in order number {$item->order->id}.";
        } else {
            if ($action === 'update') {
                $title = 'تعديل عنصر';
                $body = " قام العميل {$user_name} بتعديل العنصر \"{$item->product->name_ar}\" في الطلب رقم {$item->order->id}.";
            } else {
                $title = 'حذف عنصر';
                $body = " قام العميل {$user_name} بحذف العنصر \"{$item->product->name_ar}\" في الطلب رقم {$item->order->id}.";
            }

        }

        $data = [
            'order_number' => $item->order->id,
        ];
        $deviceToken = $item->order->store()->user->fcm_token;

        $this->sendNotification($deviceToken, $title, $body, $data);
    }

    public function notifyCustomerItem(Order_item $item, $action, $lang = 'en')
    {
        $store = $item->product->store;

        if ($lang === 'en') {
            $store_name = $store->name_en;
            $item_name = $item->product->name_en;
            switch ($action) {
                case 'accept':
                    $title = "Item Accepted";
                    $body = "The store {$store_name} has accepted the item {$item_name}, and it is now being prepared.";
                    break;

                case 'reject':
                    $title = "Item Rejected";
                    $body = "The store {$store_name} has rejected the item {$item_name}. We apologize.";
                    break;

                case 'not available':
                    $title = "Item Unavailable";
                    $body = "The store {$store_name} has rejected the item {$item_name} due to unavailability of the requested quantity.";
                    break;

                case 'ship':
                    $title = "Item Shipped";
                    $body = "The store {$store_name} has shipped the item {$item_name}, and it is on its way to the specified location.";
                    break;

                case 'deliver':
                    $title = "Item Delivered";
                    $body = "The store {$store_name} has delivered the item {$item_name}. Thank you for your order.";
                    break;

                case 'cancel':
                    $title = "Item Canceled";
                    $body = "The store {$store_name} has canceled the item {$item_name}. We apologize.";
                    break;
            }
        } else {
            $store_name = $store->name_ar;
            $item_name = $item->product->name_ar;
            switch ($action) {
                case 'accept':
                    $title = "تم قبول عنصر";
                    $body = "قام متجر {$store_name} بقبول العنصر {$item_name} ,ويتم الأن العمل على تحضيره.";
                    break;
                case 'reject':
                    $title = "تم رفض عنصر";
                    $body = "قام متجر {$store_name} برفض العنصر {$item_name} ,نعتذر لكم.";
                    break;
                case 'not available':
                    $title = "العنصر غير متوفر";
                    $body = "قام متجر {$store_name} برفض العنصر {$item_name} بسبب عدم توفر الكمية المطلوبة.";
                    break;
                case 'ship':
                    $title = "تم شحن عنصر";
                    $body =  "قام متجر {$store_name} بشحن العنصر {$item_name} ,وهو في طربقه إلى الموقع المحدد.";
                    break;
                case 'deliver':
                    $title = "تم توصيل عنصر";
                    $body =  "قام متجر {$store_name} بتوصيل العنصر {$item_name} ,شكراً لطلبكم.";
                    break;
                case 'cancel':
                    $title = "تم إلغاء عنصر";
                    $body =  "قام متجر {$store_name} بإلغاء العنصر {$item_name} ,نعتذر لكم.";
                    break;
            }
        }
        $data = [
            'order_id' => $item->order->id,
        ];

        $deviceToken = $item->order->user->fcm_token;

        $this->sendNotification($deviceToken, $title, $body, $data);
    }
}
