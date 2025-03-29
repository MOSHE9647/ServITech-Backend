<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\Admin\CategoryFactory> */
    use HasFactory, SoftDeletes; 

    // Fillable properties for mass assignment
    // These are the attributes that are mass assignable.
    // This means you can use the create() method to insert data into these fields.
    // For example:
    // Article::create(['name' => 'Sample Article', 'description' => 'Sample Description']);
    // This will insert a new article with the name and description provided.
    protected $fillable = ['name', 'description'];

    /**
     * Define a one-to-many relationship with the Subcategory model.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Subcategory, Category>
     */
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    /**
     * Define a one-to-many relationship with the Article model.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Article, Category>
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
