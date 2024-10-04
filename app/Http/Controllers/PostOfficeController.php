<?php

namespace App\Http\Controllers;

use App\Models\PostOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostOfficeController extends Controller
{
    public function index()
    {
        $postOffices = PostOffice::all();
        return view('post_offices.index', compact('postOffices'));
    }

    public function create()
    {
        return view('post_offices.create');
    }

    public function show(PostOffice $postOffice)
    {
        return view('post_offices.show', compact('postOffice'));
    }

    public function edit(PostOffice $postOffice)
    {
        return view('post_offices.edit', compact('postOffice'));
    }

 

    public function destroy(PostOffice $postOffice)
    {
        $postOffice->delete();

        return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được xóa thành công.');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'coordinates' => 'required|string',
        ]);

        try {
            // Convert coordinates
            $coordinates = $this->convertCoordinates($validatedData['coordinates']);
            $decodedCoordinates = json_decode($coordinates, true);

            // Assign latitude and longitude values
            $validatedData['latitude'] = $decodedCoordinates[1];
            $validatedData['longitude'] = $decodedCoordinates[0];
            $validatedData['coordinates'] = $coordinates;

            // Create new post office
            PostOffice::create($validatedData);

            return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được tạo thành công.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['coordinates' => $e->getMessage()]);
        }
    }
    public function update(Request $request, PostOffice $postOffice)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'coordinates' => 'required|string',
        ]);

        try {
            // Xử lý chuỗi JSON coordinates
            $coordinatesData = json_decode($validatedData['coordinates'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Lỗi khi xử lý dữ liệu tọa độ: ' . json_last_error_msg());
            }

            // Đảm bảo rằng lng và lat là số thực
            $longitude = floatval($coordinatesData['lng']);
            $latitude = floatval($coordinatesData['lat']);

            $updateData = [
                'name' => $validatedData['name'],
                'address' => $validatedData['address'],
                'district' => $validatedData['district'],
                'province' => $validatedData['province'],
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            $postOffice->update($updateData);

            return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được cập nhật thành công.');
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật bưu cục', [
                'post_office_id' => $postOffice->id,
                'input_data' => $validatedData,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('post_offices.edit', $postOffice)->withInput()->withErrors(['error' => 'Có lỗi xảy ra khi cập nhật bưu cục: ' . $e->getMessage()]);
        }
    }
    public function getCoordinatesAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }
    private function convertCoordinates($coordinates)
    {
        // If coordinates is already a JSON string, decode and validate it
        $decoded = json_decode($coordinates, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded) && isset($decoded[0], $decoded[1]) &&
                is_numeric($decoded[0]) && is_numeric($decoded[1])) {
                return json_encode($decoded);
            }
        }

        // If coordinates is a string, try to parse it
        if (is_string($coordinates)) {
            // Try "longitude,latitude" format
            $parts = explode(',', $coordinates);
            if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                return json_encode([floatval(trim($parts[0])), floatval(trim($parts[1]))]);
            }

            // Try "latitude longitude" format
            $parts = preg_split('/\s+/', trim($coordinates));
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                return json_encode([floatval($parts[1]), floatval($parts[0])]);
            }
        }

        // If coordinates is an array, validate and encode it
        if (is_array($coordinates) && isset($coordinates[0], $coordinates[1]) &&
            is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
            return json_encode($coordinates);
        }

        // If we reach here, the input format is invalid
        throw new \InvalidArgumentException("Invalid coordinates format. Expected JSON array, 'longitude,latitude' string, or 'latitude longitude' string.");
    }

}