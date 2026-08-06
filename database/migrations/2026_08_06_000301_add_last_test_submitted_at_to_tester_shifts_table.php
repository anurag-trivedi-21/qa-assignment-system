<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tester_shifts', function (Blueprint $table) {
            $table->timestamp('last_test_submitted_at')->nullable()->after('clocked_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('tester_shifts', function (Blueprint $table) {
            $table->dropColumn('last_test_submitted_at');
        });
    }
};
