<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportRequest extends Model
{
    /** @use HasFactory<\Database\Factories\SupportRequestFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * This means you can use the create() method to insert
     * data ONLY into these fields.
     *
     * @var list<string>
     */
    protected $fillable = ["user_id", "date", "location", "detail"];

    /**
     * The attributes that should be cast to native types.
     * This means that when you retrieve these attributes from the database,
     * they will be automatically converted to the specified types.
     * @var array
     */
    protected $casts = [    // convert to date
        'date' => 'datetime',
    ];

    /**
     * Define a one-to-many relationship with the User model.
     * This means that each support request belongs to a single user.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, SupportRequest>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
