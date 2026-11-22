<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('license_plate', 20)->nullable()->unique();
            $table->string('brand_model', 100);
            $table->enum('type', ['standard', 'charging', 'exclusive']);
            $table->decimal('battery_capacity_kwh', 8, 2)->nullable();
            $table->decimal('remaining_kwh', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};