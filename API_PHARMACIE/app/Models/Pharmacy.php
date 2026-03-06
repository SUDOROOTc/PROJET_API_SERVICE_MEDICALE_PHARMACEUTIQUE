<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pharmacy extends Model
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
        'groupe',
        'phone',
        'latitude',
        'longitude',
        'is_on_duty',
        'opens_at',
        'closes_at',
    ];

    /**
     * Get medicaments available in the pharmacy.
     */
    public function medicaments(): BelongsToMany
    {
        return $this->belongsToMany(Medicament::class, 'medicament_pharmacy')
            ->withPivot(['stock_quantity', 'is_available', 'price'])
            ->withTimestamps();
    }
}
 