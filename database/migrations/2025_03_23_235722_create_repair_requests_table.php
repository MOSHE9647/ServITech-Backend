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
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();
            
            $table->string('receipt_number')->unique(); // Unique receipt number instead of ID
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->string('article_name');
            $table->string('article_type');
            $table->string('article_brand');
            $table->string('article_model');
            $table->string('article_serialnumber')->nullable();
            $table->text('article_accesories')->nullable();
            $table->text('article_problem');
            $table->string('repair_status')->default('Pending');
            $table->text('repair_details')->nullable();
            $table->decimal('repair_price', 10, 2)->nullable();
            $table->date('received_at');
            $table->date('repaired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
