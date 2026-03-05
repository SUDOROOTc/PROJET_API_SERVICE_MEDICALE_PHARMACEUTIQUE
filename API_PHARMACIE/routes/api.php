<?php

use App\Http\Controllers\Api\V1\ExamenController;
use App\Http\Controllers\Api\V1\HopitalController;
use App\Http\Controllers\Api\V1\MedicamentController;
use App\Http\Controllers\Api\V1\PharmacyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/pharmacies', [PharmacyController::class, 'index']);
    Route::get('/pharmacies/on-duty', [PharmacyController::class, 'onDuty']);
    Route::get('/pharmacies/nearby', [PharmacyController::class, 'nearby']);
    Route::get('/pharmacies/{pharmacy}', [PharmacyController::class, 'show']);

    Route::get('/medicaments', [MedicamentController::class, 'index']);
    Route::get('/medicaments/search', [MedicamentController::class, 'search']);
    Route::get('/medicaments/{medicament}', [MedicamentController::class, 'show']);
    Route::get('/medicaments/{medicament}/pharmacies', [MedicamentController::class, 'pharmacies']);

    Route::get('/hopitaux', [HopitalController::class, 'index']);
    Route::get('/hopitaux/search', [HopitalController::class, 'search']);
    Route::get('/hopitaux/nearby', [HopitalController::class, 'nearby']);
    Route::get('/hopitaux/{hopital}', [HopitalController::class, 'show']);

    Route::get('/examens', [ExamenController::class, 'index']);
    Route::get('/examens/search', [ExamenController::class, 'search']);
    Route::get('/examens/{examen}', [ExamenController::class, 'show']);
    Route::get('/examens/{examen}/hopitaux', [ExamenController::class, 'hopitaux']);
});
