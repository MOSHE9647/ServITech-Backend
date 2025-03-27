<?php

namespace App\Http\Requests\RepairRequest;

use App\Enums\RepairStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateRepairRequest extends FormRequest
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
            'customer_phone'        => 'required|string|min:8',
            'customer_email'        => 'required|email',
            'article_name'          => 'required|string|min:3',
            'article_type'          => 'required|string|min:3',
            'article_brand'         => 'required|string|min:2',
            'article_model'         => 'required|string|min:2',
            'article_serialnumber'  => 'nullable|string|min:6',
            'article_accesories'    => 'nullable|string|min:3',
            'article_problem'       => 'required|string|min:3',
            'repair_status'         => [
                'required', 'string',
                new Enum(RepairStatus::class)
            ],
            'repair_details'        => 'nullable|string|min:3',
            'repair_price'          => 'nullable|numeric',
            'received_at'           => 'required|date',
            'repaired_at'           => 'nullable|date',
         ];


    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_phone.required' => __('validation.required', [
                'attribute' => __('validation.attributes.phone')
            ]),
            'customer_phone.string' => __('validation.string', [
                'attribute' => __('validation.attributes.phone')
            ]),
            'customer_phone.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.phone'),
                'min' => 8
            ]),
            'customer_email.required' => __('validation.required', [
                'attribute' => __('validation.attributes.email')
            ]),
            'customer_email.email' => __('validation.email', [
                'attribute' => __('validation.attributes.email')
            ]),
            'article_name.required' => __('validation.required', [
                'attribute' => __('validation.attributes.article_name')
            ]),
            'article_name.string' => __('validation.string', [
                'attribute' => __('validation.attributes.article_name')
            ]),
            'article_name.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.article_name'),
                'min' => 3
            ]),
            'article_type.required' => __('validation.required', [
                'attribute' => __('validation.attributes.article_type')
            ]),
            'article_type.string' => __('validation.string', [
                'attribute' => __('validation.attributes.article_type')
            ]),
            'article_type.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.article_type'),
                'min' => 3
            ]),
            'article_brand.required' => __('validation.required', [
                'attribute' => __('validation.attributes.article_brand')
            ]),
            'article_brand.string' => __('validation.string', [
                'attribute' => __('validation.attributes.article_brand')
            ]),
            'article_brand.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.article_brand'),
                'min' => 2
            ]),
            'article_model.required' => __('validation.required', [
                'attribute' => __('validation.attributes.article_model')
            ]),
            'article_model.string' => __('validation.string', [
                'attribute' => __('validation.attributes.article_model')
            ]),
            'article_model.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.article_model'),
                'min' => 2
            ]),
            'article_serialnumber.string' => __('validation.string', [
                'attribute' => __('validation.attributes.serialnumber')
            ]),
            'article_serialnumber.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.serialnumber'),
                'min' => 6
            ]),
            'article_accesories.string' => __('validation.string', [
                'attribute' => __('validation.attributes.accesories')
            ]),
            'article_accesories.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.accesories'),
                'min' => 3
            ]),
            'article_problem.required' => __('validation.required', [
                'attribute' => __('validation.attributes.article_problem')
            ]),
            'article_problem.string' => __('validation.string', [
                'attribute' => __('validation.attributes.article_problem')
            ]),
            'article_problem.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.article_problem'),
                'min' => 3
            ]),
            'repair_status.enum' => __('validation.enum', [
                'attribute' => __('validation.attributes.repair_status'),
                'values' => implode(', ', array_column(RepairStatus::cases(), 'value'))
            ]),
            'repair_details.string' => __('validation.string', [
                'attribute' => __('validation.attributes.repair_details')
            ]),
            'repair_details.min' => __('validation.min.string', [
                'attribute' => __('validation.attributes.repair_details'),
                'min' => 3
            ]),
            'repair_price.numeric' => __('validation.numeric', [
                'attribute' => __('validation.attributes.repair_price')
            ]),
            'received_at.required' => __('validation.required', [
                'attribute' => __('validation.attributes.received_at')
            ]),
            'received_at.date' => __('validation.date', [
                'attribute' => __('validation.attributes.received_at')
            ]),
            'repaired_at.date' => __('validation.date', [
                'attribute' => __('validation.attributes.repaired_at')
            ]),
        ];
    }
}
