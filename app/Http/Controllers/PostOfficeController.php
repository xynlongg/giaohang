<?php

namespace App\Http\Controllers;

use App\Models\PostOffice;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'coordinates' => 'required',
        ]);

        $validatedData['coordinates'] = $this->convertCoordinates($validatedData['coordinates']);

        PostOffice::create($validatedData);

        return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được tạo thành công.');
    }

    public function show(PostOffice $postOffice)
    {
        \Log::info('Post Office Coordinates: ' . $postOffice->coordinates);
        return view('post_offices.show', compact('postOffice'));
    }

    public function edit(PostOffice $postOffice)
    {
        return view('post_offices.edit', compact('postOffice'));
    }

    public function update(Request $request, PostOffice $postOffice)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'coordinates' => 'required',
        ]);

        $validatedData['coordinates'] = $this->convertCoordinates($validatedData['coordinates']);

        $postOffice->update($validatedData);

        return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được cập nhật thành công.');
    }

    public function destroy(PostOffice $postOffice)
    {
        $postOffice->delete();

        return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được xóa thành công.');
    }

    private function convertCoordinates($coordinates)
    {
        if (is_string($coordinates)) {
            // Nếu đã là chuỗi JSON dạng mảng, trả về nguyên dạng
            if (preg_match('/^\[[-\d.,]+\]$/', $coordinates)) {
                return $coordinates;
            }
            $coords = json_decode($coordinates, true);
        } elseif (is_array($coordinates)) {
            $coords = $coordinates;
        } else {
            throw new \InvalidArgumentException("Invalid coordinates format");
        }

        if (isset($coords['lng']) && isset($coords['lat'])) {
            return json_encode([$coords['lng'], $coords['lat']]);
        } elseif (isset($coords[0]) && isset($coords[1])) {
            return json_encode($coords);
        } else {
            throw new \InvalidArgumentException("Invalid coordinates format");
        }
    }
}