<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // =====================================================
    // REGISTER
    // =====================================================

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9+\s]+$/',
                'unique:users,phone',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'name.required' =>
            'Vui lòng nhập họ tên.',

            'email.required' =>
            'Vui lòng nhập email.',

            'email.email' =>
            'Email không hợp lệ.',

            'email.unique' =>
            'Email này đã được sử dụng.',

            'phone.required' =>
            'Vui lòng nhập số điện thoại.',

            'phone.regex' =>
            'Số điện thoại không hợp lệ.',

            'phone.unique' =>
            'Số điện thoại này đã được sử dụng.',

            'password.required' =>
            'Vui lòng nhập mật khẩu.',

            'password.min' =>
            'Mật khẩu phải có ít nhất 8 ký tự.',

            'password.confirmed' =>
            'Xác nhận mật khẩu không khớp.',
        ]);

        $user = User::create([
            'name' =>
            $validated['name'],

            'email' =>
            $validated['email'],

            'phone' =>
            $validated['phone'],

            'password' =>
            $validated['password'],

            'role' =>
            'user',

            'is_active' =>
            true,
        ]);

        $token = $user
            ->createToken('flutter-app')
            ->plainTextToken;

        return response()->json([
            'success' => true,

            'message' =>
            'Đăng ký thành công.',

            'token' =>
            $token,

            'user' =>
            $user->fresh(),
        ], 201);
    }

    // =====================================================
    // LOGIN
    // =====================================================

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ], [
            'email.required' =>
            'Vui lòng nhập email.',

            'email.email' =>
            'Email không hợp lệ.',

            'password.required' =>
            'Vui lòng nhập mật khẩu.',
        ]);

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                'Email hoặc mật khẩu không chính xác.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,

                'message' =>
                'Tài khoản của bạn đã bị khóa.',
            ], 403);
        }

        // Một tài khoản chỉ giữ token mới nhất
        $user->tokens()->delete();

        $token = $user
            ->createToken('flutter-app')
            ->plainTextToken;

        return response()->json([
            'success' => true,

            'message' =>
            'Đăng nhập thành công.',

            'token' =>
            $token,

            'user' =>
            $user->fresh(),
        ]);
    }

    // =====================================================
    // CURRENT USER
    // =====================================================

    public function user(Request $request)
    {
        return response()->json([
            'success' => true,

            'data' =>
            $request->user()->fresh(),
        ]);
    }

    // =====================================================
    // LOGOUT
    // =====================================================

    public function logout(Request $request)
    {
        $request
            ->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,

            'message' =>
            'Đăng xuất thành công.',
        ]);
    }
}
