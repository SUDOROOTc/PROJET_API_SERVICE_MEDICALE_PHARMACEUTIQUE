<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Http\Requests\Api\V1\NearbyRequest;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Models\Hopital;
use Illuminate\Http\JsonResponse;

class HopitalController extends BaseApiController
{
    public function index(IndexRequest $request): JsonResponse
    {
        $query = Hopital::query();

        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->string('city')->value().'%');
        }

        $perPage = (int) $request->integer('per_page', 15);
        $hopitaux = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return $this->paginated($hopitaux, 'Liste des hopitaux chargee avec succes.');
    }

    public function show(Hopital $hopital): JsonResponse
    {
        $hopital->load(['examens' => function ($query): void {
            $query->orderBy('name');
        }]);

        return $this->success($hopital, 'Details de l\'hopital charges avec succes.');
    }

    public function search(SearchRequest $request): JsonResponse
    {
        $q = $request->string('q')->value();
        $perPage = (int) $request->integer('per_page', 15);

        $hopitaux = Hopital::query()
            ->where('name', 'like', '%'.$q.'%')
            ->orWhere('city', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($hopitaux, 'Recherche d\'hopitaux effectuee avec succes.');
    }

    public function nearby(NearbyRequest $request): JsonResponse
    {
        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $radiusKm = (float) $request->input('radius_km', 30);
        $perPage = (int) $request->integer('per_page', 15);

        $distanceSql = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

        $hopitaux = Hopital::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('*')
            ->selectRaw($distanceSql.' AS distance_km', [$lat, $lng, $lat])
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($hopitaux, 'Liste des hopitaux proches chargee avec succes.');
    }
}
