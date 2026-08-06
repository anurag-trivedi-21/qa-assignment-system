<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_tests', function (Blueprint $table) {
            $table->boolean('is_auto_assigned')->default(false)->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('pending_tests', function (Blueprint $table) {
            $table->dropColumn('is_auto_assigned');
        });
    }
};
