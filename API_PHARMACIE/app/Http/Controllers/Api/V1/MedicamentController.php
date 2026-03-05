<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Models\Medicament;
use Illuminate\Http\JsonResponse;

class MedicamentController extends BaseApiController
{
    public function index(IndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $medicaments = Medicament::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($medicaments, 'Medicaments list loaded successfully.');
    }

    public function show(Medicament $medicament): JsonResponse
    {
        return $this->success($medicament, 'Medicament details loaded successfully.');
    }

    public function search(SearchRequest $request): JsonResponse
    {
        $q = $request->string('q')->value();
        $perPage = (int) $request->integer('per_page', 15);

        $medicaments = Medicament::query()
            ->where('is_active', true)
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($medicaments, 'Medicament search completed successfully.');
    }

    public function pharmacies(Medicament $medicament, IndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $pharmacies = $medicament->pharmacies()
            ->wherePivot('is_available', true)
            ->orderBy('pharmacies.name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($pharmacies, 'Pharmacies for medicament loaded successfully.');
    }
}
