<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PostOffice;
use App\Models\ProductCategory;
use App\Models\WarrantyPackage;


class OrdersImport implements ToModel, WithHeadingRow
{
    protected $commonData;
    protected $columnMap = [
        'receiver_name' => ['ten nguoi nhan', 'ten_nguoi_nhan'],
        'receiver_phone' => ['so dien thoai nguoi nhan', 'so_dien_thoai_nguoi_nhan'],
        'receiver_address' => ['dia chi nguoi nhan', 'dia_chi_nguoi_nhan'],
        'products' => ['san pham', 'san_pham', 'san_pham_tenso_luonggia_codcan_nang'],
        'total_weight' => ['tong khoi luong', 'tong_khoi_luong_kg'],
        'total_cod' => ['tong tien thu ho', 'tong_tien_thu_ho_vnd'],
        'total_value' => ['tong gia tri hang hoa', 'tong_gia_tri_hang_hoa_vnd'],
        'category_name' => ['danh muc', 'danh_muc'],
        'warranty_package_name' => ['goi bao hanh', 'goi_bao_hanh'],
    ];


    public function __construct($commonData)
    {
        $this->commonData = $commonData;
    }

    public function model(array $row)
    {
        Log::info('Bắt đầu xử lý hàng mới', ['row' => $row]);
        
        $mappedRow = $this->mapRow($row);
        Log::info('Dữ liệu hàng đã ánh xạ', ['mappedRow' => $mappedRow]);
    
        try {
            $this->validateRequiredFields($mappedRow);
            
            $category = ProductCategory::firstOrCreate(['name' => $mappedRow['category_name']]);
            $warrantyPackage = WarrantyPackage::firstOrCreate(
                ['name' => $mappedRow['warranty_package_name']],
                [
                    'description' => 'Mô tả mặc định cho ' . $mappedRow['warranty_package_name'],
                    'price' => 0 
                ]
            );            
            $products = $this->parseProducts($mappedRow['products']);
            
            $receiverCoordinates = $this->getCoordinates($mappedRow['receiver_address']);
            if (!$receiverCoordinates) {
                throw new \Exception("Không thể xác định tọa độ cho địa chỉ người nhận: " . $mappedRow['receiver_address']);
            }
    
            $distance = $this->calculateDistance(
                $this->commonData['sender_coordinates'][1],
                $this->commonData['sender_coordinates'][0],
                $receiverCoordinates[1],
                $receiverCoordinates[0]
            );
    
            $shippingFee = $this->calculateShippingFee($distance);
            $estimatedDeliveryDate = $this->calculateEstimatedDeliveryDate($distance, Carbon::parse($this->commonData['pickup_date']));
    
            $total_amount = $mappedRow['total_cod'] + $shippingFee + $warrantyPackage->price;
    
            $order = new Order([
                'receiver_name' => $mappedRow['receiver_name'],
                'receiver_phone' => $mappedRow['receiver_phone'],
                'receiver_address' => $mappedRow['receiver_address'],
                'receiver_coordinates' => $receiverCoordinates,
                'total_weight' => $mappedRow['total_weight'],
                'total_cod' => $mappedRow['total_cod'],
                'total_value' => $mappedRow['total_value'],
                'category_id' => $category->id,
                'warranty_package_id' => $warrantyPackage->id,
                'sender_name' => $this->commonData['sender_name'],
                'sender_phone' => $this->commonData['sender_phone'],
                'sender_address' => $this->commonData['sender_address'],
                'sender_coordinates' => $this->commonData['sender_coordinates'],
                'is_pickup_at_post_office' => $this->commonData['is_pickup_at_post_office'],
                'pickup_location_id' => $this->commonData['pickup_location_id'] ?? null,
                'pickup_date' => $this->commonData['pickup_date'],
                'pickup_time' => $this->commonData['pickup_time'],
                'shipping_fee' => $shippingFee,
                'delivery_date' => $estimatedDeliveryDate,
                'status' => 'pending',
                'tracking_number' => $this->generateTrackingNumber($mappedRow['receiver_address']),
                'total_amount' => $total_amount,
                'current_coordinates' => $this->commonData['sender_coordinates'],
                'current_location' => $this->commonData['sender_address'],
                'current_location_type' => 'sender',
            ]);
            
            $order->save();
            
            foreach ($products as $productData) {
                $product = $this->findOrCreateProduct($productData);
                $order->products()->attach($product->id, [
                    'quantity' => $productData['quantity'],
                    'cod_amount' => $productData['cod_amount'],
                    'weight' => $productData['weight'],
                ]);
            }
            
            return $order;
        } catch (\Exception $e) {
            Log::error('Lỗi khi xử lý hàng: ' . $e->getMessage(), ['exception' => $e, 'row' => $row]);
            throw $e;
        }
    }
    
