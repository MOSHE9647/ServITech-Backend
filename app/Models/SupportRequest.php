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
    protected $fillable =[
        "date", "location", "detail",
    ];
    protected $casts = [    // convert to date
        'date' => 'datetime',
    ];

   public function user(): BelongsTo
   {
       return $this->belongsTo(User::class);
   }
}
