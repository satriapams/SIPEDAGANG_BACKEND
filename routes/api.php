<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminManagementController;
use App\Models\User;
use App\Http\Controllers\PengadaanController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\UserProfileController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/update-profile', [UserProfileController::class, 'updateProfile']);
    Route::post('/user/update-status/{id}', [UserProfileController::class, 'updateStatus']);
});



Route::middleware(['auth:sanctum', 'role:superadmin'])->get('/admin/list', [UserProfileController::class, 'listAdmins']);


Route::middleware(['auth:sanctum'])->post('/pengadaan', [PengadaanController::class, 'store']);

Route::middleware('auth:sanctum')->get('/pengadaan/suplier/{nama}', [PengadaanController::class, 'getSuplierData']);

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

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out']);
});

Route::middleware(['auth:sanctum', 'role:superadmin'])->post('/admin', [AdminManagementController::class, 'store']);
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->prefix('pengadaan')->group(function () {
    Route::post('/', [PengadaanController::class, 'store']);
    Route::get('/', [PengadaanController::class, 'index']);
    Route::get('/suplier/{nama}', [PengadaanController::class, 'getSuplierData']);
    Route::get('/{id}', [PengadaanController::class, 'show']);
    Route::put('/{id}', [PengadaanController::class, 'update']);
    Route::delete('/{id}', [PengadaanController::class, 'destroy']);
    Route::get('/{id}/download', [PengadaanController::class, 'download']);
});


Route::post('/reset/request', [ResetPasswordController::class, 'requestReset']); // admin minta reset
Route::middleware('auth:sanctum')->post('/reset/approve/{id}', [ResetPasswordController::class, 'approveRequest']); // superadmin approve
Route::post('/reset/password', [ResetPasswordController::class, 'resetPassword']); // admin ubah password
Route::middleware('auth:sanctum')->get('/reset/list', [ResetPasswordController::class, 'listResetRequests']); // superadmin lihat permintaan

