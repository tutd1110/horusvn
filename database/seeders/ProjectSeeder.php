<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('projects')->insert([
            'name' => 'WZ 0.0.1',
            'code' => 'W001',
            'start_date' => '2022/08/04 00:00:00',
            'end_date' => '2023/08/04 01:00:00',
            'project_day' => '364',
            'description' => '<p>Link Design<p>',
            'created_at' => '2022/08/02 10:00:40',
            'updated_at' => '2022/08/02 10:00:40',
        ]);
    }
}
