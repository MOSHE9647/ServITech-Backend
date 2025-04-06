<?php

namespace App\Http\Requests\ArticleRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'              => 'required|string|min:3',
            'description'       => 'required|string|min:10|max:255',
            'price'             => 'required|numeric|min:0',
            'category_id'       => 'required|exists:categories,id',
            'subcategory_id'    => 'required|exists:subcategories,id',
            'images'            => 'nullable|array',
            'images.*'          => 'image|mimes:jpeg,png,jpg|max:2048', // Máximo 2MB por imagen
        ];
    }

    /**
     * Get the validation error messages.
     * @return array{category_id.exists: array|string|null, category_id.min: array|string|null, category_id.required: array|string|null, category_id.string: array|string|null, images.*.image: array|string|null, images.*.max: array|string|null, images.*.mimes: array|string|null, subcategory_id.exists: array|string|null, subcategory_id.min: array|string|null, subcategory_id.required: array|string|null, subcategory_id.string: array|string|null}
     */
    public function messages(): array
    {
        return [
            'category_id.required' => __('validation.required', ['attribute' => __('validation.attributes.category')]),
            'category_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.category')]),
            'category_id.string' => __('validation.string'),
            'category_id.min' => __('validation.min.string'),
            'subcategory_id.required' => __('validation.required', ['attribute' => __('validation.attributes.subcategory')]),
            'subcategory_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.subcategory')]),
            'subcategory_id.string' => __('validation.string'),
            'subcategory_id.min' => __('validation.min.string'),
            'images.*.image' => __('validation.image', ['attribute'=> __('validation.attributes.image')]),
            'images.*.mimes' => __('validation.mimes', ['attribute'=> __('validation.attributes.image'), 'values' => 'jpeg,png,jpg']),
            'images.*.max' => __('validation.max.file', ['attribute'=> __('validation.attributes.image')]),
        ];
    }
}
