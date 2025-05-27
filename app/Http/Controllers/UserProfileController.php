<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    // Admin atau Superadmin mengupdate profil mereka sendiri
    public function updateProfile(Request $request)
    {
        $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'phone_number' => 'nullable|string|max:15',
        ]);

        $user = Auth::user();

        // Jika ada file yang diupload
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = 'profile_' . $user->id . '.' . $file->getClientOriginalExtension();

            // Simpan ke disk 'public' => storage/app/public/profile_photos
            $file->storeAs('profile_photos', $filename, 'public');

            // Simpan path ke DB (akses URL: /storage/profile_photos/xxx.jpg)
            $user->profile_photo = 'storage/profile_photos/' . $filename;
        }

        $user->phone_number = $request->phone_number;
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'profile_photo' => $user->profile_photo
        ]);
    }

    // Superadmin mengubah status user
    public function updateStatus(Request $request, $id)
    {
        if (Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $user = User::findOrFail($id);
        $user->status = $request->status;
        $user->save();

        return response()->json(['message' => 'Status pengguna berhasil diperbarui']);
    }

    // Superadmin melihat daftar admin
    public function listAdmins()
    {
        $admins = User::where('role', 'admin')->get();

        return response()->json($admins);
    }
}
