<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->enum('type', ['S', 'C', 'E']);
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('kwh_rate', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};