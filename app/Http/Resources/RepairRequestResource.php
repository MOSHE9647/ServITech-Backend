<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'receipt_number'       => $this->receipt_number,
            'customer_name'        => $this->customer_name,
            'customer_phone'       => $this->customer_phone,
            'customer_email'       => $this->customer_email,
            'article_name'         => $this->article_name,
            'article_type'         => $this->article_type,
            'article_brand'        => $this->article_brand,
            'article_model'        => $this->article_model,
            'article_serialnumber' => $this->article_serialnumber,
            'article_accesories'   => $this->article_accesories,
            'article_problem'      => $this->article_problem,
            'repair_status'        => $this->repair_status,
            'repair_details'       => $this->repair_details,
            'repair_price'         => $this->repair_price,
            'received_at'          => $this->received_at,
            'repaired_at'          => $this->repaired_at,
            'images'               => ImageResource::collection(
                $this->whenLoaded('images')
            ),
        ];
    }
}
