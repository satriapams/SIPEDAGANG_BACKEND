<?php

namespace App\Http\Controllers;

use App\Models\ResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    // 1. Admin meminta reset password
    public function requestReset(Request $request)
    {
        $request->validate([
            'nama_pengguna' => 'required|string|exists:users,nama_pengguna',
        ]);

        $admin = User::where('nama_pengguna', $request->nama_pengguna)
                    ->where('role', 'admin')
                    ->first();

        if (!$admin) {
            return response()->json(['message' => 'User bukan admin'], 403);
        }

        $existing = ResetRequest::where('admin_id', $admin->id)
                                ->where('status', 'pending')
                                ->first();

        if ($existing) {
            return response()->json(['message' => 'Permintaan reset sudah ada dan menunggu persetujuan.'], 409);
        }

        ResetRequest::create([
            'admin_id' => $admin->id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Permintaan reset dikirim, tunggu konfirmasi superadmin.']);
    }

    // 2. Superadmin menyetujui request
    public function approveRequest($id)
    {
        $request = ResetRequest::findOrFail($id);

        if (Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Akses ditolak. Hanya superadmin yang dapat menyetujui.'], 403);
        }

        if ($request->status !== 'pending') {
            return response()->json(['message' => 'Permintaan ini sudah diproses.'], 400);
        }

        $request->status = 'approved';
        $request->save();

        return response()->json(['message' => 'Permintaan reset disetujui']);
    }

    // 5. Superadmin menolak permintaan reset
    public function declineRequest($id)
    {
        $reset = ResetRequest::findOrFail($id);

        if (Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Akses ditolak. Hanya superadmin yang dapat menolak.'], 403);
        }

        if ($reset->status !== 'pending') {
            return response()->json(['message' => 'Permintaan ini sudah diproses.'], 400);
        }

        $reset->status = 'declined';
        $reset->save();

        return response()->json(['message' => 'Permintaan reset ditolak.']);
    }


    // 3. Admin mengganti password setelah disetujui
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'nama_pengguna' => 'required|string|exists:users,nama_pengguna',
                'password' => 'required|min:6|confirmed',
            ]);

            $user = User::where('nama_pengguna', $request->nama_pengguna)
                        ->where('role', 'admin')
                        ->firstOrFail();

            $resetRequest = ResetRequest::where('admin_id', $user->id)
                                        ->latest()
                                        ->first();

            if (!$resetRequest) {
                return response()->json(['message' => 'Tidak ada permintaan reset password.'], 403);
            }

            if ($resetRequest->status === 'pending') {
                return response()->json(['message' => 'Permintaan belum disetujui superadmin.'], 403);
            }

            if ($resetRequest->status === 'declined') {
                return response()->json(['message' => 'Permintaan ganti password ditolak oleh superadmin.'], 403);
            }

            if ($resetRequest->status === 'used') {
                return response()->json(['message' => 'Permintaan ini sudah digunakan.'], 403);
            }

            // Jika status = approved, lanjut ganti password
            $user->password = Hash::make($request->password);
            $user->plain_password = $request->password;
            $user->save();

            $resetRequest->status = 'used';
            $resetRequest->save();

            return response()->json(['message' => 'Password berhasil diganti']);
        } catch (\Exception $e) {
            Log::error('Reset Password Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengganti password',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // 4. Superadmin melihat semua permintaan reset
    public function listResetRequests()
    {
        if (Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $requests = ResetRequest::with('admin')->where('status', 'pending')->get();

        return response()->json($requests);
    }
}
