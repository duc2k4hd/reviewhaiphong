<?php

namespace App\Exports;

use App\Models\Post;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PostsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Post::query()->with(['category', 'account'])->orderBy('created_at', 'desc');
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tiêu đề',
            'Slug',
            'Nội dung',
            'SEO Title',
            'SEO Description',
            'SEO Keywords',
            'SEO Image',
            'Tags',
            'Danh mục',
            'Trạng thái',
            'Ngày xuất bản',
            'Lượt xem',
            'Type'
        ];
    }

    public function map($post): array
    {
        return [
            $post->id,
            $post->name,
            $post->slug,
            $post->content,
            $post->seo_title,
            $post->seo_desc,
            $post->seo_keywords,
            $post->seo_image,
            $post->tags,
            $post->category ? $post->category->slug : '',
            $post->status,
            $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('Y-m-d H:i:s') : '',
            $post->views,
            $post->type
        ];
    }
}
