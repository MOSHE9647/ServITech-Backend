<?php

namespace App\Http\Requests\SubcategoryRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'sometimes|required|exists:categories,id',
        ];
    }
}
