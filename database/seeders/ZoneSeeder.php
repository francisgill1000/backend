<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => '1st floor'],
            ['name' => '2nd floor'],
            ['name' => '1st and 2nd floor'],
        ];

        Zone::insert($data);
    }
}
