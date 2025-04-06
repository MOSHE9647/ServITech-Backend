<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    // Fillable properties for mass assignment
    // These are the attributes that are mass assignable.
    // This means you can use the create() method to insert data into these fields.
    // For example:
    // Article::create(['name' => 'Sample Article', 'description' => 'Sample Description']);
    // This will insert a new article with the name and description provided.
    protected $fillable = ['path'];

    /**
     * Polymorphic relationship: This image can belong to different models.
     * 
     * Example usage:
     * 
     * Get the model (RepairRequest, Article, etc.) that owns this image
     * ```php
     * $owner = $image->imageable;
     * ```
     */
    public function imageable() {
        return $this->morphTo();
    }
}
