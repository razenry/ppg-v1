<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            if (! Schema::hasColumn('nodes', 'status')) {
                $table->string('status')->default('online');
            }
            if (! Schema::hasColumn('nodes', 'latency_ms')) {
                $table->unsignedInteger('latency_ms')->nullable();
            }
            if (! Schema::hasColumn('nodes', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn(['latency_ms', 'last_checked_at']);
        });
    }
};
