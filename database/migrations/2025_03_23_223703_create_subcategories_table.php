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
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Subcategory name
            $table->text('description')->nullable(); // Subcategory description
            $table->softDeletes(); // Deleted_At
            $table->unique(['category_id', 'name']); // Indicates that the combination of category_id and name must be unique
            
            $table->timestamps(); // Created_At and Updated_At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategories');
    }
};
