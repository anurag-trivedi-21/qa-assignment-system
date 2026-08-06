<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_tests', function (Blueprint $table) {
            $table->timestamp('return_to_queue_at')->nullable()->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('pending_tests', function (Blueprint $table) {
            $table->dropColumn('return_to_queue_at');
        });
    }
};
