<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('project_users')->insert([
            'project_id' => 1,
            'user_id' => 1,
            'created_at' => '2022/08/02 10:00:40',
            'updated_at' => '2022/08/02 10:00:40',
        ]);
    }
}