    private function calculateEstimatedDeliveryDate($distance, $pickupDate)
    {
        $deliveryDate = clone $pickupDate;
        
        if ($distance <= 50) {
            $deliveryDate->addDay();
        } elseif ($distance <= 300) {
            $deliveryDate->addDays(2);
        } else {
            $deliveryDate->addDays(3);
        }
        
        while ($deliveryDate->isWeekend()) {
            $deliveryDate->addDay();
        }
        
        return $deliveryDate;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // Bán kính trái đất tính bằng km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = 
            sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
            sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $R * $c; // Khoảng cách tính bằng km
        return $distance;
    }

    private function calculateShippingFee($distance)
    {
        if ($distance <= 5) {
            return 10000; // 10k VND cho dưới 5km
        } elseif ($distance <= 10) {
            return 15000; // 15k VND cho 5-10km
        } elseif ($distance <= 30) {
            return ceil($distance) * 1500; // 1500 VND * số km cho 10-30km
        } elseif ($distance <= 60) {
            return ceil($distance) * 1300; // 1300 VND * số km cho 31-60km
        } elseif ($distance <= 100) {
            return ceil($distance) * 1000; // 1000 VND * số km cho 61-100km
        } elseif ($distance <= 150) {
            return ceil($distance) * 700; // 700 VND * số km cho 100-150km
        } elseif ($distance <= 300) {
            return ceil($distance) * 600; // 600 VND * số km cho 150-300km
        } else {
            return ceil($distance) * 450; // 450 VND * số km cho trên 300km
        }
    }

   

    private function mapRow($row)
    {
        $mappedRow = [];
        foreach ($this->columnMap as $key => $vietnameseNames) {
            foreach ($vietnameseNames as $vietnameseName) {
                $normalizedName = $this->normalizeColumnName($vietnameseName);
                if (isset($row[$normalizedName])) {
                    $mappedRow[$key] = $row[$normalizedName];
                    break;
                }
            }
        }
        
        $missingKeys = array_diff(array_keys($this->columnMap), array_keys($mappedRow));
        if (!empty($missingKeys)) {
            Log::warning('Missing columns:', $missingKeys);
        }
        
        return $mappedRow;
    }

    private function normalizeColumnName($name)
    {
        return str_replace([' ', '-'], '_', strtolower(trim($name)));
    }

    private function validateRequiredFields($mappedRow)
    {
        $requiredKeys = ['receiver_name', 'receiver_phone', 'receiver_address', 'products', 'category_name', 'warranty_package_name'];
        foreach ($requiredKeys as $key) {
            if (!isset($mappedRow[$key]) || empty($mappedRow[$key])) {
                Log::error("Missing or empty required column: {$key}", $mappedRow);
                throw new \Exception("Missing or empty required column: {$key}");
            }
        }
    }

    private function parseProducts($productString)
    {
        $products = explode(';', $productString);
        $parsedProducts = [];
        foreach ($products as $product) {
            $parts = explode(':', $product);
            if (count($parts) !== 4) {
                throw new \Exception("Invalid product format: " . $product);
            }
            list($name, $quantity, $codAmount, $weight) = $parts;
            $parsedProducts[] = [
                'name' => trim($name),
                'quantity' => (int)$quantity,
                'cod_amount' => (float)$codAmount,
                'weight' => (float)$weight,
            ];
        }
        return $parsedProducts;
    }

