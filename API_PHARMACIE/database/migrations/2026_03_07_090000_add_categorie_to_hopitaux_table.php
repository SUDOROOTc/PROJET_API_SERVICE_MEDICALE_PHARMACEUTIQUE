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
        Schema::table('hopitaux', function (Blueprint $table) {
            if (! Schema::hasColumn('hopitaux', 'categorie')) {
                $table->string('categorie')->nullable()->after('name')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hopitaux', function (Blueprint $table) {
            if (Schema::hasColumn('hopitaux', 'categorie')) {
                $table->dropColumn('categorie');
            }
        });
    }
};
