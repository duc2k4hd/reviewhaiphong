<?php

namespace App\Imports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;

class CategoryImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public function collection(Collection $rows)
    {
        $existingSlugs = Category::pluck('id', 'slug')->toArray();
        $inserts = [];
        $upserts = [];
        $now = now();

        foreach ($rows as $row) {
            $name = $row['ten_danh_muc'] ?? null;
            if (empty($name)) continue;

            $id = !empty($row['id']) ? (int) $row['id'] : null;
            $slug = !empty($row['slug']) ? Str::slug($row['slug']) : Str::slug($name);

            // Kiểm tra trùng lặp slug (cực nhanh bằng mảng trong RAM)
            $originalSlug = $slug;
            $counter = 1;
            while (isset($existingSlugs[$slug]) && $existingSlugs[$slug] !== $id) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $existingSlugs[$slug] = $id ?: -rand(1, 999999); // Giữ chỗ

            $item = [
                'name' => $name,
                'slug' => $slug,
                'parent_id' => !empty($row['id_danh_muc_cha']) ? (int) $row['id_danh_muc_cha'] : null,
                'description' => $row['mo_ta'] ?? null,
                'status' => (!empty($row['trang_thai']) && in_array(strtolower($row['trang_thai']), ['active', 'inactive'])) ? strtolower($row['trang_thai']) : 'active',
                'sort_order' => isset($row['thu_tu']) ? (int) $row['thu_tu'] : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($id) {
                $item['id'] = $id;
                $upserts[] = $item;
            } else {
                $inserts[] = $item;
            }
        }

        // Thực thi SQL Bulk Insert (nhanh gấp hàng nghìn lần)
        if (count($inserts) > 0) {
            Category::insert($inserts);
        }
        if (count($upserts) > 0) {
            Category::upsert($upserts, ['id'], ['name', 'slug', 'parent_id', 'description', 'status', 'sort_order', 'updated_at']);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
