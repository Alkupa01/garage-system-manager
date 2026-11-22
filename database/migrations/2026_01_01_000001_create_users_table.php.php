<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('email', 120)->unique();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'tenant'])->default('tenant');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('ktp_image_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};