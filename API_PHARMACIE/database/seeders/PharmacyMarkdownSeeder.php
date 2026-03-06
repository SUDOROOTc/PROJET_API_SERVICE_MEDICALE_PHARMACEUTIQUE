<?php

namespace Database\Seeders;

use App\Models\Pharmacy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PharmacyMarkdownSeeder extends Seeder
{
    /**
     * Seed the application's database from markdown source.
     */
    public function run(): void
    {
        $filePath = base_path('docs/liste_pharmacie.md');

        if (! File::exists($filePath)) {
            $this->command?->error('Fichier introuvable: docs/liste_pharmacie.md');

            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->command?->error('Impossible de lire le fichier markdown des pharmacies.');

            return;
        }

        $currentCity = null;
        $currentGroup = null;
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || $trimmed === '---') {
                continue;
            }

            if (preg_match('/^##\s+\*\*(.+)\*\*$/', $trimmed, $matches) === 1) {
                $currentCity = trim($matches[1]);

                continue;
            }

            if (preg_match('/^###\s+\*\*Groupe\s+(\d+)\*\*$/', $trimmed, $matches) === 1) {
                $currentGroup = 'Groupe '.$matches[1];

                continue;
            }

            if (! str_starts_with($trimmed, '|')) {
                continue;
            }

            if (str_contains($trimmed, 'Nom de la Pharmacie') || preg_match('/^\|[-\s|]+\|$/', $trimmed) === 1) {
                continue;
            }

            if ($currentCity === null || $currentGroup === null) {
                $skipped++;

                continue;
            }

            $parts = array_map('trim', explode('|', $trimmed));
            if (count($parts) < 6) {
                $skipped++;

                continue;
            }

            $name = $this->normalizeText($parts[1] ?? '');
            $phone = $this->normalizePhone($parts[2] ?? '');
            $situation = $this->normalizeText($parts[3] ?? '');
            $quartierVille = $this->normalizeText($parts[4] ?? '');

            if ($name === null) {
                $skipped++;

                continue;
            }

            $city = $quartierVille ?? $currentCity;
            $address = $situation ?? ('Adresse non renseignee - '.$city);

            $model = Pharmacy::query()
                ->where('name', $name)
                ->where('city', $city)
                ->first();

            $payload = [
                'address' => $address,
                'groupe' => $currentGroup,
                'phone' => $phone,
                'latitude' => null,
                'longitude' => null,
                'is_on_duty' => false,
            ];

            if ($model !== null) {
                $model->fill($payload);
                $model->save();
                $updated++;

                continue;
            }

            Pharmacy::query()->create([
                'name' => $name,
                'city' => $city,
                ...$payload,
            ]);
            $inserted++;
        }

        $this->command?->info('Import pharmacies termine.');
        $this->command?->info('Inserees: '.$inserted);
        $this->command?->info('Mises a jour: '.$updated);
        $this->command?->info('Ignorees: '.$skipped);
    }

    private function normalizeText(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : preg_replace('/\s+/', ' ', $value);
    }

    private function normalizePhone(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = str_replace('-', ' ', $value);

        return preg_replace('/\s+/', ' ', $value);
    }
}
