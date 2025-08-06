<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keyword = trim($request->keyword);
        
        // Validate keyword
        if (empty($keyword) || strlen($keyword) < 2) {
            return redirect()->back()->with('error', 'Từ khóa tìm kiếm phải có ít nhất 2 ký tự.');
        }

        try {
            // Cache search results for 15 minutes
            $cacheKey = 'search_' . md5(strtolower($keyword));
            
            $posts = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($keyword) {
                return $this->performSearch($keyword);
            });

            // Get popular posts with caching
            $postsViews = Cache::remember(
                'popular_posts_search', 
                now()->addMinutes(30), 
                function () {
                    return Post::select([
                        'id', 'category_id', 'account_id', 'views', 'slug',
                        'seo_title', 'seo_desc', 'seo_image', 'published_at'
                    ])
                    ->with([
                        'category:id,name,slug',
                        'account:id,username',
                        'account.profile:id,account_id,name,avatar'
                    ])
                    ->published()
                    ->popular(10)
                    ->get();
                }
            );

        } catch (\Throwable $th) {
            \Log::error('Search error: ' . $th->getMessage());
            return view('client.templates.errors.404');
        }

        return view('client.layouts.search', compact('posts', 'keyword', 'postsViews'));
    }

    private function performSearch(string $keyword): \Illuminate\Database\Eloquent\Collection
    {
        // Try fulltext search first (if available)
        $posts = $this->fullTextSearch($keyword);
        
        // If no results, fall back to LIKE search
        if ($posts->isEmpty()) {
            $posts = $this->likeSearch($keyword);
        }

        return $posts;
    }

    private function fullTextSearch(string $keyword): \Illuminate\Database\Eloquent\Collection
    {
        // Check if fulltext index exists (you may need to create this index)
        try {
            return Post::select([
                'id', 'category_id', 'account_id', 'name', 'slug',
                'seo_title', 'seo_desc', 'seo_image', 'published_at'
            ])
            ->with([
                'category:id,name,slug', 
                'account:id,username', 
                'account.profile:id,name,account_id,avatar'
            ])
            ->published()
            ->whereRaw("MATCH(seo_title, seo_desc, content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$keyword])
            ->orderByRaw("MATCH(seo_title, seo_desc, content) AGAINST(? IN NATURAL LANGUAGE MODE) DESC", [$keyword])
            ->limit(50)
            ->get();
        } catch (\Exception $e) {
            // Fulltext index doesn't exist, return empty collection
            return collect();
        }
    }

    private function likeSearch(string $keyword): \Illuminate\Database\Eloquent\Collection
    {
        $searchTerms = explode(' ', $keyword);
        
        return Post::select([
            'id', 'category_id', 'account_id', 'name', 'slug',
            'seo_title', 'seo_desc', 'seo_image', 'published_at'
        ])
        ->with([
            'category:id,name,slug', 
            'account:id,username', 
            'account.profile:id,name,account_id,avatar'
        ])
        ->published()
        ->where(function ($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $term = trim($term);
                if (!empty($term)) {
                    $query->orWhere('seo_title', 'like', "%{$term}%")
                          ->orWhere('seo_desc', 'like', "%{$term}%")
                          ->orWhere('content', 'like', "%{$term}%");
                }
            }
        })
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();
    }
}
