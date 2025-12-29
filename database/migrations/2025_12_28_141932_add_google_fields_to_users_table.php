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
        Schema::table('users', function (Blueprint $table) {
            // Tambah kolom google_id setelah kolom email
            $table->string('google_id')->nullable()->unique()->after('email');
            
            // Tambah kolom avatar setelah kolom google_id
            $table->string('avatar')->nullable()->after('google_id');
            
            // Kalau kolom email_verified_at belum ada, uncomment baris ini:
            $table->timestamp('email_verified_at')->nullable()->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar']);
            
            // Kalau tadi tambah email_verified_at, uncomment:
            $table->dropColumn('email_verified_at');
        });
    }
};
