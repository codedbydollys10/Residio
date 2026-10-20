<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_bill_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained('societies')->cascadeOnDelete();
            $table->foreignId('flat_id')->constrained('flats')->cascadeOnDelete();
            $table->foreignId('resident_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supersedes_id')->nullable()->constrained('maintenance_bill_documents')->nullOnDelete();
            $table->string('period', 7);
            $table->string('reference_number')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('storage_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size');
            $table->enum('status', ['available', 'superseded', 'cancelled'])->default('available');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(['society_id', 'flat_id', 'period']);
            $table->index(['society_id', 'resident_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_bill_documents');
    }
};
