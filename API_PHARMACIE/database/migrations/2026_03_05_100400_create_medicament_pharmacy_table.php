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
        Schema::create('medicament_pharmacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicament_id')->constrained('medicaments')->cascadeOnDelete();
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_available')->default(true)->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['medicament_id', 'pharmacy_id']);
            $table->index(['pharmacy_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicament_pharmacy');
    }
};
