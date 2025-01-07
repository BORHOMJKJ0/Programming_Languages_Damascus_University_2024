<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class IdRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_id' => 'sometimes|nullable|numeric|exists:users,id',
        ];
    }
}
