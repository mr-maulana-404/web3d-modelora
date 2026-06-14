<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Wallet;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin#123'),
            'usertype' => 'admin',
        ]);
        Wallet::firstOrCreate(['user_id' => $admin->id], ['credits' => 30]);

        // User 
        $user = User::create([
            'name' => 'User Biasa',
            'email' => 'user@gmail.com',
            'password' => Hash::make('user#123'),
            'usertype' => 'user',
        ]);
        Wallet::firstOrCreate(['user_id' => $user->id], ['credits' => 30]);
    }
}
