<?php

namespace App\Http\Requests;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $numericFields = ['price', 'amount', 'quantity'];
        $column = $this->input('column');
        $model = $this->route('model');

        $this->validateModel($model);

        $availableFields = $this->getAvailableFields($model);

        $rules = [
            'column' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($model, $availableFields) {
                    if (! in_array($value, $availableFields)) {
                        $fail("$value not available for $model. Available fields for $model are: ".implode(', ', $availableFields));
                    }
                },
            ],
            function ($attribute, $value, $fail) use ($numericFields, $column) {
                if (in_array($column, $numericFields)) {
                    if (! $this->input('min') && ! $this->input('max')) {
                        $fail("For numeric column '{$column}', at least one of 'min' or 'max' must be provided.");
                    }
                } else {
                    if (! $this->input('value')) {
                        $fail("For string column '{$column}', the 'value' field must be provided.");
                    }
                }
            },
        ];

        if (in_array($column, $numericFields)) {
            $rules = array_merge($rules, $this->numericFieldRules());
        } else {
            $rules = array_merge($rules, $this->stringFieldRules());
        }

        return $rules;
    }

    private function validateModel($model)
    {
        $validModels = ['users', 'categories', 'stores', 'products', 'cart_items'];
        if (! in_array($model, $validModels)) {
            $closest = $this->getClosestMatch($model, $validModels);
            throw new HttpResponseException(
                ResponseHelper::jsonResponse(
                    [],
                    "Invalid model '$model'. Did you mean '$closest'?",
                    400,
                    false
                )
            );
        }
    }

    private function getClosestMatch($input, $options)
    {
        $closest = null;
        $shortest = -1;

        foreach ($options as $option) {
            $lev = levenshtein($input, $option);

            if ($shortest == -1 || $lev < $shortest) {
                $closest = $option;
                $shortest = $lev;
            }
        }

        return $closest;
    }

    private function getAvailableFields($model)
    {
        $modelFields = [
            'users' => ['first_name', 'last_name', 'full_name', 'location'],
            'categories' => ['name'],
            'stores' => ['name', 'location'],
            'products' => ['name', 'description', 'price', 'amount'],
            'cart_items' => ['quantity'],
        ];

        return $modelFields[$model] ?? [];
    }

    private function numericFieldRules()
    {
        return [
            'value' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $fail("{$attribute} is prohibited for {$this->input('column')}.");
                    }
                },
            ],
            function ($attribute, $value, $fail) {
                if (! $this->input('min') && ! $this->input('max')) {
                    $fail("At least one of min or max must be provided {$this->input('column')}.");
                }
            },
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function stringFieldRules()
    {
        return [
            'value' => [
                'required_without_all:min,max',
                'string',
            ],
            'min' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $fail("{$attribute} is not allowed for {$this->input('column')}.");
                    }
                },
            ],
            'max' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $fail("'{$attribute}' is not allowed for '{$this->input('column')}'.");
                    }
                },
            ],
        ];
    }
}
