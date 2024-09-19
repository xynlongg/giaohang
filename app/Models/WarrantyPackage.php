<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyPackage extends Model
{
    protected $fillable = ['name', 'description', 'price'];

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'category_warranty', 'warranty_package_id', 'product_category_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
};