
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('groupes_garde')) {
            return;
        }

        Schema::table('groupes_garde', function (Blueprint $table) {
            if (! Schema::hasColumn('groupes_garde', 'debut_garde')) {
                $table->dateTime('debut_garde')->nullable()->index();
            }

            if (! Schema::hasColumn('groupes_garde', 'fin_garde')) {
                $table->dateTime('fin_garde')->nullable()->index();
            }

            if (! Schema::hasColumn('groupes_garde', 'actif')) {
                $table->boolean('actif')->default(true)->index();
            }

            if (! Schema::hasColumn('groupes_garde', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // Remove unique index to allow multiple schedules per group name.
        try {
            Schema::table('groupes_garde', function (Blueprint $table): void {
                $table->dropUnique('groupes_garde_nom_ville_unique');
            });
        } catch (\Throwable) {
            // Index may already be removed in some environments.
        }

        if (Schema::hasTable('plannings_garde')) {
            DB::statement(
                'INSERT INTO groupes_garde (nom, ville, description, debut_garde, fin_garde, actif, notes, created_at, updated_at)
                 SELECT g.nom, g.ville, g.description, p.debut_garde, p.fin_garde, p.actif, p.notes, p.created_at, p.updated_at
                 FROM plannings_garde p
                 INNER JOIN groupes_garde g ON g.id = p.groupe_garde_id'
            );

            // Keep only schedule rows in unified table.
            DB::statement('DELETE FROM groupes_garde WHERE debut_garde IS NULL OR fin_garde IS NULL');
        }

        if (Schema::hasTable('groupe_garde_pharmacie')) {
            Schema::drop('groupe_garde_pharmacie');
        }

        if (Schema::hasTable('plannings_garde')) {
            Schema::drop('plannings_garde');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('groupes_garde')) {
            return;
        }

        if (! Schema::hasTable('groupe_garde_pharmacie')) {
            Schema::create('groupe_garde_pharmacie', function (Blueprint $table) {
                $table->id();
                $table->foreignId('groupe_garde_id')->constrained('groupes_garde')->cascadeOnDelete();
                $table->foreignId('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['groupe_garde_id', 'pharmacy_id']);
                $table->index(['pharmacy_id', 'groupe_garde_id']);
            });
        }

        if (! Schema::hasTable('plannings_garde')) {
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
    }
};
