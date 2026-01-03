<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

class StaffAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo tài khoản Staff
        $staffAccount = Account::create([
            'username' => 'staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('123456'),
            'role_id' => 2, // Staff role
        ]);

        // Tạo profile cho Staff
        Profile::create([
            'account_id' => $staffAccount->id,
            'name' => 'Nhân viên Staff',
            'age' => 25,
            'address' => 'Hải Phòng, Việt Nam',
            'phone' => '0123456789',
            'bio' => 'Tôi là nhân viên Staff, chuyên viết bài viết cho website.',
            'avatar' => 'avatar-admin.webp',
            'cover_photo' => 'cover.jpg',
            'social_link' => 'https://facebook.com/staff',
        ]);

        $this->command->info('Tài khoản Staff đã được tạo thành công!');
        $this->command->info('Username: staff');
        $this->command->info('Password: 123456');
        $this->command->info('Email: staff@example.com');
    }
}
