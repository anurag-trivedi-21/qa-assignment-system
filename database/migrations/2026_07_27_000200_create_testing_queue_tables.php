<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('test_value', 4, 2)->default(0.5);
            $table->timestamps();
        });

        Schema::create('pending_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason');
            $table->unsignedInteger('impact_count')->default(0);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['tester_id', 'created_at']);
            $table->index(['test_subject_id', 'tester_id']);
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tester_id')->constrained('users')->cascadeOnDelete();
            $table->string('result');
            $table->timestamp('tested_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tester_id', 'tested_at']);
            $table->index(['test_subject_id', 'tester_id', 'tested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('pending_tests');
        Schema::dropIfExists('test_subjects');
    }
};
