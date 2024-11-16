<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeographyService
{
    const API_BASE_URL = 'https://provinces.open-api.vn/api/';

    public function getProvinces()
    {
        return Cache::remember('provinces', 86400, function () {
            $response = Http::get(self::API_BASE_URL . '?depth=1');
            return $response->json();
        });
    }

    public function getDistricts($provinceCode)
    {
        return Cache::remember("districts_{$provinceCode}", 86400, function () use ($provinceCode) {
            $response = Http::get(self::API_BASE_URL . "p/{$provinceCode}?depth=2");
            $data = $response->json();
            return $data['districts'] ?? [];
        });
    }

    public function getProvinceCode($provinceName)
    {
        $provinces = $this->getProvinces();
        foreach ($provinces as $province) {
            if (strcasecmp($province['name'], $provinceName) === 0) {
                return $province['code'];
            }
        }
        return null;
    }

    public function isUrbanDistrict($provinceCode, $districtName)
    {
        $urbanProvinces = ['01', '79']; // Hà Nội và TP.HCM
        if (!in_array($provinceCode, $urbanProvinces)) {
            return false;
        }

        $districts = $this->getDistricts($provinceCode);
        foreach ($districts as $district) {
            if (strcasecmp($district['name'], $districtName) === 0) {
                // Giả sử quận nội thành có mã bắt đầu bằng số
                return is_numeric(substr($district['code'], 0, 1));
            }
        }
        return false;
    }
}