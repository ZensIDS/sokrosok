<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bins', function (Blueprint $table) {
            // Admin menekan "Reset WiFi" -> requested_at terisi.
            // Perangkat mengambil perintah itu saat bangun -> delivered_at terisi.
            $table->timestamp('wifi_reset_requested_at')->nullable()->after('last_reported_at');
            $table->timestamp('wifi_reset_delivered_at')->nullable()->after('wifi_reset_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('bins', function (Blueprint $table) {
            $table->dropColumn(['wifi_reset_requested_at', 'wifi_reset_delivered_at']);
        });
    }
};