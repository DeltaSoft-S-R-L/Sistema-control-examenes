<?php

namespace Database\Seeders;

use App\Models\NormaExamen;
use Illuminate\Database\Seeder;

class NormaExamenSeeder extends Seeder
{
    public function run(): void
    {
        NormaExamen::factory()->count(2)->general()->create();

        NormaExamen::factory()->count(2)->particular()->create();
    }
}
