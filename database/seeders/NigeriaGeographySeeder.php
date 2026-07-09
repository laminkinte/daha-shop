<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\State;
use Illuminate\Database\Seeder;

class NigeriaGeographySeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/nigeria_states_lgas.php');

        foreach ($data as $stateData) {
            $state = State::create([
                'name' => $stateData['name'],
                'code' => $stateData['code'],
            ]);

            $state->lgas()->createMany(
                array_map(fn (string $name) => ['name' => $name], $stateData['lgas'])
            );
        }
    }
}
