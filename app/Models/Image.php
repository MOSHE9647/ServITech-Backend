<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

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
