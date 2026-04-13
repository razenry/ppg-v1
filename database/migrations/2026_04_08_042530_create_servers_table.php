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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->string('identifier')->unique(); // Unique per domain, but unique here for simplicity
            $table->string('src_ip');
            $table->unsignedInteger('src_port');
            $table->string('dest_ip')->nullable();
            $table->unsignedInteger('dest_port')->nullable();
            $table->foreignId('node_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, active, failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
