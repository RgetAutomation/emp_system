<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attendances', 'late_minutes')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->integer('late_minutes')->nullable()->after('status');
                $table->text('notes')->nullable()->after('late_minutes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'notes']);
        });
    }
};
