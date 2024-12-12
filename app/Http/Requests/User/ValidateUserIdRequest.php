<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class ValidateUserIdRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|exists:users,id',
        ];
    }
}
