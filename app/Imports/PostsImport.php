<?php

namespace App\Imports;

use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public $errors = [];
    public $skipCount = 0;
    public $successCount = 0;

    public function collection(Collection $rows)
    {
        $existingSlugs = Post::pluck('id', 'slug')->toArray();
        $categories = Category::pluck('id', 'slug')->toArray();
        $inserts = [];
        $upserts = [];
        $now = now();
        $accountId = Auth::id() ?: 1;

        foreach ($rows as $row) {
            $rowIndex = $row['_row_index'] ?? '?';
            $name = $row['tieu_de'] ?? null;
            
            if (empty($name)) {
                $this->skipCount++;
                continue;
            }

            $id = !empty($row['id']) ? (int) $row['id'] : null;
            $slug = !empty($row['slug']) ? Str::slug($row['slug']) : Str::slug($name);

            // Logic tìm ID và chống trùng lặp Slug
            if (!$id && isset($existingSlugs[$slug]) && $existingSlugs[$slug] > 0) {
                // 1. Cập nhật theo Slug: Không có ID nhưng Slug đã có trong DB -> Lấy ID cũ để Update
                $id = $existingSlugs[$slug];
            } else if (!$id) {
                // 2. Tạo mới: Không có ID và Slug chưa tồn tại -> Chống trùng lặp Slug nếu tên giống nhau
                $originalSlug = $slug;
                $counter = 1;
                while (isset($existingSlugs[$slug])) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                $existingSlugs[$slug] = -rand(1, 999999); // Giữ chỗ tạm thời trong lô
            }

            $categorySlug = $row['danh_muc'] ?? $row['danh_muc_slug'] ?? null;
            $categoryId = !empty($categorySlug) && isset($categories[$categorySlug]) ? $categories[$categorySlug] : null;

            if (!$categoryId) {
                $this->errors[] = "Dòng {$rowIndex}: Danh mục '{$categorySlug}' không tồn tại. Bài viết: {$name}";
                $this->skipCount++;
                continue;
            }

            $status = strtolower($row['trang_thai'] ?? '');
            $validStatuses = ['draft', 'published', 'archived', 'pending'];
            if (!in_array($status, $validStatuses)) {
                $status = 'draft';
            }

            $publishedAt = null;
            if (!empty($row['ngay_xuat_ban'])) {
                try {
                    $publishedAt = \Carbon\Carbon::parse($row['ngay_xuat_ban']);
                } catch (\Exception $e) {
                    $publishedAt = null;
                }
            }

            $item = [
                'name' => $name,
                'slug' => $slug,
                'content' => $row['noi_dung'] ?? '',
                'seo_title' => $row['seo_title'] ?: $name,
                'seo_desc' => $row['seo_description'] ?? '',
                'seo_keywords' => $row['seo_keywords'] ?? '',
                'seo_image' => $row['seo_image'] ?? '',
                'tags' => $row['tags'] ?? '',
                'category_id' => $categoryId,
                'status' => $status,
                'published_at' => $publishedAt,
                'views' => isset($row['luot_xem']) ? max(0, (int)$row['luot_xem']) : 0,
                'type' => $row['type'] ?? '',
                'account_id' => $accountId, // Mặc định người upload là tác giả nếu tạo mới
                'last_updated_by' => $accountId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($id) {
                $item['id'] = $id;
                // Khi upsert, nếu muốn giữ nguyên account_id cũ thì cần query trước, nhưng vì chúng ta đang tối ưu tốc độ,
                // ta tạm thời dùng account_id hiện tại luôn, hoặc bỏ qua cập nhật account_id nếu upsert bằng cách không đưa vào array update
                $upserts[] = $item;
            } else {
                $inserts[] = $item;
            }
            
            $this->successCount++;
        }

        // Thực thi SQL Bulk Insert (nhanh gấp hàng nghìn lần)
        if (count($inserts) > 0) {
            Post::insert($inserts);
        }
        
        if (count($upserts) > 0) {
            // Khi upsert, không cập nhật cột account_id để giữ nguyên tác giả ban đầu
            Post::upsert(
                $upserts, 
                ['id'], 
                ['name', 'slug', 'content', 'seo_title', 'seo_desc', 'seo_keywords', 'seo_image', 'tags', 'category_id', 'status', 'published_at', 'views', 'type', 'last_updated_by', 'updated_at']
            );
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
