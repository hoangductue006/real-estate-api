<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    // =========================================
    // DANH SÁCH
    // =========================================

    public function index(Request $request)
    {
        $query = Property::with([
            'category',
            'user',
            'images',
        ])
            ->where('is_hidden', false);


        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where(
                'property_category_id',
                $request->category_id
            );
        }

        if (
            $request->filled('min_price') &&
            is_numeric($request->min_price)
        ) {
            $query->where(
                'price',
                '>=',
                $request->min_price
            );
        }

        if (
            $request->filled('max_price') &&
            is_numeric($request->max_price)
        ) {
            $query->where(
                'price',
                '<=',
                $request->max_price
            );
        }

        if (
            $request->filled('min_area') &&
            is_numeric($request->min_area)
        ) {
            $query->where(
                'area',
                '>=',
                $request->min_area
            );
        }

        if (
            $request->filled('max_area') &&
            is_numeric($request->max_area)
        ) {
            $query->where(
                'area',
                '<=',
                $request->max_area
            );
        }

        if (
            $request->filled('bedrooms') &&
            is_numeric($request->bedrooms)
        ) {
            $query->where(
                'bedrooms',
                '>=',
                $request->bedrooms
            );
        }

        if ($request->filled('district')) {
            $query->where(
                'district',
                'like',
                '%' . trim($request->district) . '%'
            );
        }

        if (
            $request->filled('status') &&
            in_array(
                $request->status,
                ['available', 'sold', 'rented'],
                true
            )
        ) {
            $query->where(
                'status',
                $request->status
            );
        }

        // =====================================
        // SORT
        // =====================================

        switch ($request->get('sort', 'newest')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;

            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;

            case 'area_asc':
                $query->orderBy('area', 'asc');
                break;

            case 'area_desc':
                $query->orderBy('area', 'desc');
                break;

            default:
                $query->latest();
                break;
        }

        // =====================================
        // PAGINATION
        // =====================================

        $perPage = (int) $request->get(
            'per_page',
            6
        );

        $perPage = max(
            1,
            min($perPage, 20)
        );

        $paginator = $query->paginate(
            $perPage
        );

        return response()->json([
            'success' => true,

            'data' => $paginator->items(),

            'pagination' => [
                'current_page' =>
                $paginator->currentPage(),

                'last_page' =>
                $paginator->lastPage(),

                'per_page' =>
                $paginator->perPage(),

                'total' =>
                $paginator->total(),

                'from' =>
                $paginator->firstItem(),

                'to' =>
                $paginator->lastItem(),
            ],
        ]);
    }

    // =========================================
    // CHI TIẾT
    // =========================================

    public function show(Property $property)
    {
        if ($property->is_hidden) {
            return response()->json([
                'success' => false,
                'message' => 'Tin đăng này hiện không khả dụng.',
            ], 404);
        }

        $property->load([
            'category',
            'user',
            'images',
        ]);

        return response()->json([
            'success' => true,
            'data' => $property,
        ]);
    }

    // =========================================
    // TIN CỦA TÔI
    // =========================================

    public function myProperties(Request $request)
    {
        $properties = Property::with([
            'category',
            'user',
            'images',
        ])
            ->where(
                'user_id',
                $request->user()->id
            )
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    // =========================================
    // ĐĂNG TIN
    // =========================================

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_category_id' => [
                'required',
                'exists:property_categories,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'area' => [
                'required',
                'numeric',
                'min:1',
            ],

            'address' => [
                'required',
                'string',
                'max:255',
            ],

            'district' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'required',
                'string',
                'max:255',
            ],

            'bedrooms' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'bathrooms' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $propertyData = [
            'user_id' =>
            $request->user()->id,

            'property_category_id' =>
            $validated['property_category_id'],

            'title' =>
            $validated['title'],

            'description' =>
            $validated['description'] ?? null,

            'price' =>
            $validated['price'],

            'area' =>
            $validated['area'],

            'address' =>
            $validated['address'],

            'district' =>
            $validated['district'] ?? null,

            'city' =>
            $validated['city'],

            'bedrooms' =>
            $validated['bedrooms'] ?? 0,

            'bathrooms' =>
            $validated['bathrooms'] ?? 0,

            'featured' => false,

            'status' => 'available',
        ];

        $property = Property::create(
            $propertyData
        );

        $files = $request->file(
            'images',
            []
        );

        foreach ($files as $index => $file) {
            $path = $file->store(
                'properties',
                'public'
            );

            $property->images()->create([
                'image' => $path,
                'sort_order' => $index,
            ]);

            if ($index === 0) {
                $property->image = $path;
            }
        }

        $property->save();

        $property->load([
            'category',
            'user',
            'images',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
            'Đăng tin thành công',
            'data' => $property,
        ], 201);
    }

    // =========================================
    // SỬA TIN + QUẢN LÝ ẢNH
    // =========================================

    public function update(
        Request $request,
        Property $property
    ) {
        if (
            (int) $property->user_id !==
            (int) $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                'Bạn không có quyền sửa tin này.',
            ], 403);
        }

        $validated = $request->validate([
            'property_category_id' => [
                'required',
                'exists:property_categories,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'area' => [
                'required',
                'numeric',
                'min:1',
            ],

            'address' => [
                'required',
                'string',
                'max:255',
            ],

            'district' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'required',
                'string',
                'max:255',
            ],

            'bedrooms' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'bathrooms' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'delete_image_ids' => [
                'nullable',
                'array',
            ],

            'delete_image_ids.*' => [
                'integer',
            ],

            'cover_existing_id' => [
                'nullable',
                'integer',
            ],

            'cover_new_index' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $property->load('images');

        // =====================================
        // ẢNH CẦN XÓA
        // =====================================

        $deleteIds = collect(
            $validated['delete_image_ids'] ?? []
        )
            ->map(fn($id) => (int) $id)
            ->unique();

        $imagesToDelete = $property
            ->images
            ->whereIn(
                'id',
                $deleteIds
            );

        $remainingImages = $property
            ->images
            ->whereNotIn(
                'id',
                $deleteIds
            );

        // =====================================
        // ẢNH MỚI
        // =====================================

        $newFiles = $request->file(
            'images',
            []
        );

        if (!is_array($newFiles)) {
            $newFiles = [
                $newFiles,
            ];
        }

        $finalCount =
            $remainingImages->count() +
            count($newFiles);

        if ($finalCount > 10) {
            return response()->json([
                'success' => false,
                'message' =>
                'Một tin chỉ được tối đa 10 ảnh.',
            ], 422);
        }

        if ($finalCount < 1) {
            return response()->json([
                'success' => false,
                'message' =>
                'Tin đăng phải có ít nhất 1 ảnh.',
            ], 422);
        }

        // =====================================
        // XÓA FILE ẢNH CŨ
        // =====================================

        foreach ($imagesToDelete as $image) {
            if (
                !str_starts_with(
                    $image->image,
                    'http://'
                ) &&
                !str_starts_with(
                    $image->image,
                    'https://'
                )
            ) {
                Storage::disk('public')
                    ->delete(
                        $image->image
                    );
            }

            $image->delete();
        }

        // =====================================
        // UPDATE THÔNG TIN
        // =====================================

        $property->update([
            'property_category_id' =>
            $validated['property_category_id'],

            'title' =>
            $validated['title'],

            'description' =>
            $validated['description'] ?? null,

            'price' =>
            $validated['price'],

            'area' =>
            $validated['area'],

            'address' =>
            $validated['address'],

            'district' =>
            $validated['district'] ?? null,

            'city' =>
            $validated['city'],

            'bedrooms' =>
            $validated['bedrooms'] ?? 0,

            'bathrooms' =>
            $validated['bathrooms'] ?? 0,
        ]);

        // =====================================
        // UPLOAD ẢNH MỚI
        // =====================================

        $uploadedImages = [];

        $maxSort = $property
            ->images()
            ->max('sort_order') ?? -1;

        foreach ($newFiles as $index => $file) {
            $path = $file->store(
                'properties',
                'public'
            );

            $image = $property
                ->images()
                ->create([
                    'image' => $path,

                    'sort_order' =>
                    $maxSort +
                        $index +
                        1,
                ]);

            $uploadedImages[] =
                $image;
        }

        // =====================================
        // ĐỔI ẢNH BÌA
        // =====================================

        $coverPath = null;

        if (
            isset($validated['cover_existing_id'])
        ) {
            $coverImage = $property
                ->images()
                ->where(
                    'id',
                    $validated['cover_existing_id']
                )
                ->first();

            if ($coverImage) {
                $coverPath =
                    $coverImage->image;
            }
        }

        if (
            $coverPath === null &&
            isset($validated['cover_new_index'])
        ) {
            $newIndex =
                (int) $validated['cover_new_index'];

            if (
                isset(
                    $uploadedImages[$newIndex]
                )
            ) {
                $coverPath =
                    $uploadedImages[$newIndex]->image;
            }
        }

        // Nếu không chọn bìa mới,
        // kiểm tra bìa cũ còn tồn tại không
        if ($coverPath === null) {
            $currentCoverExists =
                $property
                ->images()
                ->where(
                    'image',
                    $property->image
                )
                ->exists();

            if ($currentCoverExists) {
                $coverPath =
                    $property->image;
            }
        }

        // Nếu bìa cũ đã bị xóa
        if ($coverPath === null) {
            $firstImage = $property
                ->images()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            $coverPath =
                $firstImage?->image;
        }

        $property->image =
            $coverPath;

        $property->save();

        $property->load([
            'category',
            'user',
            'images',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
            'Cập nhật tin thành công',

            'data' => $property,
        ]);
    }

    // =========================================
    // STATUS
    // =========================================

    public function updateStatus(
        Request $request,
        Property $property
    ) {
        if (
            (int) $property->user_id !==
            (int) $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                'Bạn không có quyền cập nhật tin này.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                'in:available,sold,rented',
            ],
        ]);

        $property->update([
            'status' =>
            $validated['status'],
        ]);

        $property->load([
            'category',
            'user',
            'images',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
            'Cập nhật trạng thái thành công',

            'data' => $property,
        ]);
    }

    // =========================================
    // XÓA TIN
    // =========================================

    public function destroy(
        Request $request,
        Property $property
    ) {
        if (
            (int) $property->user_id !==
            (int) $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                'Bạn không có quyền xóa tin này.',
            ], 403);
        }

        $property->load('images');

        foreach ($property->images as $image) {
            if (
                !str_starts_with(
                    $image->image,
                    'http://'
                ) &&
                !str_starts_with(
                    $image->image,
                    'https://'
                )
            ) {
                Storage::disk('public')
                    ->delete(
                        $image->image
                    );
            }
        }

        $property->delete();

        return response()->json([
            'success' => true,
            'message' =>
            'Xóa tin thành công',
        ]);
    }
}
