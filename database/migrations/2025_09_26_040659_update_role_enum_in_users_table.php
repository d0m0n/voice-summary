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
        // MySQLのENUMカラムを更新するために、一時的にカラムを削除して再作成
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'moderator', 'viewer'])->default('viewer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ロールバック: 元のENUMに戻す
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'viewer'])->default('viewer');
        });
    }
};
