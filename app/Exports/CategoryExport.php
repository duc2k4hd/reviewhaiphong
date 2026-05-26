<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoryExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Category::query()->orderBy('sort_order', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tên danh mục',
            'Slug',
            'ID Danh mục cha',
            'Mô tả',
            'Trạng thái',
            'Thứ tự',
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->slug,
            $category->parent_id,
            $category->description,
            $category->status,
            $category->sort_order,
        ];
    }
}
