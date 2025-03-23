<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcategory extends Model
{
    /** @use HasFactory<\Database\Factories\SubcategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "name",
        "description",
        "category_id",
    ];

    protected $casts = [
    
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
