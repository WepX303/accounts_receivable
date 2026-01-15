<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@gmail.com';
        $phone = '99362624628';

        // Eğer email veya telefon zaten varsa tekrar oluşturma
        if (User::where('email', $email)->orWhere('phonenumber', $phone)->exists()) {
            return;
        }

        User::create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => $email,

            'phonenumber' => $phone,
            'position' => 'Administrator',

            'role' => UserRoleEnum::ADMIN->value, 
            'status' => true,

            'password' => Hash::make('12341234'),

            'token' => null,
            'token_expires_at' => null,
        ]);
    }
}
