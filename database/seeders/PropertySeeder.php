<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyCategory;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $house = PropertyCategory::create([
            'name' => 'Nhà',
            'slug' => 'nha',
        ]);

        $land = PropertyCategory::create([
            'name' => 'Đất',
            'slug' => 'dat',
        ]);

        $apartment = PropertyCategory::create([
            'name' => 'Chung cư',
            'slug' => 'chung-cu',
        ]);

        $rent = PropertyCategory::create([
            'name' => 'Cho thuê',
            'slug' => 'cho-thue',
        ]);

        Property::create([
            'property_category_id' => $house->id,
            'title' => 'Nhà 3 tầng tại Nam Từ Liêm',
            'description' => 'Nhà 3 tầng, vị trí đẹp, giao thông thuận tiện.',
            'price' => 4200000000,
            'area' => 65,
            'address' => 'Nam Từ Liêm',
            'district' => 'Nam Từ Liêm',
            'city' => 'Hà Nội',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'image' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6',
            'featured' => true,
            'status' => 'available',
        ]);

        Property::create([
            'property_category_id' => $apartment->id,
            'title' => 'Chung cư cao cấp 2 phòng ngủ',
            'description' => 'Căn hộ hiện đại, đầy đủ tiện ích.',
            'price' => 3100000000,
            'area' => 78,
            'address' => 'Cầu Giấy',
            'district' => 'Cầu Giấy',
            'city' => 'Hà Nội',
            'bedrooms' => 2,
            'bathrooms' => 2,
            'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c',
            'featured' => true,
            'status' => 'available',
        ]);

        Property::create([
            'property_category_id' => $land->id,
            'title' => 'Đất thổ cư gần trung tâm',
            'description' => 'Đất thổ cư, khu dân cư ổn định.',
            'price' => 2600000000,
            'area' => 90,
            'address' => 'Hoài Đức',
            'district' => 'Hoài Đức',
            'city' => 'Hà Nội',
            'bedrooms' => 0,
            'bathrooms' => 0,
            'image' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef',
            'featured' => false,
            'status' => 'available',
        ]);

        Property::create([
            'property_category_id' => $rent->id,
            'title' => 'Căn hộ cho thuê đầy đủ nội thất',
            'description' => 'Căn hộ có thể vào ở ngay.',
            'price' => 9000000,
            'area' => 55,
            'address' => 'Thanh Xuân',
            'district' => 'Thanh Xuân',
            'city' => 'Hà Nội',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267',
            'featured' => false,
            'status' => 'available',
        ]);
    }
}
