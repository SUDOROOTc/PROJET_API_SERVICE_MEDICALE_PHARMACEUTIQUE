<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\IndexRequest;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Models\Examen;
use Illuminate\Http\JsonResponse;

class ExamenController extends BaseApiController
{
    public function index(IndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $examens = Examen::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($examens, 'Liste des examens chargee avec succes.');
    }

    public function show(Examen $examen): JsonResponse
    {
        return $this->success($examen, 'Details de l\'examen charges avec succes.');
    }

    public function search(SearchRequest $request): JsonResponse
    {
        $q = $request->string('q')->value();
        $perPage = (int) $request->integer('per_page', 15);

        $examens = Examen::query()
            ->where('is_active', true)
            ->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('category', 'like', '%'.$q.'%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($examens, 'Recherche d\'examens effectuee avec succes.');
    }

    public function hopitaux(Examen $examen, IndexRequest $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $hopitaux = $examen->hopitaux()
            ->wherePivot('is_available', true)
            ->orderBy('hopitaux.name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($hopitaux, 'Hopitaux proposant cet examen charges avec succes.');
    }
}
