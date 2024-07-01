<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Major extends Model
{
    use HasFactory;

    protected $table = "major_passing_grade";
    protected $primaryKey = "major_id";

    protected $fillable = [
        'university_id',
        'major_name',
        'education_levels',
        'max_score',
        'min_score',
        'average_score',
        'capacity'
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'university_id');
    }

}

