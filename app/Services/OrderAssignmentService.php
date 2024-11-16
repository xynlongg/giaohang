<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PostOffice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class OrderAssignmentService
{
    private $apiUrl = 'https://provinces.open-api.vn/api/';

  
    public function assignOrderToPostOffice(Order $order)
    {
        Log::info('Bắt đầu gán đơn hàng cho bưu cục', [
            'order_id' => $order->id,
            'sender_coordinates' => $order->sender_coordinates
        ]);

        $senderCoordinates = $order->sender_coordinates;
        if (!is_array($senderCoordinates) || count($senderCoordinates) !== 2) {
            Log::error('Tọa độ người gửi không hợp lệ', ['sender_coordinates' => $senderCoordinates]);
            return false;
        }

        $senderInfo = $this->getLocationInfo($senderCoordinates[1], $senderCoordinates[0]);
        if (!$senderInfo) {
            Log::error('Không thể lấy thông tin địa chỉ từ tọa độ', ['coordinates' => $senderCoordinates]);
            return false;
        }

        $nearestPostOffice = $this->findNearestPostOffice($senderCoordinates[1], $senderCoordinates[0], $senderInfo['district']);

        if (!$nearestPostOffice) {
            Log::error('Không tìm thấy bưu cục phù hợp', ['sender_coordinates' => $senderCoordinates]);
            return false;
        }

        $order->postOffices()->attach($nearestPostOffice->id);
        $order->update([
            'current_location_id' => $nearestPostOffice->id,
            'current_location_type' => 'post_office',
            'current_coordinates' => [$nearestPostOffice->longitude, $nearestPostOffice->latitude],
            'current_location' => $nearestPostOffice->address,
        ]);

        Log::info('Đã gán đơn hàng cho bưu cục', [
            'order_id' => $order->id,
            'post_office_id' => $nearestPostOffice->id
        ]);

        return true;
    }
    private function getLocationInfo($lat, $lon)
    {
        $response = Http::get("https://api.mapbox.com/geocoding/v5/mapbox.places/{$lon},{$lat}.json", [
            'access_token' => env('MAPBOX_ACCESS_TOKEN'),
            'types' => 'place,district,locality',
            'language' => 'vi'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $features = $data['features'];

            $city = null;
            $district = null;

            foreach ($features as $feature) {
                if ($feature['place_type'][0] === 'place') {
                    $city = $feature['text'];
                } elseif ($feature['place_type'][0] === 'district' || $feature['place_type'][0] === 'locality') {
                    $district = $feature['text'];
                }

                if ($city && $district) break;
            }

            return [
                'city' => $city,
                'district' => $district,
            ];
        }

        return null;
    }
    private function findNearestPostOffice($lat, $lon, $senderDistrict)
    {
        $postOffices = PostOffice::all();
        $nearestPostOffice = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($postOffices as $postOffice) {
            $distance = $this->calculateDistance($lat, $lon, $postOffice->latitude, $postOffice->longitude);
            
            // Ưu tiên bưu cục cùng quận/huyện
            if ($postOffice->district == $senderDistrict) {
                return $postOffice;
            }

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestPostOffice = $postOffice;
            }
        }

        return $nearestPostOffice;
    }
    private function getAddressInfo($address)
    {
        Log::info("Bắt đầu trích xuất thông tin địa chỉ", ['address' => $address]);
    
        $addressParts = array_map('trim', explode(',', $address));
        Log::info("Các phần của địa chỉ", ['parts' => $addressParts]);
    
        $province = $this->findProvinceFromAPI($addressParts);
        
        if (!$province) {
            Log::error("Không thể tìm thấy tỉnh/thành phố trong địa chỉ: " . $address);
            return null;
        }
    
        Log::info("Đã tìm thấy tỉnh/thành phố", ['province' => $province]);
    
        $district = $this->findDistrictFromAPI($addressParts, $province['code']);
    
        if (!$district) {
            Log::warning("Không thể tìm thấy quận/huyện trong địa chỉ: " . $address);
            // Sử dụng phần đầu tiên của địa chỉ làm quận/huyện nếu không tìm thấy
            $district = ['name' => $addressParts[0]];
        }
    
        Log::info("Thông tin địa chỉ đã trích xuất", ['province' => $province['name'], 'district' => $district['name']]);
    
        return [
            'province' => $province['name'],
            'district' => $district['name'],
        ];
    }

    private function findProvinceFromAPI($addressParts)
    {
        $provinces = $this->getProvinces();
        $fullAddress = implode(' ', $addressParts);

        foreach ($provinces as $province) {
            if (Str::contains(Str::lower($fullAddress), Str::lower($province['name']))) {
                return $province;
            }
        }

        return null;
    }

    private function findDistrictFromAPI($addressParts, $provinceCode)
    {
        $districts = $this->getDistricts($provinceCode);
        $fullAddress = implode(' ', $addressParts);
    
        foreach ($districts as $district) {
            // So sánh tên quận/huyện
            if (Str::contains(Str::lower($fullAddress), Str::lower($district['name']))) {
                return $district;
            }
            
            // So sánh tên không dấu
            $districtNameWithoutAccents = $this->removeAccents($district['name']);
            if (Str::contains(Str::lower($this->removeAccents($fullAddress)), Str::lower($districtNameWithoutAccents))) {
                return $district;
            }
        }
    
        // Nếu không tìm thấy, thử tìm kiếm theo từng phần của địa chỉ
        foreach ($addressParts as $part) {
            foreach ($districts as $district) {
                if (Str::contains(Str::lower($part), Str::lower($district['name']))) {
                    return $district;
                }
                
                $districtNameWithoutAccents = $this->removeAccents($district['name']);
                if (Str::contains(Str::lower($this->removeAccents($part)), Str::lower($districtNameWithoutAccents))) {
                    return $district;
                }
            }
        }
    
        // Nếu vẫn không tìm thấy, log danh sách quận/huyện để kiểm tra
        Log::warning("Không tìm thấy quận/huyện. Danh sách quận/huyện:", ['districts' => $districts]);
    
        return null;
    }

    private function removeAccents($str) {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    private function getProvinces()
    {
        return Cache::remember('provinces', 86400, function () {
            $response = Http::get($this->apiUrl . 'p/');
            return $response->json();
        });
    }

    private function getDistricts($provinceCode)
    {
        return Cache::remember("districts_{$provinceCode}", 86400, function () use ($provinceCode) {
            $response = Http::get($this->apiUrl . "p/{$provinceCode}?depth=2");
            $data = $response->json();
            return $data['districts'] ?? [];
        });
    }

    private function findMatchingPostOffice($district, $province)
    {
        Log::info("Bắt đầu tìm bưu cục phù hợp", ['district' => $district, 'province' => $province]);

        $postOffice = PostOffice::where(function ($query) use ($district, $province) {
            $query->where('district', 'like', '%' . $this->removePrefix($district) . '%')
                  ->where('province', 'like', '%' . $this->removePrefix($province) . '%');
        })->orWhere(function ($query) use ($district, $province) {
            $query->where('address', 'like', '%' . $this->removePrefix($district) . '%')
                  ->where('address', 'like', '%' . $this->removePrefix($province) . '%');
        })->first();

        if ($postOffice) {
            Log::info("Đã tìm thấy bưu cục phù hợp", ['post_office_id' => $postOffice->id, 'post_office_name' => $postOffice->name]);
        } else {
            Log::error("Không tìm thấy bưu cục phù hợp", ['district' => $district, 'province' => $province]);
            // Tìm bưu cục gần nhất trong cùng tỉnh/thành phố
            $nearestPostOffice = PostOffice::where('province', 'like', '%' . $this->removePrefix($province) . '%')
                ->first();
            if ($nearestPostOffice) {
                Log::info("Đã tìm thấy bưu cục gần nhất trong cùng tỉnh/thành phố", ['post_office_id' => $nearestPostOffice->id, 'post_office_name' => $nearestPostOffice->name]);
                return $nearestPostOffice;
            }
        }

        return $postOffice;
    }

    private function removePrefix($name)
    {
        $prefixes = ['Quận', 'Huyện', 'Thị xã', 'Thành phố'];
        foreach ($prefixes as $prefix) {
            if (Str::startsWith($name, $prefix)) {
                return trim(Str::replaceFirst($prefix, '', $name));
            }
        }
        return $name;
    }
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
}