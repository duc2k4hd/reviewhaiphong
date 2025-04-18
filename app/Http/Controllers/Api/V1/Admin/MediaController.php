<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        function convertToSEOName($string)
        {
            // Loại bỏ dấu tiếng Việt
            $unwanted_array = [
                'à' => 'a',
                'á' => 'a',
                'ạ' => 'a',
                'ả' => 'a',
                'ã' => 'a',
                'â' => 'a',
                'ầ' => 'a',
                'ấ' => 'a',
                'ậ' => 'a',
                'ẩ' => 'a',
                'ẫ' => 'a',
                'ă' => 'a',
                'ằ' => 'a',
                'ắ' => 'a',
                'ặ' => 'a',
                'ẳ' => 'a',
                'ẵ' => 'a',
                'è' => 'e',
                'é' => 'e',
                'ẹ' => 'e',
                'ẻ' => 'e',
                'ẽ' => 'e',
                'ê' => 'e',
                'ề' => 'e',
                'ế' => 'e',
                'ệ' => 'e',
                'ể' => 'e',
                'ễ' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'ị' => 'i',
                'ỉ' => 'i',
                'ĩ' => 'i',
                'ò' => 'o',
                'ó' => 'o',
                'ọ' => 'o',
                'ỏ' => 'o',
                'õ' => 'o',
                'ô' => 'o',
                'ồ' => 'o',
                'ố' => 'o',
                'ộ' => 'o',
                'ổ' => 'o',
                'ỗ' => 'o',
                'ơ' => 'o',
                'ờ' => 'o',
                'ớ' => 'o',
                'ợ' => 'o',
                'ở' => 'o',
                'ỡ' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'ụ' => 'u',
                'ủ' => 'u',
                'ũ' => 'u',
                'ư' => 'u',
                'ừ' => 'u',
                'ứ' => 'u',
                'ự' => 'u',
                'ử' => 'u',
                'ữ' => 'u',
                'ỳ' => 'y',
                'ý' => 'y',
                'ỵ' => 'y',
                'ỷ' => 'y',
                'ỹ' => 'y',
                'đ' => 'd',
            ];

            // Thay thế tất cả các ký tự có dấu bằng ký tự không dấu
            $string = strtr($string, $unwanted_array);

            // Chuyển tất cả các ký tự còn lại thành chữ thường và bỏ ký tự không phải là chữ cái hoặc số
            $string = strtolower($string);

            // Thay thế các ký tự đặc biệt và khoảng trắng thành dấu gạch ngang
            $string = preg_replace('/[^a-z0-9]+/', '-', $string);

            // Xóa dấu gạch ngang thừa ở đầu và cuối
            $string = trim($string, '-');

            return $string;
        }
        if ($request->hasFile('image')) {
            $images = $request->file('image');
            $imageUrls = [];

            if (is_array($images)) {
                foreach ($images as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $timestamp = time();

                    $safeName = convertToSEOName($originalName);
                    $filename = $safeName . '-' . $timestamp . '.' . $extension;
                    $destinationPath = public_path('client/assets/images/posts/');

                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    $file->move($destinationPath, $filename);
                    $imageUrls[] = asset('client/assets/images/posts/' . $filename);
                }
            } else {
                $file = $images;
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $timestamp = time();

                $safeName = convertToSEOName($originalName);
                $filename = $safeName . '-' . $timestamp . '.' . $extension;
                $destinationPath = public_path('client/assets/images/posts/');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $filename);
                $imageUrls[] = asset('client/assets/images/posts/' . $filename);
            }

            return response()->json([
                'urls' => $imageUrls,
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
