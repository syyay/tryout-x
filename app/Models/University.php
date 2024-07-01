<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class University extends Model
{
    use HasFactory;

    protected $table = "university";
    protected $primaryKey = "university_id";

    protected $fillable = [
        'university_name'
    ];

    public function user_pkgs(): HasMany
    {
        return $this->hasMany(UserPKG::class, 'university_id');
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class, 'university_id');
    }


}
