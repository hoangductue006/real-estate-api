<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    // =========================================
    // USER GỬI BÁO CÁO
    // =========================================

    public function store(
        Request $request,
        Property $property
    ) {
        // Không cho báo cáo chính tin của mình
        if (
            (int) $property->user_id ===
            (int) $request->user()->id
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                'Bạn không thể báo cáo tin do chính mình đăng.',
            ], 422);
        }

        // Tin đang bị Admin ẩn rồi
        if ($property->is_hidden) {
            return response()->json([
                'success' => false,

                'message' =>
                'Tin đăng này hiện đang bị ẩn.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => [
                'required',

                Rule::in([
                    'scam',
                    'incorrect_info',
                    'duplicate',
                    'unreasonable_price',
                    'inappropriate',
                    'other',
                ]),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:reason,other',
            ],
        ], [
            'reason.required' =>
            'Vui lòng chọn lý do báo cáo.',

            'reason.in' =>
            'Lý do báo cáo không hợp lệ.',

            'description.required_if' =>
            'Vui lòng mô tả lý do báo cáo.',

            'description.max' =>
            'Mô tả không được vượt quá 1000 ký tự.',
        ]);

        // Không cho spam cùng một tin khi report cũ
        // vẫn đang chờ Admin xử lý
        $existingReport = Report::where(
            'user_id',
            $request->user()->id
        )
            ->where(
                'property_id',
                $property->id
            )
            ->where(
                'status',
                'pending'
            )
            ->first();

        if ($existingReport) {
            return response()->json([
                'success' => false,

                'message' =>
                'Bạn đã báo cáo tin này và báo cáo đang chờ xử lý.',
            ], 422);
        }

        $report = Report::create([
            'user_id' =>
            $request->user()->id,

            'property_id' =>
            $property->id,

            'reason' =>
            $validated['reason'],

            'description' =>
            $validated['description'] ?? null,

            'status' =>
            'pending',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
            'Đã gửi báo cáo. Cảm ơn bạn đã phản hồi.',

            'data' =>
            $report,
        ], 201);
    }
}
