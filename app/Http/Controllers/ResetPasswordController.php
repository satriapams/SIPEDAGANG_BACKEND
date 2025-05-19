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
            'name' => 'required|string|exists:users,name',
        ]);

        $admin = User::where('name', $request->name)
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

    // 3. Admin mengganti password setelah disetujui
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|exists:users,name',
                'password' => 'required|min:6|confirmed',
            ]);

            $user = User::where('name', $request->name)
                        ->where('role', 'admin')
                        ->firstOrFail();

            $resetRequest = ResetRequest::where('admin_id', $user->id)
                                        ->where('status', 'approved')
                                        ->latest()
                                        ->first();

            if (!$resetRequest) {
                return response()->json(['message' => 'Permintaan belum disetujui superadmin.'], 403);
            }

            $user->password = Hash::make($request->password);
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
