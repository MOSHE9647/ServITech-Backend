<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class RepairRequest extends Model
{
    /** @use HasFactory<\Database\Factories\RepairRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_email',
        'article_name',
        'article_type',
        'article_brand',
        'article_model',
        'article_serialnumber',
        'article_accesories',
        'article_problem',
        'repair_status',
        'repair_details',
        'repair_price',
        'received_at',
        'repaired_at',
    ];

    protected $dates = ['received_at', 'repaired_at'];

    /**
     * The "booting" method of the model.
     * This method is used to generate a unique receipt number for each new RepairRequest.
     * 
     * @return void
     */
    protected static function boot() {
        parent::boot();

        static::creating(function ($repairRequest) {
            if (empty($repairRequest->receipt_number)) {
                $repairRequest->receipt_number = self::generateReceiptNumber();
            }
        });
    }

    /**
     * Generate a unique receipt number for a new RepairRequest.
     */
    public static function generateReceiptNumber() {
        return Cache::lock('repair_request_number_lock', 5)
            ->block(3, function () {
                $key = 'repair_request_last_number';
                $lastNumber = Cache::get($key, 0) + 1;
                Cache::put($key, $lastNumber, now()->addDays(1)); // Store the last number for 1 day
                return 'RR-' . str_pad($lastNumber, 12, '0', STR_PAD_LEFT);
            }
        );
    }

    /**
     * Polymorphic relationship: A RepairRequest can have multiple images.
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
}
