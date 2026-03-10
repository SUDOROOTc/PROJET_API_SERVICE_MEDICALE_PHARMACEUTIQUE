<?php

namespace Database\Seeders;

use App\Models\GroupeGarde;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProgrammeGardeSeeder extends Seeder
{
    /**
     * Seed duty groups and schedules from markdown source.
     */
    public function run(): void
    {
        $filePath = base_path('docs/groupe_garde.md');

        if (! File::exists($filePath)) {
            $this->command?->error('Fichier introuvable: docs/groupe_garde.md');

            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->command?->error('Impossible de lire le programme de garde.');

            return;
        }

        $ville = 'Ouagadougou';
        $annee = 2026;
        $enregistrementsCrees = 0;
        $enregistrementsMaj = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/(20\d{2})/', $trimmed, $yearMatch) === 1) {
                $annee = (int) $yearMatch[1];
            }

            if (preg_match('/^-\s*(\d{2}\/\d{2})\s+au\s+(\d{2}\/\d{2})\s*:\s*\*\*Groupe\s+([IVXLC0-9]+)\*\*/u', $trimmed, $matches) !== 1) {
                continue;
            }

            $debut = $this->parseDate($matches[1], $annee);
            $fin = $this->parseDate($matches[2], $annee);

            if ($debut === null || $fin === null) {
                continue;
            }

            // Safety for year rollover schedules (example: 28/12 to 04/01).
            if ($fin->lessThanOrEqualTo($debut)) {
                $fin = $fin->addYear();
            }

            $nomGroupe = $this->normalizeGroupName($matches[3]);

            $enregistrement = GroupeGarde::query()->firstOrNew([
                'nom' => $nomGroupe,
                'ville' => $ville,
                'debut_garde' => $debut->toDateTimeString(),
                'fin_garde' => $fin->toDateTimeString(),
            ]);

            $enregistrement->description = 'Programme de garde importe depuis docs/groupe_garde.md';
            $enregistrement->actif = true;
            $enregistrement->notes = 'Programme de garde importe depuis docs/groupe_garde.md';

            if ($enregistrement->exists) {
                $enregistrement->save();
                $enregistrementsMaj++;
            } else {
                $enregistrement->save();
                $enregistrementsCrees++;
            }
        }

        $this->command?->info('Import programme de garde termine.');
        $this->command?->info('Lignes de planning creees: '.$enregistrementsCrees);
        $this->command?->info('Lignes de planning mises a jour: '.$enregistrementsMaj);
    }

    private function parseDate(string $dayMonth, int $year): ?CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('d/m/Y H:i', $dayMonth.'/'.$year.' 00:00');

        return $date === false ? null : $date;
    }

    private function normalizeGroupName(string $groupToken): string
    {
        $token = strtoupper(trim($groupToken));

        if (is_numeric($token)) {
            return 'Groupe '.(int) $token;
        }

        $map = [
            'I' => 1,
            'II' => 2,
            'III' => 3,
            'IV' => 4,
            'V' => 5,
        ];

        return 'Groupe '.($map[$token] ?? $token);
    }
}
