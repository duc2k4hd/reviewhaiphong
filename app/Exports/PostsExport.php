<?php

namespace App\Exports;

use App\Models\Post;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PostsExport
{
    protected $posts;

    public function __construct($posts = null)
    {
        $this->posts = $posts ?? Post::with(['category', 'account'])->get();
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bài viết');

        // Header row
        $headers = [
            'ID',
            'Tiêu đề',
            'Slug',
            'Nội dung',
            'SEO Title',
            'SEO Description',
            'SEO Keywords',
            'SEO Image',
            'Tags',
            'Danh mục (Slug)',
            'Trạng thái',
            'Ngày xuất bản',
            'Lượt xem',
            'Type'
        ];

        // Set header style
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        // Write headers
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $sheet->getStyleByColumnAndRow($col, 1)->applyFromArray($headerStyle);
            $col++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // ID
        $sheet->getColumnDimension('B')->setWidth(40); // Tiêu đề
        $sheet->getColumnDimension('C')->setWidth(30); // Slug
        $sheet->getColumnDimension('D')->setWidth(50); // Nội dung
        $sheet->getColumnDimension('E')->setWidth(40); // SEO Title
        $sheet->getColumnDimension('F')->setWidth(50); // SEO Description
        $sheet->getColumnDimension('G')->setWidth(30); // SEO Keywords
        $sheet->getColumnDimension('H')->setWidth(50); // SEO Image
        $sheet->getColumnDimension('I')->setWidth(30); // Tags
        $sheet->getColumnDimension('J')->setWidth(20); // Danh mục
        $sheet->getColumnDimension('K')->setWidth(15); // Trạng thái
        $sheet->getColumnDimension('L')->setWidth(20); // Ngày xuất bản
        $sheet->getColumnDimension('M')->setWidth(12); // Lượt xem
        $sheet->getColumnDimension('N')->setWidth(15); // Type

        // Write data
        $row = 2;
        foreach ($this->posts as $post) {
            $col = 1;
            
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->id);
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->name ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->slug ?? '');
            
            // Content - set as text to preserve HTML
            $contentCell = $sheet->getCellByColumnAndRow($col++, $row);
            $contentCell->setValueExplicit($post->content ?? '', DataType::TYPE_STRING);
            
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->seo_title ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->seo_desc ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->seo_keywords ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->seo_image ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->tags ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->category ? $post->category->slug : '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->status ?? 'draft');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->published_at ? $post->published_at->format('Y-m-d H:i:s') : '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->views ?? 0);
            $sheet->setCellValueByColumnAndRow($col++, $row, $post->type ?? '');

            // Set row height for content
            $sheet->getRowDimension($row)->setRowHeight(-1);
            
            // Wrap text for content column
            $sheet->getStyleByColumnAndRow(4, $row)->getAlignment()->setWrapText(true);
            
            $row++;
        }

        // Freeze first row
        $sheet->freezePane('A2');

        // Set default row style
        $defaultStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ];

        $sheet->getStyle('A2:N' . ($row - 1))->applyFromArray($defaultStyle);

        return $spreadsheet;
    }

    public function download($filename = 'bai-viet.xlsx')
    {
        $spreadsheet = $this->export();
        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}

