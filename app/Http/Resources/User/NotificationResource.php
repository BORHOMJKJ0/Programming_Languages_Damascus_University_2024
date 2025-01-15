<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'en');

        return [
            'id' => $this->id,
            'title' => $lang === 'ar' ? $this->title_ar : $this->title_en,
            'body' => $lang === 'ar' ? $this->body_ar : $this->body_en,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
