<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Examen extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'description',
        'is_active',
    ];

    /**
     * Get hospitals that provide this exam.
     */
    public function hopitaux(): BelongsToMany
    {
        return $this->belongsToMany(Hopital::class, 'examen_hopital')
            ->withPivot(['is_available', 'preparation_notes'])
            ->withTimestamps();
    }
}
