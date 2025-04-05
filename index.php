<?php

// // for ($i=1; $i <= 89; $i++) { 
// //     $sitemapUrl = 'https://haiphongtech.vn/product-sitemap'.$i.'.xml';  // Thay đổi URL tới Sitemap của bạn

// //     // Tải tệp XML từ URL
// //     $sitemapXml = simplexml_load_file($sitemapUrl);

// //     foreach ($sitemapXml->url as $url) {
// //         // Lấy giá trị của thẻ <loc>, đây là URL
// //         echo (string) $url->loc. "\n";
// //     }
// // }


// for($i = 2; $i <= 4; $i++) {
//     $url = 'https://baa.vn/vn/Category/cam-bien-vung-autonics_110_378/page/'.$i.'/';

//     // Lấy HTML với User-Agent
//     $options = [
//         'http' => [
//             'method' => 'GET',
//             'header' => "User-Agent: Mozilla/5.0\r\n"
//         ]
//     ];
//     $context = stream_context_create($options);
//     $html = file_get_contents($url, false, $context);

//     if (!$html) {
//         die("Không thể lấy HTML từ trang.");
//     }

//     // ✨ Ép mã hóa về UTF-8 cho DOMDocument
//     $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

//     libxml_use_internal_errors(true);
//     $doc = new DOMDocument();
//     $doc->loadHTML($html);
//     libxml_clear_errors();

//     // ✅ Tạo XPath sau khi loadHTML
//     $xpath = new DOMXPath($doc);

//     // Truy vấn tất cả <a class="card product__card">
//     $nodes = $xpath->query('//a[contains(@class, "card") and contains(@class, "product__card")]');

//     // Lặp và in ra các href
//     foreach ($nodes as $node) {
//         $href = $node->getAttribute('href');
//         echo $href . "<br>";
//     }
// }





// function isIndexedByGoogle($url) {
//     $query = 'site:' . urlencode($url);
//     $googleUrl = "https://www.google.com/search?q={$query}";

//     $context = stream_context_create([
//         'http' => [
//             'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
//         ]
//     ]);

//     $html = @file_get_contents($googleUrl, false, $context);
//     echo '<pre>' . htmlentities($html) . '</pre>'; exit();
    
//     if (!$html || strpos($html, "unusual traffic") !== false) {
//         return '⚠️ Bị Google chặn';
//     }

//     // Load HTML vào DOM
//     libxml_use_internal_errors(true);
//     $dom = new DOMDocument();
//     $dom->loadHTML($html);
//     libxml_clear_errors();

//     // Tìm tất cả các thẻ <a> trong kết quả tìm kiếm
//     $xpath = new DOMXPath($dom);
//     $nodes = $xpath->query('//div[@class="g"]//a');

//     foreach ($nodes as $node) {
//         $link = $node->getAttribute('href');
//         if (strpos($link, rtrim($url, '/')) !== false) {
//             return '✅ ĐÃ INDEX';
//         }
//     }

//     return '❌ CHƯA INDEX';
// }

// // ✅ Test
// $urls = [
//     'https://haiphongtech.vn/product/c200h-id212/',
//     'https://haiphongtech.vn/product/cp2e-n14dt1-d/',
//     'https://haiphongtech.vn/product/san-pham-khong-co/'
// ];

// foreach ($urls as $url) {
//     sleep(1); // để tránh bị chặn
//     $status = isIndexedByGoogle($url);
//     echo $url . ' => ' . $status . PHP_EOL;
// }





?>


