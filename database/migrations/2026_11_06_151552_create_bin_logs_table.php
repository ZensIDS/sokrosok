<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bin_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('fill_level_percent')->nullable();
            $table->decimal('distance_cm', 6, 1)->nullable();
            $table->string('status', 10);
            $table->boolean('sensor_error')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['bin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_logs');
    }
};