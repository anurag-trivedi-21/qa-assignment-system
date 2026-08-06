<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tester_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('clocked_in_at');
            $table->timestamp('clocked_out_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'clocked_in_at']);
            $table->index(['user_id', 'clocked_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tester_shifts');
    }
};
