<?php

namespace Database\Seeders;

use App\Models\Major;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        set_time_limit(500);
        require_once base_path('app/Http/Controllers/Universitas/majors.php');

        foreach ($majors as $maj) {
            Major::create([
                "university_id" => $maj['university_id'],
                "major_name" => $maj['major_name'],
                "education_levels" => $maj['education_levels'],
                "max_score" => $maj['max_score'],
                "min_score" => $maj['min_score'],
                "average_score" => $maj['average_score'],
            ]);
        }
    }
}
