<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('designations', 'code')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->string('code')->nullable();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('designations')->nullOnDelete();
                $table->decimal('min_salary', 10, 2)->nullable();
                $table->decimal('max_salary', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['code', 'department_id', 'parent_id', 'min_salary', 'max_salary', 'is_active']);
        });
    }
};
