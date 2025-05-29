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
        Schema::create('recurrings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('destination_id')->nullable();
            $table->string('title');
            $table->string('currency')->nullable();
            $table->decimal('fixed', 16, 2)->nullable();
            $table->decimal('percentage')->nullable();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->enum('interval', [
                'daily',
                'weekly',
                'monthly',
                'yearly',
                'weekday',      // Senin - Jumat
                'weekend',      // Sabtu & Minggu
            ]);
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->timestamp('last_generated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrings');
    }
};
