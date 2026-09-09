<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // Danh sách yêu thích
    public function index(Request $request)
    {
        $favorites = Favorite::where(
            'user_id',
            $request->user()->id
        )
            ->with([
                'property.category',
                'property.user',
            ])
            ->latest()
            ->get();

        $properties = $favorites
            ->pluck('property')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    // Thêm / bỏ yêu thích
    public function toggle(
        Request $request,
        Property $property
    ) {
        $favorite = Favorite::where(
            'user_id',
            $request->user()->id
        )
            ->where(
                'property_id',
                $property->id
            )
            ->first();

        // Nếu đã yêu thích → xóa
        if ($favorite) {
            $favorite->delete();

            return response()->json([
                'success' => true,
                'favorited' => false,
                'message' => 'Đã bỏ yêu thích',
            ]);
        }

        // Chưa yêu thích → thêm
        Favorite::create([
            'user_id' =>
            $request->user()->id,

            'property_id' =>
            $property->id,
        ]);

        return response()->json([
            'success' => true,
            'favorited' => true,
            'message' =>
            'Đã thêm vào yêu thích',
        ]);
    }
}
