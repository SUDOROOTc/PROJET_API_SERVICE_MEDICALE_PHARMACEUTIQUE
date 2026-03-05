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
        Schema::create('examen_hopital', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examens')->cascadeOnDelete();
            $table->foreignId('hopital_id')->constrained('hopitaux')->cascadeOnDelete();
            $table->boolean('is_available')->default(true)->index();
            $table->text('preparation_notes')->nullable();
            $table->timestamps();

            $table->unique(['examen_id', 'hopital_id']);
            $table->index(['hopital_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examen_hopital');
    }
};
