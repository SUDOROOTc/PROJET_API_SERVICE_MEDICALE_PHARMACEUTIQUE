<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hopital extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'latitude',
        'longitude',
        'emergency_available',
    ];

    /**
     * Get examens available in the hospital.
     */
    public function examens(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'examen_hopital')
            ->withPivot(['is_available', 'preparation_notes'])
            ->withTimestamps();
    }
}
