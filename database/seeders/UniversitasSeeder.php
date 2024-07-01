<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UniversitasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        set_time_limit(500);
        require_once base_path('app/Http/Controllers/Universitas/university.php');

        foreach ($universities as $univ) {
            University::create([
                "university_name" => $univ['university_name']
            ]);
        }
    }
}
