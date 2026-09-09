<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;


// =====================================================
// PUBLIC AUTH
// =====================================================

Route::post('/register', [
    AuthController::class,
    'register',
]);

Route::post('/login', [
    AuthController::class,
    'login',
]);


// =====================================================
// PUBLIC PROPERTY
// =====================================================

Route::get('/properties', [
    PropertyController::class,
    'index',
]);

Route::get('/properties/{property}', [
    PropertyController::class,
    'show',
]);


// =====================================================
// AUTH SANCTUM
// =====================================================

Route::middleware('auth:sanctum')
    ->group(function () {

        // =============================================
        // USER
        // =============================================

        Route::get('/user', [
            AuthController::class,
            'user',
        ]);

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);


        // =============================================
        // PROFILE
        // =============================================

        Route::put('/profile', [
            ProfileController::class,
            'update',
        ]);


        // =============================================
        // USER PROPERTY
        // =============================================

        Route::post('/properties', [
            PropertyController::class,
            'store',
        ]);

        Route::get('/my-properties', [
            PropertyController::class,
            'myProperties',
        ]);

        Route::put('/properties/{property}', [
            PropertyController::class,
            'update',
        ]);

        Route::patch(
            '/properties/{property}/status',
            [
                PropertyController::class,
                'updateStatus',
            ]
        );

        Route::delete(
            '/properties/{property}',
            [
                PropertyController::class,
                'destroy',
            ]
        );


        // =============================================
        // FAVORITES
        // =============================================

        Route::get('/favorites', [
            FavoriteController::class,
            'index',
        ]);

        Route::post(
            '/properties/{property}/favorite',
            [
                FavoriteController::class,
                'toggle',
            ]
        );


        // =============================================
        // USER REPORT PROPERTY
        // =============================================

        Route::post(
            '/properties/{property}/reports',
            [
                ReportController::class,
                'store',
            ]
        );


        // =============================================
        // ADMIN DASHBOARD
        // =============================================

        Route::get(
            '/admin/dashboard',
            [
                AdminController::class,
                'dashboard',
            ]
        );


        // =============================================
        // ADMIN USERS
        // =============================================

        Route::get(
            '/admin/users',
            [
                AdminController::class,
                'users',
            ]
        );

        Route::patch(
            '/admin/users/{user}/status',
            [
                AdminController::class,
                'toggleUserStatus',
            ]
        );


        // =============================================
        // ADMIN PROPERTIES
        // =============================================

        Route::get(
            '/admin/properties',
            [
                AdminController::class,
                'properties',
            ]
        );

        Route::patch(
            '/admin/properties/{property}/visibility',
            [
                AdminController::class,
                'togglePropertyVisibility',
            ]
        );

        Route::delete(
            '/admin/properties/{property}',
            [
                AdminController::class,
                'deleteProperty',
            ]
        );


        // =============================================
        // ADMIN REPORTS
        // =============================================

        Route::get(
            '/admin/reports',
            [
                AdminController::class,
                'reports',
            ]
        );

        Route::patch(
            '/admin/reports/{report}/ignore',
            [
                AdminController::class,
                'ignoreReport',
            ]
        );

        Route::patch(
            '/admin/reports/{report}/hide-property',
            [
                AdminController::class,
                'hideReportedProperty',
            ]
        );

        Route::delete(
            '/admin/reports/{report}/property',
            [
                AdminController::class,
                'deleteReportedProperty',
            ]
        );
    });
