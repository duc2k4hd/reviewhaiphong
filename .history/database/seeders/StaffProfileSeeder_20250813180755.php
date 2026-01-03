<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Profile;

class StaffProfileSeeder extends Seeder
{
    public function run(): void
    {
        // Tìm tài khoản Staff đã tồn tại
        $staffAccount = Account::where('username', 'staff')->first();
        
        if ($staffAccount) {
            // Kiểm tra xem đã có Profile chưa
            $existingProfile = Profile::where('account_id', $staffAccount->id)->first();
            
            if (!$existingProfile) {
                // Tạo Profile cho Staff
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
                
                $this->command->info('Profile cho Staff đã được tạo thành công!');
            } else {
                $this->command->info('Staff đã có Profile rồi!');
            }
        } else {
            $this->command->error('Không tìm thấy tài khoản Staff!');
        }
    }
}








