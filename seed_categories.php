<?php
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');
Category::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

$data = [
    'Ẩm thực' => [
        'Món ngon Hải Phòng', 'Đặc sản Hải Phòng', 'Quán ăn', 'Nhà hàng', 'Hải sản', 'Lẩu - Nướng', 'Ăn vặt', 'Ăn sáng', 'Ăn đêm', 'Buffet', 'Đồ ăn healthy', 'Đồ ăn gia đình', 'Quán bình dân', 'Quán vỉa hè', 'Review món ăn'
    ],
    'Check-in' => [
        'Địa điểm check-in', 'Góc sống ảo', 'Địa điểm chụp ảnh đẹp', 'Check-in ngoài trời', 'Check-in trong nhà', 'Địa điểm hẹn hò', 'Địa điểm đi chơi cuối tuần', 'Địa điểm cho gia đình', 'Địa điểm cho nhóm bạn', 'Địa điểm miễn phí', 'Công viên', 'Bãi biển', 'Phố đẹp', 'Công trình nổi bật', 'Góc ảnh Hải Phòng'
    ],
    'Du lịch' => [
        'Du lịch Hải Phòng', 'Địa điểm du lịch', 'Kinh nghiệm du lịch', 'Lịch trình du lịch', 'Du lịch tự túc', 'Du lịch cuối tuần', 'Du lịch gia đình', 'Du lịch cặp đôi', 'Cát Bà', 'Đồ Sơn', 'Vịnh Lan Hạ', 'Tour du lịch', 'Vé tham quan', 'Phương tiện di chuyển', 'Cẩm nang du lịch'
    ],
    'Cafe - Đồ uống' => [
        'Quán cafe', 'Cafe view đẹp', 'Cafe học bài', 'Cafe làm việc', 'Cafe check-in', 'Cafe sân vườn', 'Cafe acoustic', 'Trà sữa', 'Trà chanh', 'Nước ép - Sinh tố', 'Chè - Kem', 'Đồ uống mang đi', 'Quán chill', 'Quán đồ uống giá tốt', 'Review đồ uống'
    ],
    'Dịch vụ' => [
        'Làm đẹp', 'Spa - Massage', 'Salon tóc', 'Nail - Mi', 'Sức khỏe', 'Phòng khám', 'Nha khoa', 'Sửa chữa', 'Vệ sinh - Dọn dẹp', 'Chuyển nhà', 'Cưới hỏi', 'Sự kiện', 'Xe cộ', 'Công nghệ', 'In ấn - Thiết kế', 'Chụp ảnh - Quay phim', 'Thú cưng', 'Dịch vụ đời sống'
    ],
    'Mua sắm' => [
        'Trung tâm thương mại', 'Siêu thị', 'Chợ', 'Cửa hàng tiện lợi', 'Thời trang', 'Mỹ phẩm', 'Giày dép - Phụ kiện', 'Mẹ và bé', 'Đồ gia dụng', 'Nội thất', 'Điện máy', 'Điện thoại - Máy tính', 'Quà tặng', 'Nhà sách', 'Đồ thể thao', 'Mua sắm online'
    ],
    'Giải trí' => [
        'Khu vui chơi', 'Rạp chiếu phim', 'Karaoke', 'Bar - Pub', 'Bida', 'Bowling', 'Game center', 'Công viên giải trí', 'Hoạt động cuối tuần', 'Picnic - Dã ngoại', 'Cắm trại', 'Câu cá', 'Thể thao', 'Phòng gym', 'Yoga - Pilates', 'Hoạt động gia đình'
    ],
    'Lưu trú' => [
        'Khách sạn', 'Homestay', 'Resort', 'Villa', 'Nhà nghỉ', 'Căn hộ dịch vụ', 'Lưu trú Cát Bà', 'Lưu trú Đồ Sơn', 'Lưu trú trung tâm', 'Lưu trú gần biển', 'Lưu trú giá tốt', 'Lưu trú cho gia đình', 'Lưu trú cho cặp đôi', 'Lưu trú cho nhóm', 'Review nơi ở'
    ],
    'Sự kiện' => [
        'Sự kiện Hải Phòng', 'Sự kiện cuối tuần', 'Lễ hội', 'Hội chợ', 'Triển lãm', 'Workshop', 'Khai trương', 'Âm nhạc', 'Thể thao', 'Văn hóa - Nghệ thuật', 'Sự kiện cộng đồng', 'Sự kiện trường học', 'Sự kiện doanh nghiệp', 'Ưu đãi - Khuyến mãi', 'Lịch sự kiện'
    ],
    'Khu vực' => [
        'Hồng Bàng', 'Lê Chân', 'Ngô Quyền', 'Hải An', 'Kiến An', 'Đồ Sơn', 'Dương Kinh', 'Thủy Nguyên', 'An Dương', 'An Lão', 'Kiến Thụy', 'Tiên Lãng', 'Vĩnh Bảo', 'Cát Hải', 'Cát Bà'
    ],
    'Tin tức - Chuyện ngoài lề' => [
        'Tin Hải Phòng', 'Tin đời sống', 'Tin giao thông', 'Tin đô thị', 'Tin du lịch', 'Tin ẩm thực', 'Tin mua sắm', 'Tin sự kiện', 'Câu chuyện địa phương', 'Người Hải Phòng', 'Chuyện phố phường', 'Góc chia sẻ', 'Góc review', 'Xu hướng', 'Kinh nghiệm hay', 'Mẹo vặt đời sống', 'Chuyện vui', 'Chuyện ngoài lề', 'Góc cộng đồng', 'Hải Phòng xưa và nay'
    ]
];

$sortOrder = 1;
function generateUniqueSlug($baseName) {
    $slug = Str::slug($baseName);
    $originalSlug = $slug;
    $counter = 1;
    while (Category::where('slug', $slug)->exists()) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    return $slug;
}

foreach ($data as $parentName => $children) {
    $parent = Category::create([
        'name' => $parentName,
        'slug' => generateUniqueSlug($parentName),
        'parent_id' => null, // Parent categories use null
        'status' => 'active',
        'sort_order' => $sortOrder++,
        'seo_title' => $parentName,
        'seo_desc' => $parentName . ' Hải Phòng',
    ]);
    
    $childSortOrder = 1;
    foreach ($children as $childName) {
        Category::create([
            'name' => $childName,
            'slug' => generateUniqueSlug($childName),
            'parent_id' => $parent->id,
            'status' => 'active',
            'sort_order' => $childSortOrder++,
            'seo_title' => $childName,
            'seo_desc' => $childName . ' Hải Phòng',
        ]);
    }
}

echo "Created " . count($data) . " parent categories and their subcategories.\n";
