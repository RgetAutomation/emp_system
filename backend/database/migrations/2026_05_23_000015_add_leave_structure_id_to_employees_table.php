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
        if (!Schema::hasColumn('employees', 'leave_structure_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('leave_structure_id')->nullable()->constrained('leave_structures')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['leave_structure_id']);
            $table->dropColumn('leave_structure_id');
        });
    }
};
