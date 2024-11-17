<?php

namespace App\Http\Controllers;

use App\Models\WarrantyPackage;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class WarrantyPackageController extends Controller
{
    public function index()
    {
        $warrantyPackages = WarrantyPackage::with('categories')->get();
        return view('warranty_packages.index', compact('warrantyPackages'));
    }

    public function create()
    {
        $categories = ProductCategory::all();
        return view('warranty_packages.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
            'price' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ]);

        $warrantyPackage = WarrantyPackage::create($validatedData);
        $warrantyPackage->categories()->attach($request->categories);

        return redirect()->route('warranty-packages.index')->with('success', 'Gói bảo hành đã được tạo thành công.');
    }

    public function edit(WarrantyPackage $warrantyPackage)
    {
        $categories = ProductCategory::all();
        return view('warranty_packages.edit', compact('warrantyPackage', 'categories'));
    }

    public function update(Request $request, WarrantyPackage $warrantyPackage)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
            'price' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ]);

        $warrantyPackage->update($validatedData);
        $warrantyPackage->categories()->sync($request->categories);

        return redirect()->route('warranty-packages.index')->with('success', 'Gói bảo hành đã được cập nhật thành công.');
    }

    public function destroy(WarrantyPackage $warrantyPackage)
    {
        $warrantyPackage->delete();

        return redirect()->route('warranty-packages.index')->with('success', 'Gói bảo hành đã được xóa thành công.');
    }
    protected $attributes = [
        'description' => 'Mô tả mặc định',
    ];
}