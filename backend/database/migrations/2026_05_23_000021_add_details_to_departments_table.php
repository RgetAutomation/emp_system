<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('departments', 'code')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('code')->nullable();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('location')->nullable();
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['code', 'manager_id', 'location', 'is_active']);
        });
    }
};
