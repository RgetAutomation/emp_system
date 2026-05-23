<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });

        // Optionally copy existing emails from users table to employees table
        DB::statement('UPDATE employees e
            INNER JOIN users u ON e.user_id = u.id
            SET e.email = u.email
            WHERE e.email IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
