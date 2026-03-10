<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GroupeGarde extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'groupes_garde';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'ville',
        'description',
        'debut_garde',
        'fin_garde',
        'actif',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debut_garde' => 'datetime',
            'fin_garde' => 'datetime',
            'actif' => 'boolean',
        ];
    }

    /**
     * Pharmacies that belong to the same city and group label.
     */
    public function pharmacies(): Builder
    {
        return Pharmacy::query()
            ->where('city', $this->ville)
            ->where('groupe', $this->nom);
    }
}
