<?php

namespace App\Http\Requests\RepairRequest;

use App\Enums\RepairStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRepairRequest extends FormRequest
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
            'article_serialnumber'  => 'nullable|string|min:6',
            'article_accesories'    => 'nullable|string|min:3',
            'repair_status'         => [
                'required', 'string', 
                new Enum(RepairStatus::class)
            ],
            'repair_details'        => 'nullable|string|min:3',
            'repair_price'          => 'nullable|numeric',
            'repaired_at'           => 'nullable|date',
        ];
    }
}
