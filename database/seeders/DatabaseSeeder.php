<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'tranbinhan1997@gmail.com'],
            [
                'name' => 'Trần Bình An',
                'password' => Hash::make('123456'),
            ]
        );
        User::updateOrCreate(
            ['email' => 'anan@gmail.com'],
            [
                'name' => 'An An',
                'password' => Hash::make('123456'),
            ]
        );
    }
}
