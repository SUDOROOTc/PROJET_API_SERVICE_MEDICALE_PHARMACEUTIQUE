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
        Schema::create('plannings_garde', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_garde_id')->constrained('groupes_garde')->cascadeOnDelete();
            $table->dateTime('debut_garde')->index();
            $table->dateTime('fin_garde')->index();
            $table->boolean('actif')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['groupe_garde_id', 'debut_garde', 'fin_garde']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plannings_garde');
    }
};
