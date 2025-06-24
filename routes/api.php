<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\PengadaanController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\DataPemohonController;
use App\Models\User;
use App\Http\Controllers\PengaturanPengadaanController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

// ðŸ”“ Login
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

// ðŸ”“ Reset Password (tanpa login)
Route::post('/reset/request', [ResetPasswordController::class, 'requestReset']);
Route::post('/reset/password', [ResetPasswordController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth Required via Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // ðŸ”’ Logout
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    });

    // ðŸ”’ Get Current User
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ðŸ”’ User Profile
    Route::post('/user/update-profile', [UserProfileController::class, 'updateProfile']);
    Route::post('/user/update-status/{id}', [UserProfileController::class, 'updateStatus']);

    // ðŸ”’ Admin Management (edit data admin)
    Route::get('/admin/list/{id_admin}', [AdminManagementController::class, 'show']);
    Route::put('/admin/list/{id_admin}', [AdminManagementController::class, 'update']);

    // ðŸ”’ Pengadaan
    Route::prefix('pengadaan')->group(function () {
        Route::post('/', [PengadaanController::class, 'store']);
        Route::get('/', [PengadaanController::class, 'index']);
        Route::get('/{id}', [PengadaanController::class, 'show']);
        Route::put('/{id}', [PengadaanController::class, 'update']);
        Route::delete('/{id}', [PengadaanController::class, 'destroy']);
        Route::get('/{id}/download', [PengadaanController::class, 'download']);
    });

    // ðŸ”’ Data Pemohon
    Route::prefix('pemohon')->group(function () {
        // âœ… GET All with Pagination + Search
        Route::get('/', [DataPemohonController::class, 'index']); // Now supports pagination & search

        Route::get('/{id}', [DataPemohonController::class, 'show']);

        // âœ… Manual Add
        Route::post('/', [DataPemohonController::class, 'store']);

        // âœ… Update dengan POST method override
        Route::put('/{id}', [DataPemohonController::class, 'update']); // Gunakan _method=PUT

        // âœ… Delete dengan POST method override
        Route::delete('/{id}', [DataPemohonController::class, 'destroy']); // Gunakan _method=DELETE
        
        

    });

    Route::post('/data-pemohon/import', [DataPemohonController::class, 'uploadExcel']);
        
    Route::get('/data-pemohon/by-perusahaan', [DataPemohonController::class, 'getByNamaPerusahaan']);
        
    Route::get('/data-pemohon/detail/{nama_perusahaan}', [DataPemohonController::class, 'getByPerusahaan']);


    Route::get('/pengadaanclear', [PengadaanController::class, 'clear']);

    Route::get('/pengaturan-pengadaan', [PengaturanPengadaanController::class, 'index']);

    Route::get('/pengaturan-pengadaan/{id}', [PengaturanPengadaanController::class, 'show']);


    // ðŸ”’ Reset Password Approval



});

/*
|--------------------------------------------------------------------------
| Superadmin-Only Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    Route::post('/admin', [AdminManagementController::class, 'store']);   // Tambah Admin
    Route::get('/admin/list', [UserProfileController::class, 'listAdmins']); // Lihat Semua Admin

    Route::get('/reset/list', [ResetPasswordController::class, 'listResetRequests']);
    Route::post('/reset/approve/{id}', [ResetPasswordController::class, 'approveRequest']);
    Route::post('/reset/decline/{id}', [ResetPasswordController::class, 'declineRequest']);


    Route::post('/pengaturan-pengadaan', [PengaturanPengadaanController::class, 'store']);
    Route::put('/pengaturan-pengadaan/{id}', [PengaturanPengadaanController::class, 'update']);
    Route::delete('/pengaturan-pengadaan/{id}', [PengaturanPengadaanController::class, 'destroy']);
});
