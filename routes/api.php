<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\PengadaanController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Login
Route::post('/login', function (Request $request) {
    $request->validate([
        'nama_pengguna' => 'required|string',
        'password' => 'required'
    ]);

    $user = User::where('nama_pengguna', $request->nama_pengguna)->first();

    if (!$user || !\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Login gagal: nama_pengguna atau password salah'], 401);
    }

    if ($user->status !== 'active') {
        return response()->json(['message' => 'Akun tidak aktif. Silakan hubungi superadmin.'], 403);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});

// Reset password (tanpa login)
Route::post('/reset/request', [ResetPasswordController::class, 'requestReset']);
Route::post('/reset/password', [ResetPasswordController::class, 'resetPassword']);


/*
|--------------------------------------------------------------------------
| Authenticated Routes (sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Logout
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    });

    // Ambil user login sekarang
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Profil User (Admin & Superadmin)
    Route::post('/user/update-profile', [UserProfileController::class, 'updateProfile']);
    Route::post('/user/update-status/{id}', [UserProfileController::class, 'updateStatus']);

    // Pengadaan
    Route::prefix('pengadaan')->group(function () {
        Route::post('/', [PengadaanController::class, 'store']);
        Route::get('/', [PengadaanController::class, 'index']);
        Route::get('/suplier/{nama}', [PengadaanController::class, 'getSuplierData']);
        Route::get('/{id}', [PengadaanController::class, 'show']);
        Route::put('/{id}', [PengadaanController::class, 'update']);
        Route::delete('/{id}', [PengadaanController::class, 'destroy']);
        Route::get('/{id}/download', [PengadaanController::class, 'download']);
    });

    // Admin management (akses superadmin & admin)
    Route::get('/admin/list/{id_admin}', [AdminManagementController::class, 'show']);
    Route::put('/admin/list/{id_admin}', [AdminManagementController::class, 'update']);

    // Reset password list (khusus superadmin)
    Route::get('/reset/list', [ResetPasswordController::class, 'listResetRequests']);

    // Approve reset password (superadmin)
    Route::post('/reset/approve/{id}', [ResetPasswordController::class, 'approveRequest']);
});


/*
|--------------------------------------------------------------------------
| Superadmin-Only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    // Tambah admin
    Route::post('/admin', [AdminManagementController::class, 'store']);

    // Lihat semua admin
    Route::get('/admin/list', [UserProfileController::class, 'listAdmins']);
});
