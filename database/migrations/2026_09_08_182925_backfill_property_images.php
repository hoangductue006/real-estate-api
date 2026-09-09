<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $properties = DB::table('properties')
            ->whereNotNull('image')
            ->get();

        foreach ($properties as $property) {
            $exists = DB::table('property_images')
                ->where('property_id', $property->id)
                ->where('image', $property->image)
                ->exists();

            if (!$exists) {
                DB::table('property_images')->insert([
                    'property_id' => $property->id,
                    'image' => $property->image,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Không xóa để tránh mất dữ liệu ảnh.
    }
};
