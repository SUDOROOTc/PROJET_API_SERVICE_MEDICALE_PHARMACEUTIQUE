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
        Schema::create('groupe_garde_pharmacie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_garde_id')->constrained('groupes_garde')->cascadeOnDelete();
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['groupe_garde_id', 'pharmacy_id']);
            $table->index(['pharmacy_id', 'groupe_garde_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupe_garde_pharmacie');
    }
};
