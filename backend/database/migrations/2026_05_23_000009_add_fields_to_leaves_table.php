<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('admin_note');
            $table->integer('days')->default(1)->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['admin_note', 'approved_by', 'days']);
        });
    }
};
