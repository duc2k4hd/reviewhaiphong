<?php

use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Client\GeneralApiController;
use App\Http\Controllers\Api\V1\Client\NewsDetailApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Admin
Route::post('/media/upload', [MediaController::class, 'upload'])->name('api.admin.media.upload');


// Client
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/category', [GeneralApiController::class, 'category'])->name('api.client.category');
Route::get('/posts-new', [GeneralApiController::class, 'postsNew'])->name('api.client.posts.new');
Route::get('/posts-category', [GeneralApiController::class, 'postsCategory'])->name('api.client.posts.category');
Route::get('/{category}/{slug}', [NewsDetailApiController::class, 'newsDetail'])->name('api.client.post.detail');
Route::get('/feutured-posts', [GeneralApiController::class,'featuredPosts'])->name('api.client.posts.featured');