<?php

namespace App\Imports;

use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PostsImport
{
    protected $errors = [];
    protected $successCount = 0;
    protected $skipCount = 0;

    public function import($filePath)
    {
        try {
            // Kiểm tra file tồn tại
            if (!file_exists($filePath)) {
                throw new \Exception('File Excel không tồn tại: ' . $filePath);
            }

            // Kiểm tra file có thể đọc được
            if (!is_readable($filePath)) {
                throw new \Exception('Không thể đọc file Excel. Vui lòng kiểm tra quyền truy cập.');
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows) || count($rows) < 2) {
                throw new \Exception('File Excel không có dữ liệu hoặc thiếu header.');
            }

            // Skip header row (row 1)
            $dataRows = array_slice($rows, 1);

            foreach ($dataRows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // +2 vì bỏ qua header và index bắt đầu từ 0
                
                try {
                    // Validate và import row
                    $this->importRow($row, $rowNumber);
                } catch (\Exception $e) {
                    $this->errors[] = "Dòng {$rowNumber}: " . $e->getMessage();
                    Log::error("PostsImport - Dòng {$rowNumber}: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'successCount' => $this->successCount,
                'skipCount' => $this->skipCount,
                'errors' => $this->errors,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi đọc file Excel: ' . $e->getMessage(),
                'errors' => $this->errors,
            ];
        }
    }

    protected function importRow($row, $rowNumber)
    {
        // Map columns: ID, Tiêu đề, Slug, Nội dung, SEO Title, SEO Description, SEO Keywords, SEO Image, Tags, Danh mục, Trạng thái, Ngày xuất bản, Lượt xem, Type
        $id = isset($row[0]) ? trim((string)$row[0]) : null;
        $name = isset($row[1]) ? trim((string)$row[1]) : '';
        $slug = isset($row[2]) ? trim((string)$row[2]) : '';
        $content = isset($row[3]) ? trim((string)$row[3]) : '';
        $seoTitle = isset($row[4]) ? trim((string)$row[4]) : '';
        $seoDesc = isset($row[5]) ? trim((string)$row[5]) : '';
        $seoKeywords = isset($row[6]) ? trim((string)$row[6]) : '';
        $seoImage = isset($row[7]) ? trim((string)$row[7]) : '';
        $tags = isset($row[8]) ? trim((string)$row[8]) : '';
        $categorySlug = isset($row[9]) ? trim((string)$row[9]) : '';
        $status = isset($row[10]) ? trim((string)$row[10]) : 'draft';
        $publishedAt = isset($row[11]) ? trim((string)$row[11]) : '';
        $views = isset($row[12]) ? (int)$row[12] : 0;
        $type = isset($row[13]) ? trim((string)$row[13]) : '';

        // Validation
        if (empty($name)) {
            throw new \Exception('Tiêu đề không được để trống');
        }

        if (empty($slug)) {
            // Tự động tạo slug từ tên
            $slug = Str::slug($name);
        } else {
            // Validate slug format
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                throw new \Exception('Slug không hợp lệ. Slug chỉ được chứa chữ thường, số và dấu gạch ngang.');
            }
        }

        // Tìm bài viết đã tồn tại để UPDATE thay vì tạo mới
        $existingPost = null;
        
        // Ưu tiên 1: Tìm theo ID nếu có
        if (!empty($id) && is_numeric($id)) {
            $existingPost = Post::find($id);
        }
        
        // Ưu tiên 2: Nếu không tìm thấy theo ID, tìm theo slug
        if (!$existingPost) {
            $existingPost = Post::where('slug', $slug)->first();
        }
        
        // Nếu tìm thấy bài viết theo ID hoặc slug, kiểm tra slug có trùng với bài viết khác không
        if ($existingPost) {
            // Nếu slug thay đổi và slug mới đã tồn tại ở bài viết khác -> Lỗi
            if ($existingPost->slug !== $slug) {
                $slugExists = Post::where('slug', $slug)->where('id', '!=', $existingPost->id)->exists();
                if ($slugExists) {
                    throw new \Exception("Slug '{$slug}' đã tồn tại ở bài viết khác (ID: {$existingPost->id}).");
                }
            }
        } else {
            // Nếu không tìm thấy bài viết nào, kiểm tra slug có trùng không (để tránh tạo mới trùng)
            $slugExists = Post::where('slug', $slug)->exists();
            if ($slugExists) {
                // Nếu slug trùng nhưng không tìm thấy bài viết -> có thể do lỗi, bỏ qua
                $this->skipCount++;
                throw new \Exception("Slug '{$slug}' đã tồn tại nhưng không tìm thấy bài viết. Bỏ qua dòng này.");
            }
        }

        // Validate status
        $validStatuses = ['draft', 'published', 'archived', 'pending'];
        if (!in_array($status, $validStatuses)) {
            $status = 'draft';
        }

        // Validate category
        $categoryId = null;
        if (!empty($categorySlug)) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $categoryId = $category->id;
            } else {
                throw new \Exception("Danh mục với slug '{$categorySlug}' không tồn tại.");
            }
        }

        // Parse published_at
        $publishedAtDate = null;
        if (!empty($publishedAt)) {
            try {
                $publishedAtDate = \Carbon\Carbon::parse($publishedAt);
            } catch (\Exception $e) {
                // Nếu không parse được, dùng null
                $publishedAtDate = null;
            }
        }

        // Get current user account - sử dụng Auth facade
        $account = \Illuminate\Support\Facades\Auth::user();
        if (!$account) {
            throw new \Exception('Không tìm thấy thông tin người dùng.');
        }

        // Prepare data
        $data = [
            'name' => $name,
            'slug' => $slug,
            'content' => $content,
            'seo_title' => $seoTitle ?: $name,
            'seo_desc' => $seoDesc,
            'seo_keywords' => $seoKeywords,
            'seo_image' => $seoImage,
            'tags' => $tags,
            'category_id' => $categoryId,
            'status' => $status,
            'published_at' => $publishedAtDate,
            'views' => max(0, $views),
            'type' => $type,
            'account_id' => $account->id,
            'last_updated_by' => $account->id,
        ];

        // UPDATE nếu đã tồn tại, CREATE nếu chưa có
        if ($existingPost) {
            // UPDATE: Giữ nguyên account_id gốc nếu không thay đổi, chỉ cập nhật last_updated_by
            $data['account_id'] = $existingPost->account_id; // Giữ nguyên tác giả gốc
            $existingPost->update($data);
            $this->successCount++;
            Log::info("PostsImport - Cập nhật bài viết ID: {$existingPost->id}, Slug: {$slug}");
        } else {
            // CREATE: Tạo mới bài viết
            Post::create($data);
            $this->successCount++;
            Log::info("PostsImport - Tạo mới bài viết, Slug: {$slug}");
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}

