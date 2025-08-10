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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->bigInteger('size'); // in bytes
            $table->string('hash')->nullable();
            $table->enum('type', ['requirement', 'deliverable', 'sample'])->default('requirement');
            $table->boolean('is_processed')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'order_id']);
            $table->index(['type', 'is_processed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
