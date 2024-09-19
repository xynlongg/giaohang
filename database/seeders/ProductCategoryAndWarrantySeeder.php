<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\WarrantyPackage;

class ProductCategoryAndWarrantySeeder extends Seeder
{
    public function run()
    {
        // Tạo danh mục sản phẩm
        $electronicCategory = ProductCategory::create(['name' => 'Điện tử', 'description' => 'Các sản phẩm điện tử']);
        $agriculturalCategory = ProductCategory::create(['name' => 'Nông sản', 'description' => 'Các sản phẩm nông nghiệp']);

        // Tạo gói bảo hành
        $basicWarranty = WarrantyPackage::create([
            'name' => 'Bảo hành cơ bản',
            'description' => 'Bảo hành sản phẩm trong vòng 6 tháng',
            'price' => 10000
        ]);

        $premiumWarranty = WarrantyPackage::create([
            'name' => 'Bảo hành cao cấp',
            'description' => 'Bảo hành sản phẩm trong vòng 12 tháng',
            'price' => 15000
        ]);

        $nonCrushWarranty = WarrantyPackage::create([
            'name' => 'Bảo hành không dập nát',
            'description' => 'Bảo đảm sản phẩm không bị dập nát trong quá trình vận chuyển',
            'price' => 10000
        ]);

        // Liên kết danh mục với gói bảo hành
        $electronicCategory->warrantyPackages()->attach([$basicWarranty->id, $premiumWarranty->id]);
        $agriculturalCategory->warrantyPackages()->attach([$nonCrushWarranty->id]);
    }
}