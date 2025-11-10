<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ParkingSpace;

class ParkingSpaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Seeding table with 10 parking spaces.
        for($i = 1; $i <= 10; $i++)
        {
            //Check if exists 'first' and return, if not, create.
            ParkingSpace::firstOrCreate(['space' => "Space Nr. {$i}"]);
        }
    }
}
