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

        $validatedData['coordinates'] = $this->convertCoordinates($validatedData['coordinates']);

        PostOffice::create($validatedData);

        return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được tạo thành công.');
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

        $validatedData['coordinates'] = $this->convertCoordinates($validatedData['coordinates']);

        $postOffice->update($validatedData);

        return redirect()->route('post_offices.index')->with('success', 'Bưu cục đã được cập nhật thành công.');
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
        // Nếu coordinates đã là một mảng, trả về nó dưới dạng chuỗi JSON
        if (is_array($coordinates)) {
            return json_encode($coordinates);
        }

        // Nếu coordinates là một chuỗi, thử parse nó
        $decoded = json_decode($coordinates, true);

        // Nếu parse thành công và kết quả là một mảng hợp lệ, trả về chuỗi JSON
        if (is_array($decoded) && count($decoded) === 2 &&
            is_numeric($decoded[0]) && is_numeric($decoded[1])) {
            return json_encode($decoded);
        }

        // Nếu không phải là JSON hợp lệ, giả sử đó là một chuỗi "longitude,latitude"
        $parts = explode(',', $coordinates);
        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            return json_encode([$parts[0], $parts[1]]);
        }

        // Nếu không phù hợp với bất kỳ format nào, ném ra exception
        throw new \InvalidArgumentException("Invalid coordinates format");
    }
}