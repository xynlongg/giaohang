<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;

class WarehouseController extends Controller
{
    public function index(){
        $warehouses = Warehouse::all(); 
        return view('warehouses.index', compact('warehouses'));    
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'coordinates' => 'required|json',
        ]);

        $warehouse = new Warehouse();
        $warehouse->name = $validatedData['name'];
        $warehouse->address = $validatedData['address'];
        $warehouse->coordinates = json_decode($validatedData['coordinates'], true);
        $warehouse->save();

        return redirect()->route('warehouses.index')->with('success', 'Kho hàng đã được thêm mới thành công.');
    }

    public function show(Warehouse $warehouse)
    {
        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }
    
    public function update(Request $request, Warehouse $warehouse)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'coordinates' => 'required|json',
        ]);
    
        $warehouse->name = $validatedData['name'];
        $warehouse->address = $validatedData['address'];
        $warehouse->coordinates = json_decode($validatedData['coordinates'], true);
        $warehouse->save();
    
        return redirect()->route('warehouses.show', $warehouse)->with('success', 'Kho hàng đã được cập nhật thành công.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Kho hàng đã được xóa thành công.');
    }
}
