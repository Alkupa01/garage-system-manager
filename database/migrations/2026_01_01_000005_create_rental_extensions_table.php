<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('previous_garage_id')->constrained('garages')->restrictOnDelete();
            $table->foreignId('new_garage_id')->constrained('garages')->restrictOnDelete();
            $table->enum('extension_type', ['daily', 'monthly']);
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->integer('added_days');
            $table->decimal('daily_rate_snapshot', 12, 2);
            $table->decimal('additional_cost', 12, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
    }
};