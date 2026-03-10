<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Http\Requests\Api\V1\NearbyRequest;
use App\Http\Requests\Api\V1\PharmaciesParMedicamentRequest;
use App\Http\Requests\Api\V1\PharmaciesGardeRequest;
use App\Models\GroupeGarde;
use Carbon\CarbonImmutable;
use App\Models\Pharmacy;
use Illuminate\Http\JsonResponse;

class PharmacyController extends BaseApiController
{
    public function deGarde(PharmaciesGardeRequest $request): JsonResponse
    {
        $date = $request->filled('date')
            ? CarbonImmutable::createFromFormat('Y-m-d', (string) $request->input('date'))
            : CarbonImmutable::now();

        if ($date === false) {
            return response()->json([
                'success' => false,
                'message' => 'Le format de date est invalide.',
                'errors' => [
                    'date' => ['Le parametre date doit respecter le format YYYY-MM-DD.'],
                ],
            ], 422);
        }

        $ville = (string) $request->input('ville', 'Ouagadougou');
        $perPage = (int) $request->integer('per_page', 15);

        $reference = $date->startOfDay();

        $planningActif = GroupeGarde::query()
            ->where('actif', true)
            ->where('debut_garde', '<=', $reference)
            ->where('fin_garde', '>', $reference)
            ->where('ville', $ville)
            ->orderByDesc('debut_garde')
            ->first();

        if ($planningActif === null) {
            return $this->success([], 'Aucune pharmacie de garde trouvee pour cette date.', [
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ],
                'garde' => [
                    'ville' => $ville,
                    'date_reference' => $reference->toDateString(),
                    'groupe' => null,
                    'debut_garde' => null,
                    'fin_garde' => null,
                ],
            ]);
        }

        $pharmacies = Pharmacy::query()
            ->where('city', $ville)
            ->where('groupe', $planningActif->nom)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            $pharmacies->items(),
            'Liste des pharmacies de garde chargee avec succes.',
            [
                'pagination' => [
                    'current_page' => $pharmacies->currentPage(),
                    'per_page' => $pharmacies->perPage(),
                    'total' => $pharmacies->total(),
                    'last_page' => $pharmacies->lastPage(),
                ],
                'garde' => [
                    'ville' => $ville,
                    'date_reference' => $reference->toDateString(),
                    'groupe' => $planningActif->nom,
                    'debut_garde' => optional($planningActif->debut_garde)->toDateTimeString(),
                    'fin_garde' => optional($planningActif->fin_garde)->toDateTimeString(),
                ],
            ]
        );
    }

    public function index(IndexRequest $request): JsonResponse
    {
        $query = Pharmacy::query();

        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->string('city')->value().'%');
        }

        $perPage = (int) $request->integer('per_page', 15);
        $pharmacies = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return $this->paginated($pharmacies, 'Liste des pharmacies chargee avec succes.');
    }

    public function show(Pharmacy $pharmacy): JsonResponse
    {
        return $this->success($pharmacy, 'Details de la pharmacie charges avec succes.');
    }

    public function onDuty(IndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $pharmacies = Pharmacy::query()
            ->where('is_on_duty', true)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($pharmacies, 'Liste des pharmacies de garde chargee avec succes.');
    }

    public function parMedicament(PharmaciesParMedicamentRequest $request): JsonResponse
    {
        $q = $request->string('q')->value();
        $perPage = (int) $request->integer('per_page', 15);
        $minStock = (int) $request->integer('min_stock', 1);

        $query = Pharmacy::query();

        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->string('city')->value().'%');
        }

        $query
            ->whereHas('medicaments', function ($medicamentQuery) use ($q, $minStock): void {
                $medicamentQuery
                    ->where('medicaments.name', 'like', '%'.$q.'%')
                    ->where('medicament_pharmacy.is_available', true)
                    ->where('medicament_pharmacy.stock_quantity', '>=', $minStock);
            })
            ->with(['medicaments' => function ($medicamentQuery) use ($q, $minStock): void {
                $medicamentQuery
                    ->where('medicaments.name', 'like', '%'.$q.'%')
                    ->wherePivot('is_available', true)
                    ->wherePivot('stock_quantity', '>=', $minStock)
                    ->orderBy('medicaments.name');
            }]);

        $pharmacies = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($pharmacies, 'Pharmacies proposant le medicament recherche chargees avec succes.');
    }

    public function nearby(NearbyRequest $request): JsonResponse
    {
        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $radiusKm = (float) $request->input('radius_km', 20);
        $perPage = (int) $request->integer('per_page', 15);

        $distanceSql = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

        $query = Pharmacy::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('*')
            ->selectRaw($distanceSql.' AS distance_km', [$lat, $lng, $lat])
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');

        $pharmacies = $query->paginate($perPage)->withQueryString();

        return $this->paginated($pharmacies, 'Liste des pharmacies proches chargee avec succes.');
    }
}
