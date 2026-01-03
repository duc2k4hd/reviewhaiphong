<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Review Hải Phòng', 'slug' => '/'],
            ['name' => 'Du lịch', 'slug' => 'du-lich'],
            ['name' => 'Ẩm thực', 'slug' => 'am-thuc'],
            ['name' => 'Check-in', 'slug' => 'check-in'],
            ['name' => 'Dịch vụ', 'slug' => 'dich-vu'],
            ['name' => 'Tin tức', 'slug' => 'tin-tuc'],
            ['name' => 'Giới thiệu', 'slug' => 'gioi-thieu'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name']]
            );
        }
    }
}



