<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'reina.n@coachtech.com',
                'name' => '西 伶奈',
                'password' => 'password001',
            ],
            [
                'email' => 'taro.y@coachtech.com',
                'name' => '山田 太郎',
                'password' => 'password002',
            ],
            [
                'email' => 'issei.m@coachtech.com',
                'name' => '増田 一世',
                'password' => 'password003',
            ],
            [
                'email' => 'keikichi.y@coachtech.com',
                'name' => '山本 敬吉',
                'password' => 'password004',
            ],
            [
                'email' => 'tomomi.a@coachtech.com',
                'name' => '秋田 朋美',
                'password' => 'password005',
            ],
            [
                'email' => 'norio.n@coachtech.com',
                'name' => '中西 教夫',
                'password' => 'password006',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
