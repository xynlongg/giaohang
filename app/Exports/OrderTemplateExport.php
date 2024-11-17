<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        return [
            [
                'Nguyễn Văn A',
                '0123456789',
                '123 Đường ABC, Quận XYZ, Hà Nội',
                'Sản phẩm A:2:100000:0.5;Sản phẩm B:1:50000:0.3',
                1.3,
                150000,
                200000,
                'Điện tử',
                'Bảo hành 12 tháng',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Tên người nhận',
            'Số điện thoại người nhận',
            'Địa chỉ người nhận',
            'Sản phẩm (Tên:Số lượng:Giá COD:Cân nặng)',
            'Tổng khối lượng (kg)',
            'Tổng tiền thu hộ (VND)',
            'Tổng giá trị hàng hóa (VND)',
            'Danh mục',
            'Gói bảo hành',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A1:I1' => ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'CCCCCC']]],
        ];
    }
}