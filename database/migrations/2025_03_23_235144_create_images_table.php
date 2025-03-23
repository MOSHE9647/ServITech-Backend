<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            $table->morphs('imageable'); // This will create two columns: imageable_id and imageable_type
            $table->string('path'); // The path to the image
            $table->string('alt')->nullable(); // The alt text for the image
            $table->string('title')->nullable(); // The title text for the image

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
