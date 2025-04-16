<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('assets/images/posts', 'public'); // lưu vào storage/app/public/assets/images/posts
            return response()->json([
                'url' => asset('storage/' . $path), // dùng 'storage/' vì Laravel dùng symbolic link: public/storage → storage/app/public
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
