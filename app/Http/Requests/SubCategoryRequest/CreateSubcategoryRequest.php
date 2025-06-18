<?php

namespace App\Http\Requests\SubcategoryRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cambiar si necesitas control por roles
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
        ];
    }
}
