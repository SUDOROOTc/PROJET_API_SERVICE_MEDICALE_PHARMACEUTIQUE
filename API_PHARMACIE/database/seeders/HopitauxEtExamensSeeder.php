<?php

namespace Database\Seeders;

use App\Models\Examen;
use App\Models\Hopital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class HopitauxEtExamensSeeder extends Seeder
{
    /**
     * Seed hospitals and related exams from markdown source.
     */
    public function run(): void
    {
        $filePath = base_path('docs/hopitaux_et_examens.md');

        if (! File::exists($filePath)) {
            $this->command?->error('Fichier introuvable: docs/hopitaux_et_examens.md');

            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->command?->error('Impossible de lire docs/hopitaux_et_examens.md');

            return;
        }

        $hopitauxInserts = 0;
        $hopitauxUpdates = 0;
        $examensInserts = 0;
        $relationsAjoutees = 0;
        $lignesIgnorees = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (! str_starts_with($trimmed, '|')) {
                continue;
            }

            if (str_contains($trimmed, 'Nom de l\'Établissement') || str_contains($trimmed, ':---')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $trimmed));
            if (count($parts) < 7) {
                $lignesIgnorees++;

                continue;
            }

            $nom = $this->normalizeText($parts[2] ?? '');
            $ville = $this->normalizeText($parts[3] ?? '');
            $categorie = $this->normalizeText($parts[4] ?? '');
            $examensRaw = $this->normalizeText($parts[5] ?? '');

            if ($nom === null || $ville === null) {
                $lignesIgnorees++;

                continue;
            }

            $hopital = Hopital::query()->firstOrNew([
                'name' => $nom,
                'city' => $ville,
            ]);

            $hopital->address = $hopital->address ?: ('Adresse non renseignee - '.$ville);
            $hopital->phone = null;
            $hopital->emergency_available = $this->containsEmergencyKeyword($examensRaw);
            $hopital->categorie = $categorie;

            if ($hopital->exists) {
                $hopital->save();
                $hopitauxUpdates++;
            } else {
                $hopital->save();
                $hopitauxInserts++;
            }

            if ($examensRaw === null) {
                continue;
            }

            $items = array_filter(array_map('trim', explode(',', $examensRaw)));
            foreach ($items as $item) {
                $examName = $this->normalizeText($item);
                if ($examName === null) {
                    continue;
                }

                $examen = Examen::query()->firstOrCreate(
                    ['name' => $examName],
                    [
                        'category' => $this->categorizeExam($examName),
                        'description' => 'Importe depuis docs/hopitaux_et_examens.md',
                        'is_active' => true,
                    ]
                );

                if ($examen->wasRecentlyCreated) {
                    $examensInserts++;
                }

                $before = $hopital->examens()->where('examens.id', $examen->id)->exists();

                $hopital->examens()->syncWithoutDetaching([
                    $examen->id => [
                        'is_available' => true,
                        'preparation_notes' => null,
                    ],
                ]);

                if (! $before) {
                    $relationsAjoutees++;
                }
            }
        }

        $this->command?->info('Import hopitaux/examens termine.');
        $this->command?->info('Hopitaux inseres: '.$hopitauxInserts);
        $this->command?->info('Hopitaux mis a jour: '.$hopitauxUpdates);
        $this->command?->info('Examens inseres: '.$examensInserts);
        $this->command?->info('Relations examen-hopital ajoutees: '.$relationsAjoutees);
        $this->command?->info('Lignes ignorees: '.$lignesIgnorees);
    }

    private function normalizeText(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return preg_replace('/\s+/', ' ', $value);
    }

    private function containsEmergencyKeyword(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = mb_strtolower($value);

        return str_contains($normalized, 'urgence') || str_contains($normalized, 'urgences');
    }

    private function categorizeExam(string $name): string
    {
        $normalized = mb_strtolower($name);

        if (str_contains($normalized, 'radio') || str_contains($normalized, 'scanner') || str_contains($normalized, 'irm') || str_contains($normalized, 'echo') || str_contains($normalized, 'imagerie')) {
            return 'Imagerie';
        }

        if (str_contains($normalized, 'bio') || str_contains($normalized, 'laboratoire') || str_contains($normalized, 'analyse') || str_contains($normalized, 'nfs') || str_contains($normalized, 'glycemie') || str_contains($normalized, 'creatinine')) {
            return 'Biologie';
        }

        if (str_contains($normalized, 'chirurgie')) {
            return 'Chirurgie';
        }

        if (str_contains($normalized, 'urgence')) {
            return 'Urgence';
        }

        return 'General';
    }
}
