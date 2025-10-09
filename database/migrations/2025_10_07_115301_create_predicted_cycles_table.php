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
        Schema::create('predicted_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('predicted_start_date');
            $table->date('predicted_end_date')->nullable();
            $table->date('fertile_window_start')->nullable();
            $table->date('fertile_window_end')-> nullable();
            $table->date('ovulation_date')-> nullable();
            $table->timestamp('generated_at')->useCurrentOnUpdate();
            $table->timestamps();

            $table->index(['user_id', 'generated_at']); // Ambil prediksi terakhir
            $table->index('predicted_start_date'); // Untuk schedule job notifikasi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predicted_cycles');
    }
};
