<?php

namespace Database\Seeders;

use App\Models\Medicament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MedicamentSeeder extends Seeder
{
    /**
     * Seed les médicaments à partir du fichier markdown avec des prix en FCFA.
     */
    public function run(): void
    {
        $filePath = base_path('docs/liste_medicaments.md');

        if (! File::exists($filePath)) {
            $this->command?->error('Fichier introuvable: docs/liste_medicaments.md');

            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->command?->error('Impossible de lire le fichier docs/liste_medicaments.md');

            return;
        }

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (! str_starts_with($trimmed, '|')) {
                continue;
            }

            // Ignorer l'en-tête et les séparateurs
            if (str_contains($trimmed, 'Phone') || preg_match('/^\|[-\s|]+\|$/', $trimmed) === 1) {
                continue;
            }

            $parts = array_map('trim', explode('|', $trimmed));

            // Format attendu : | code | designation |
            if (count($parts) < 4) {
                $skipped++;
                continue;
            }

            $code        = $parts[1] ?? '';
            $designation = $parts[2] ?? '';

            if ($code === '' || $designation === '') {
                $skipped++;
                continue;
            }

            $form   = $this->extractForm($designation);
            $dosage = $this->extractDosage($designation);
            $prix   = $this->calculerPrix($code, $designation);

            $existing = Medicament::query()->where('name', $designation)->first();

            if ($existing !== null) {
                $existing->update([
                    'dosage' => $dosage,
                    'form'   => $form,
                    'prix'   => $prix,
                ]);
                $updated++;
            } else {
                Medicament::query()->create([
                    'name'      => $designation,
                    'dosage'    => $dosage,
                    'form'      => $form,
                    'prix'      => $prix,
                    'is_active' => true,
                ]);
                $inserted++;
            }
        }

        $this->command?->info('Import médicaments terminé.');
        $this->command?->info('Insérés  : '.$inserted);
        $this->command?->info('Mis à jour: '.$updated);
        $this->command?->info('Ignorés  : '.$skipped);
    }

    /**
     * Calcule un prix réaliste en FCFA de manière déterministe.
     *
     * Les deux premiers chiffres du code produit indiquent la catégorie thérapeutique.
     * Le calcul combine le prix de base de la catégorie + un multiplicateur selon
     * la forme galénique + une variation déterministe (±20%) basée sur le code.
     */
    private function calculerPrix(string $code, string $designation): int
    {
        $prefix  = (int) substr($code, 0, 2);
        $codeInt = (int) $code;

        // Prix de base par catégorie thérapeutique (FCFA)
        $prixBase = match (true) {
            $prefix === 10                                         => 3000,  // Analgésiques inj.
            $prefix === 11                                         => 2000,  // Anti-inflammatoires
            $prefix === 12                                         => 20000, // Anesthésiques
            $prefix === 13 && (int) substr($code, 0, 4) >= 1307  => 25000, // Antirétroviraux (ARV)
            $prefix === 13                                         => 3000,  // Autres anti-infectieux
            $prefix === 14                                         => 3500,  // Cardiovasculaire
            $prefix === 15                                         => 2000,  // Antihistaminiques / corticoïdes
            $prefix === 16                                         => 30000, // Hormones (insuline)
            $prefix === 18                                         => 10000, // Médicaments cardiaques urgents
            $prefix === 19                                         => 2000,  // Électrolytes
            $prefix === 20                                         => 60000, // Anticancéreux
            $prefix === 21                                         => 5000,  // Hématologie
            $prefix === 22                                         => 3000,  // Respiratoire
            $prefix === 23                                         => 2000,  // Antiseptiques / désinfectants
            $prefix === 24                                         => 2000,  // Gastro-entérologie
            $prefix === 25                                         => 4000,  // Psychotropes / neurologie
            $prefix === 26                                         => 6000,  // Solutés perfusables
            $prefix === 27                                         => 8000,  // Contraceptifs / vaccins
            $prefix === 28                                         => 3000,  // Matériel médical consommable
            $prefix === 29                                         => 25000, // Instruments chirurgicaux
            $prefix === 30                                         => 5000,  // Matériel de laboratoire
            $prefix === 31                                         => 5000,  // Dentaire
            $prefix === 40                                         => 2500,  // Dermatologie / ORL
            default                                                => 2500,
        };

        // Multiplicateur selon la forme galénique
        $desig      = mb_strtolower($designation);
        $multiplier = 1.0;

        if (str_contains($desig, 'implant')) {
            $multiplier = 3.0;
        } elseif (str_contains($desig, 'perfusion')) {
            $multiplier = 2.0;
        } elseif (str_contains($desig, 'inj')) {
            $multiplier = 1.5;
        } elseif (str_contains($desig, 'sirop') || str_contains($desig, 'susp')) {
            $multiplier = 1.2;
        }

        // Variation déterministe ±20% basée sur les deux derniers chiffres du code
        $variation = (($codeInt % 5) - 2) * (int) ($prixBase * 0.1);

        $prix = (int) (($prixBase * $multiplier) + $variation);

        // Arrondi au multiple de 50 le plus proche, minimum 100 FCFA
        return max(100, (int) (round($prix / 50) * 50));
    }

    /**
     * Extrait la forme galénique à partir du libellé du médicament.
     */
    private function extractForm(string $designation): ?string
    {
        $desig = mb_strtolower($designation);

        if (str_contains($desig, 'inj')) {
            return 'Injectable';
        }
        if (str_contains($desig, 'cp.') || str_contains($desig, 'comprimé')) {
            return 'Comprimé';
        }
        if (str_contains($desig, 'gel.') || str_contains($desig, 'gélule')) {
            return 'Gélule';
        }
        if (str_contains($desig, 'sirop')) {
            return 'Sirop';
        }
        if (str_contains($desig, 'susp') || str_contains($desig, 'buv')) {
            return 'Suspension buvable';
        }
        if (str_contains($desig, 'pde') || str_contains($desig, 'pommade')) {
            return 'Pommade';
        }
        if (str_contains($desig, 'gèle') || str_contains($desig, 'gel ')) {
            return 'Gel';
        }
        if (str_contains($desig, 'sol.') || str_contains($desig, 'solution')) {
            return 'Solution';
        }
        if (str_contains($desig, 'implant')) {
            return 'Implant';
        }
        if (str_contains($desig, 'capsule')) {
            return 'Capsule';
        }
        if (str_contains($desig, 'sachet')) {
            return 'Sachet';
        }
        if (str_contains($desig, 'coll.') || str_contains($desig, 'collyre')) {
            return 'Collyre';
        }
        if (str_contains($desig, 'pdre')) {
            return 'Poudre';
        }

        return null;
    }

    /**
     * Extrait le dosage à partir du libellé (ex: "500mg", "10mg/ml 2ml").
     */
    private function extractDosage(string $designation): ?string
    {
        if (preg_match('/(\d+[\.,]?\d*\s*(?:mg|g|UI|ml|µg)(?:\/\s*(?:ml|kg|dl))?(?:\s+\d+[\.,]?\d*\s*(?:ml|g))?)/i', $designation, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }
}
