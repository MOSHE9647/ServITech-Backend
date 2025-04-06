<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory, SoftDeletes;

    // Fillable properties for mass assignment
    // These are the attributes that are mass assignable.
    // This means you can use the create() method to insert data into these fields.
    // For example:
    // Article::create(['name' => 'Sample Article', 'description' => 'Sample Description']);
    // This will insert a new article with the name and description provided.
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

    /**
     * Define a one-to-many relationship with the Category model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Category, Article>
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Define a one-to-many relationship with the Subcategory model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Subcategory, Article>
     */
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

}
