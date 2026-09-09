<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

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

                Rule::unique(
                    'users',
                    'email'
                )->ignore($user->id),
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9+\s]+$/',

                Rule::unique(
                    'users',
                    'phone'
                )->ignore($user->id),
            ],

            'avatar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_avatar' => [
                'nullable',
                'boolean',
            ],
        ], [
            'name.required' =>
            'Vui lòng nhập họ tên.',

            'email.required' =>
            'Vui lòng nhập email.',

            'email.email' =>
            'Email không hợp lệ.',

            'email.unique' =>
            'Email này đã được tài khoản khác sử dụng.',

            'phone.required' =>
            'Vui lòng nhập số điện thoại.',

            'phone.regex' =>
            'Số điện thoại không hợp lệ.',

            'phone.unique' =>
            'Số điện thoại này đã được tài khoản khác sử dụng.',

            'avatar.image' =>
            'Ảnh đại diện không hợp lệ.',

            'avatar.mimes' =>
            'Ảnh phải là JPG, JPEG, PNG hoặc WEBP.',

            'avatar.max' =>
            'Ảnh đại diện không được lớn hơn 5MB.',
        ]);

        $user->name =
            $validated['name'];

        $user->email =
            $validated['email'];

        $user->phone =
            $validated['phone'];

        // =================================================
        // XÓA AVATAR
        // =================================================

        if (
            $request->boolean('remove_avatar') &&
            !$request->hasFile('avatar')
        ) {
            $this->deleteOldAvatar(
                $user->avatar
            );

            $user->avatar = null;
        }

        // =================================================
        // UPLOAD AVATAR MỚI
        // =================================================

        if ($request->hasFile('avatar')) {
            $this->deleteOldAvatar(
                $user->avatar
            );

            $path = $request
                ->file('avatar')
                ->store(
                    'avatars',
                    'public'
                );

            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'success' => true,

            'message' =>
            'Cập nhật thông tin thành công.',

            'data' =>
            $user->fresh(),
        ]);
    }

    private function deleteOldAvatar(
        ?string $avatar
    ): void {
        if (!$avatar) {
            return;
        }

        if (
            str_starts_with(
                $avatar,
                'http://'
            ) ||
            str_starts_with(
                $avatar,
                'https://'
            )
        ) {
            return;
        }

        Storage::disk('public')
            ->delete(
                $avatar
            );
    }
}
