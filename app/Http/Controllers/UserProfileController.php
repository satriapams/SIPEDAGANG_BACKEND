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
        $request->merge([
            'phone_number' => $request->input('phone_number', '') ?? '',
        ]);

        
        $request->validate([
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'phone_number' => 'nullable|string|max:15',
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = 'profile_' . $user->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile_photos', $filename, 'public');
            $user->profile_photo = 'storage/profile_photos/' . $filename;
        }

        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
        }

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

    // Superadmin melihat daftar admin + fitur search by name / nama_pengguna
    public function listAdmins(Request $request)
    {
        if (Auth::user()->role !== 'superadmin') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $search = $request->query('search');

        $admins = User::where('role', 'admin')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('nama_pengguna', 'like', '%' . $search . '%');
                });
            })
            ->get();

        return response()->json($admins);
    }
}
