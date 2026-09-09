<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // =========================================
    // CHECK ADMIN
    // =========================================

    private function isAdmin(
        Request $request
    ): bool {
        return $request->user()->role ===
            'admin';
    }

    private function forbidden()
    {
        return response()->json([
            'success' => false,

            'message' =>
            'Bạn không có quyền truy cập.',
        ], 403);
    }

    // =========================================
    // DASHBOARD
    // =========================================

    public function dashboard(
        Request $request
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        return response()->json([
            'success' => true,

            'data' => [
                'total_users' =>
                User::count(),

                'active_users' =>
                User::where(
                    'is_active',
                    true
                )->count(),

                'locked_users' =>
                User::where(
                    'is_active',
                    false
                )->count(),

                'total_properties' =>
                Property::count(),

                'available_properties' =>
                Property::where(
                    'status',
                    'available'
                )
                    ->where(
                        'is_hidden',
                        false
                    )
                    ->count(),

                'sold_properties' =>
                Property::where(
                    'status',
                    'sold'
                )->count(),

                'rented_properties' =>
                Property::where(
                    'status',
                    'rented'
                )->count(),

                'hidden_properties' =>
                Property::where(
                    'is_hidden',
                    true
                )->count(),

                'total_reports' =>
                Report::count(),

                'pending_reports' =>
                Report::where(
                    'status',
                    'pending'
                )->count(),
            ],
        ]);
    }

    // =========================================
    // USERS
    // =========================================

    public function users(
        Request $request
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $users = User::withCount(
            'properties'
        )
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    // =========================================
    // KHÓA / MỞ KHÓA USER
    // =========================================

    public function toggleUserStatus(
        Request $request,
        User $user
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        if (
            (int) $user->id ===
            (int) $request->user()->id
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                'Bạn không thể tự khóa tài khoản Admin.',
            ], 422);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,

                'message' =>
                'Không thể khóa tài khoản Admin.',
            ], 422);
        }

        $user->is_active =
            !$user->is_active;

        $user->save();

        // Khóa user -> hủy token
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,

            'message' =>
            $user->is_active
                ? 'Đã mở khóa tài khoản.'
                : 'Đã khóa tài khoản.',

            'data' => $user,
        ]);
    }

    // =========================================
    // DANH SÁCH PROPERTY
    // =========================================

    public function properties(
        Request $request
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $properties = Property::with([
            'category',
            'user',
            'images',
        ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    // =========================================
    // ẨN / HIỆN PROPERTY
    // =========================================

    public function togglePropertyVisibility(
        Request $request,
        Property $property
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $property->is_hidden =
            !$property->is_hidden;

        $property->save();

        $property->load([
            'category',
            'user',
            'images',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
            $property->is_hidden
                ? 'Đã ẩn tin đăng.'
                : 'Đã hiển thị lại tin đăng.',

            'data' =>
            $property,
        ]);
    }

    // =========================================
    // ADMIN XÓA PROPERTY
    // =========================================

    public function deleteProperty(
        Request $request,
        Property $property
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $this->deletePropertyFiles(
            $property
        );

        $property->delete();

        return response()->json([
            'success' => true,

            'message' =>
            'Admin đã xóa tin.',
        ]);
    }

    // =========================================
    // REPORTS
    // =========================================

    public function reports(
        Request $request
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $reports = Report::with([
            'user',

            'property.category',

            'property.user',

            'property.images',
        ])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    // =========================================
    // BỎ QUA REPORT
    // =========================================

    public function ignoreReport(
        Request $request,
        Report $report
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,

                'message' =>
                'Báo cáo này đã được xử lý.',
            ], 422);
        }

        $report->update([
            'status' => 'ignored',

            'handled_at' => now(),
        ]);

        return response()->json([
            'success' => true,

            'message' =>
            'Đã bỏ qua báo cáo.',

            'data' =>
            $report,
        ]);
    }

    // =========================================
    // ẨN PROPERTY TỪ REPORT
    // =========================================

    public function hideReportedProperty(
        Request $request,
        Report $report
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $property = $report->property;

        if (!$property) {
            return response()->json([
                'success' => false,

                'message' =>
                'Tin đăng đã bị xóa.',
            ], 404);
        }

        $property->is_hidden =
            true;

        $property->save();

        $report->update([
            'status' =>
            'resolved',

            'handled_at' =>
            now(),
        ]);

        return response()->json([
            'success' => true,

            'message' =>
            'Đã ẩn tin và xử lý báo cáo.',
        ]);
    }

    // =========================================
    // XÓA PROPERTY TỪ REPORT
    // =========================================

    public function deleteReportedProperty(
        Request $request,
        Report $report
    ) {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $property =
            $report->property;

        if (!$property) {
            return response()->json([
                'success' => false,

                'message' =>
                'Tin đăng đã bị xóa trước đó.',
            ], 404);
        }

        // Đánh dấu report đã xử lý trước
        $report->update([
            'status' =>
            'resolved',

            'handled_at' =>
            now(),
        ]);

        $this->deletePropertyFiles(
            $property
        );

        // property_id trong reports sẽ tự thành NULL
        $property->delete();

        return response()->json([
            'success' => true,

            'message' =>
            'Đã xóa tin và xử lý báo cáo.',
        ]);
    }

    // =========================================
    // DELETE PROPERTY FILES
    // =========================================

    private function deletePropertyFiles(
        Property $property
    ): void {
        $property->load('images');

        $paths = [];

        // Ảnh bìa
        if ($property->image) {
            $paths[] =
                $property->image;
        }

        // Các ảnh con
        foreach (
            $property->images
            as $image
        ) {
            $paths[] =
                $image->image;
        }

        $paths = array_unique(
            $paths
        );

        foreach ($paths as $path) {
            if (
                str_starts_with(
                    $path,
                    'http://'
                ) ||
                str_starts_with(
                    $path,
                    'https://'
                )
            ) {
                continue;
            }

            Storage::disk(
                'public'
            )->delete(
                $path
            );
        }
    }
}
