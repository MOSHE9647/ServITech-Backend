<?php

namespace App\Models;

use App\Enums\RepairStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairRequest extends Model
{
    /** @use HasFactory<\Database\Factories\RepairRequestFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * This means you can use the create() method to insert
     * data ONLY into these fields.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be mutated to dates.
     * This means that when you retrieve these attributes from the database,
     * they will be automatically converted to Carbon instances.
     * @var array
     */
    protected $dates = ['received_at', 'repaired_at'];

    /**
     * The attributes that should be cast to native types.
     * This means that when you retrieve these attributes from the database,
     * they will be automatically converted to the specified types.
     * For example, 'repair_price' will be cast to a decimal with 2 decimal places,
     * and 'repair_status' will be cast to the RepairStatus enum.
     * @var array
     */
    protected $casts = [
        'repair_price' => 'decimal:2',
        'repair_status' => RepairStatus::class,
    ];

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
     * This method generates a unique numeric identifier and formats it as RR-{number}.
     */
    public static function generateReceiptNumber() {
        // Generate a unique numeric identifier based on current timestamp and random number
        $timestamp = now()->format('YmdHisv'); // 17 digits: YYYYMMDDHHMMSSmmm
        $random = mt_rand(1000, 999999); // 6 digits
        $uniqueNumber = "$timestamp$random"; // 23 digits total

        // Create the receipt number with the format RR-{Number}
        return "RR-$uniqueNumber";
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
