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
                'avatar' => 'https://cdn3.ivivu.com/2015/11/20-hinh-anh-tuyet-dep-ve-Viet-Nam-ivivu-2.jpg'
            ]
        );
        User::updateOrCreate(
            ['email' => 'anan@gmail.com'],
            [
                'name' => 'An An',
                'password' => Hash::make('123456'),
                'avatar' => 'https://hinhanhonline.com/Hinhanh/images15/AnhMB/co-gai-voi-chiec-vay-do-va-hoa-hong-choi-loa.jpg'
            ]
        );
        User::updateOrCreate(
            ['email' => 'tranan@gmail.com'],
            [
                'name' => 'Trần An',
                'password' => Hash::make('123456'),
                'avatar' => 'https://cdn11.dienmaycholon.vn/filewebdmclnew/public/userupload/files/Image%20FP_2024/hinh-anh-avatar-ca-tinh-nu-2.jpg'
            ]
        );
    }
}
