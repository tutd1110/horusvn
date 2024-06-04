<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'fullname' => 'iamadmin',
            'date_official' => Carbon::now()->format('Y/m/d'),
            'phone' => '0966666666',
            'email' => 'iamadmin@horusvn.com',
            'department_id' => 4,
            'birthday' => Carbon::create('2000/1/1')->format('Y/m/d'),
            'permission' => 1,
            'position' => 3,
            'email_verified_at' => null,
            'place_id' => 3073,
            'place_name' => 'VOV Me Tri',
            'face_image_url' => '',
            'avatar' => 'iamadmin.png',
            'password' => '$2y$10$fl8hSP5Oqf.tTn9OnxZVZOdGHNdkye7CMs/KKlk5XM1nghwNOuGeW',
            'remember_token' => null,
            'check_type' => 1,
            'user_code' => '609125962f1b1In4P',
            'wage_now' => 0,
            'user_status' => 1,
        ]);
    }
}
