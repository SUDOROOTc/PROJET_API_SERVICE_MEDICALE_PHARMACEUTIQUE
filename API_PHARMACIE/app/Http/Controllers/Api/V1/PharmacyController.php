<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Http\Requests\Api\V1\NearbyRequest;
use App\Models\Pharmacy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class PharmacyController extends BaseApiController
{
    public function index(IndexRequest $request): JsonResponse
    {
        $query = Pharmacy::query();

        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->string('city')->value().'%');
        }

        $perPage = (int) $request->integer('per_page', 15);
        $pharmacies = $query->orderBy('name')->paginate($perPage)->withQueryString();

        return $this->paginated($pharmacies, 'Pharmacies list loaded successfully.');
    }

    public function show(Pharmacy $pharmacy): JsonResponse
    {
        return $this->success($pharmacy, 'Pharmacy details loaded successfully.');
    }

    public function onDuty(IndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $pharmacies = Pharmacy::query()
            ->where('is_on_duty', true)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($pharmacies, 'On-duty pharmacies loaded successfully.');
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

        return $this->paginated($pharmacies, 'Nearby pharmacies loaded successfully.');
    }
}
