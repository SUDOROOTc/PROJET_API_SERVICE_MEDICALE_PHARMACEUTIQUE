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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'firebase_uid')) {
                $table->string('firebase_uid')->nullable()->after('id')->unique();
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('viewer')->after('email')->index();
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role')->index();
            }

            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('users', 'firebase_uid')) {
                $columnsToDrop[] = 'firebase_uid';
            }

            if (Schema::hasColumn('users', 'role')) {
                $columnsToDrop[] = 'role';
            }

            if (Schema::hasColumn('users', 'is_active')) {
                $columnsToDrop[] = 'is_active';
            }

            if (Schema::hasColumn('users', 'last_seen_at')) {
                $columnsToDrop[] = 'last_seen_at';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