    private function getCoordinates($address)
    {
        try {
            $response = Http::get('https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($address) . '.json', [
                'access_token' => env('MAPBOX_ACCESS_TOKEN'),
                'limit' => 1,
            ]);

            $data = $response->json();

            if (isset($data['features'][0]['center'])) {
                return [
                    floatval($data['features'][0]['center'][0]),
                    floatval($data['features'][0]['center'][1])
                ];
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy tọa độ: ' . $e->getMessage());
        }

        return null;
    }


    private function generateTrackingNumber($receiverAddress)
    {
        $provinceCode = $this->getProvinceCode($receiverAddress);
        do {
            $randomPart = strtoupper(Str::random(6));
            $number = $provinceCode . '_' . $randomPart;
        } while (Order::where('tracking_number', $number)->exists());

        return $number;
    }

    private function getProvinceCode($address)
    {
        $provinces = [
            'An Giang' => 'AG', 'Bà Rịa - Vũng Tàu' => 'BV', 'Bắc Giang' => 'BG', 'Bắc Kạn' => 'BK', 'Bạc Liêu' => 'BL',
            'Bắc Ninh' => 'BN', 'Bến Tre' => 'BT', 'Bình Định' => 'BD', 'Bình Dương' => 'BI', 'Bình Phước' => 'BP',
            'Bình Thuận' => 'BH', 'Cà Mau' => 'CM', 'Cần Thơ' => 'CT', 'Cao Bằng' => 'CB', 'Đà Nẵng' => 'DN',
            'Đắk Lắk' => 'DL', 'Đắk Nông' => 'DO', 'Điện Biên' => 'DB', 'Đồng Nai' => 'DA', 'Đồng Tháp' => 'DT',
            'Gia Lai' => 'GL', 'Hà Giang' => 'HG', 'Hà Nam' => 'HM', 'Hà Nội' => 'HN', 'Hà Tĩnh' => 'HT',
            'Hải Dương' => 'HD', 'Hải Phòng' => 'HP', 'Hậu Giang' => 'HU', 'Hòa Bình' => 'HB', 'Hưng Yên' => 'HY',
            'Khánh Hòa' => 'KH', 'Kiên Giang' => 'KG', 'Kon Tum' => 'KT', 'Lai Châu' => 'LC', 'Lâm Đồng' => 'LD',
            'Lạng Sơn' => 'LS', 'Lào Cai' => 'LO', 'Long An' => 'LA', 'Nam Định' => 'ND', 'Nghệ An' => 'NA',
            'Ninh Bình' => 'NB', 'Ninh Thuận' => 'NT', 'Phú Thọ' => 'PT', 'Phú Yên' => 'PY', 'Quảng Bình' => 'QB',
            'Quảng Nam' => 'QN', 'Quảng Ngãi' => 'QG', 'Quảng Ninh' => 'QI', 'Quảng Trị' => 'QT', 'Sóc Trăng' => 'ST',
            'Sơn La' => 'SL', 'Tây Ninh' => 'TN', 'Thái Bình' => 'TB', 'Thái Nguyên' => 'TY', 'Thanh Hóa' => 'TH',
            'Thừa Thiên Huế' => 'TT', 'Tiền Giang' => 'TG', 'Thành Phố Hồ Chí Minh' => 'HCM', 'Trà Vinh' => 'TV', 'Tuyên Quang' => 'TQ',
            'Vĩnh Long' => 'VL', 'Vĩnh Phúc' => 'VP', 'Yên Bái' => 'YB'
        ];

        foreach ($provinces as $province => $code) {
            if (stripos($address, $province) !== false) {
                return $code;
            }
        }

        return 'XX'; // Default code if no province is found
    }

    private function findOrCreateProduct($productData)
    {
        $product = Product::firstOrCreate(
            ['name' => $productData['name']],
            [
                'value' => $productData['cod_amount'], // Giả sử giá trị sản phẩm là cod_amount
                'description' => 'Imported from Excel',
                'weight' => $productData['weight']
            ]
        );

        return $product;
    }
}