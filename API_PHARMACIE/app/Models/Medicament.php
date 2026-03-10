<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Medicament extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'dosage',
        'form',
        'description',
        'prix',
        'is_active',
    ];

    protected $casts = [
        'prix'      => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get pharmacies that provide this medicament.
     */
    public function pharmacies(): BelongsToMany
    {
        return $this->belongsToMany(Pharmacy::class, 'medicament_pharmacy')
            ->withPivot(['stock_quantity', 'is_available', 'price'])
            ->withTimestamps();
    }
}
