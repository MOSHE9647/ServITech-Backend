<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'images'      => ImageResource::collection(
                $this->whenLoaded('images')
            ),
        ];
    }
}
