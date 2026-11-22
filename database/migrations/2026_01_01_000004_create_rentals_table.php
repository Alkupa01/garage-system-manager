<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('garage_id')->constrained('garages')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->enum('rental_type', ['daily', 'monthly']);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days');
            $table->decimal('daily_rate_snapshot', 12, 2);
            $table->decimal('kwh_rate_snapshot', 10, 2);
            $table->decimal('base_rental_cost', 12, 2);
            $table->decimal('charging_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2);
            $table->enum('status', ['booked', 'active', 'completed', 'cancelled'])->default('booked');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};