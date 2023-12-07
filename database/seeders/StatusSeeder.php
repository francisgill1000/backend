<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => 'Approved', 'code' => 'A', 'icon' => 'mdi-check', 'model' => 'Visitor'],
            ['name' => 'Pending', 'code' => 'P', 'icon' => 'mdi-clock', 'model' => 'Visitor'],
            ['name' => 'Canceled', 'code' => 'C', 'icon' => 'mdi-cancel', 'model' => 'Visitor'],
            ['name' => 'Rejected', 'code' => 'R', 'icon' => 'mdi-cancel', 'model' => 'Visitor'],
        ];

        Status::insert($data);
    }
}
