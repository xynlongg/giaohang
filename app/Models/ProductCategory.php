<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function warrantyPackages()
    {
        return $this->belongsToMany(WarrantyPackage::class, 'category_warranty', 'product_category_id', 'warranty_package_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}