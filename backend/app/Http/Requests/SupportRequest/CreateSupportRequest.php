<?php

namespace App\Http\Requests\SupportRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
class CreateSupportRequest extends FormRequest
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
            'date'        => 'required|date',
            'location'    => 'required|string|min:10',
            'detail'      => 'required|string|min:10',
        ];
    }
}
