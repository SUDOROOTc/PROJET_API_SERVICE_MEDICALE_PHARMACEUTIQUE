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
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            if (! Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->text('avatar_url')->nullable()->after('phone_number');
            }

            if (! Schema::hasColumn('users', 'firebase_sign_in_provider')) {
                $table->string('firebase_sign_in_provider', 50)->nullable()->after('firebase_uid');
            }

            if (! Schema::hasColumn('users', 'firebase_auth_time')) {
                $table->timestamp('firebase_auth_time')->nullable()->after('last_seen_at');
            }

            if (! Schema::hasColumn('users', 'firebase_raw_claims')) {
                $table->json('firebase_raw_claims')->nullable()->after('firebase_auth_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            foreach (['phone_number', 'avatar_url', 'firebase_sign_in_provider', 'firebase_auth_time', 'firebase_raw_claims'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
