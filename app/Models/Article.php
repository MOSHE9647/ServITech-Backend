<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'price', 'category_id', 'subcategory_id'];


    /**
     * Polymorphic relationship: An article can have multiple images.
     * 
     * Example usage:
     * 
     * Retrieve a repair request by ID:
     * ```php
     * $repairRequest = RepairRequest::find(1);
     * ```
     * 
     * Get all images associated with this repair request:
     * ```php
     * $images = $repairRequest->images;
     * ```
     * 
     * Attach a new image to the repair request:
     * ```php
     * $repairRequest->images()->create(['path' => 'storage/images/repair_1.jpg']);
     * ```
     */
    public function images() {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

}
