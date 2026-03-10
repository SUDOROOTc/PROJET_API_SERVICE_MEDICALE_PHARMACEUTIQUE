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
        Schema::create('groupes_garde', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('ville')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['nom', 'ville']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupes_garde');
    }
};
