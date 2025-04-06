<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcategory extends Model
{
    /** @use HasFactory<\Database\Factories\SubcategoryFactory> */
    use HasFactory, SoftDeletes;

    // Fillable properties for mass assignment
    // These are the attributes that are mass assignable.
    // This means you can use the create() method to insert data into these fields.
    // For example:
    // Article::create(['name' => 'Sample Article', 'description' => 'Sample Description']);
    // This will insert a new article with the name and description provided.
    protected $fillable = ['category_id', 'name', 'description'];

    /**
     * Define a one-to-many relationship with the Category model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Category, Subcategory>
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Define a one-to-many relationship with the Article model.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Article, Subcategory>
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
