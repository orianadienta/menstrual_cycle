<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.-
     */
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('category'); //untuk kategori misalnya kesehatan, preventif, motivasi (edukasi)
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->timestamps();

            $table->index(['user_id', 'created_at']); // Ambil rekomendasi terbaru
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
