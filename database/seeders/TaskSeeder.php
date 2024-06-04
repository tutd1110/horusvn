<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tasks')->insert([
            'code' => 'PKMG',
            'name' => 'Phụ kiện Machine Gun',
            'start_time' => '2022/08/04 00:00:00',
            'end_time' => '2022/08/04 01:00:00',
            'time' => 1,
            'description' => '<p>Link Design<p>',
            'priority' => 1,
            'sticker_id' => 1,
            'department_id' => 4,
            'weight' => 1,
            'project_id' => 1,
            'task_parent' => 2,
            'user_id' => 1,
            'created_at' => '2022/08/02 10:00:40',
            'updated_at' => '2022/08/02 10:00:40',
            'status' => 1,
            'real_start_time' => '2022/09/05 21:41:44',
            'real_end_time' => '2022/09/06 21:41:44',
            'time_pause' => 1,
            'real_time' => 1,
            'deleted_by' => 1,
            'progress' => 1,
        ]);
    }
}
