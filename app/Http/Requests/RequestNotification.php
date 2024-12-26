<?php

namespace App\Http\Requests;

use App\Helpers\ResponseHelper;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;

class RequestNotification extends BaseRequest
{
    use AuthTrait;

    public function authorize(): bool
    {
        if (! $this->checkSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function failedAuthorization()
    {
        throw new HttpResponseException(ResponseHelper::jsonResponse([], "You aren't Super Admin", 403, false));
    }

    public function rules(): array
    {
        return [
            'response' => 'required|in:rejected,approved',
            'reason_en' => 'required_if:response,rejected|string|max:255',
            'reason_ar' => 'required_if:response,rejected|string|max:255',
        ];
    }
}
