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
        Schema::create('symptom_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // $table->foreignId('cycle_id')->constrained('cycles')->onDelete('cascade')->nullable();
            $table->foreignId('symptom_id')->constrained('symptoms')->onDelete('cascade');
            $table->timestamp('log_date');
            $table->timestamps();

            $table->index(['user_id', 'log_date']); // Calendar view
            // $table->index('cycle_id'); // Filter per cycle

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('symptom_logs');
    }
};
