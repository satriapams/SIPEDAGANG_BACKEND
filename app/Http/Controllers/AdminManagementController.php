<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // tambahkan ini di atas


class AdminManagementController extends Controller
{
    // Tambah admin baru
    public function store(Request $request)
    {
        $request->merge([
            'phone_number' => $request->input('phone_number', '') ?? '',
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nama_pengguna' => 'required|string|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = new User();
        $admin->name = $request->name;
        $admin->nama_pengguna = $request->nama_pengguna;
        $admin->phone_number = $request->phone_number ?? '';
        $admin->password = Hash::make($request->password);
        $admin->plain_password = $request->password;
        $admin->role = 'admin';
        $admin->status = 'active';

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = 'profile_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile_photos', $filename, 'public');
            $admin->profile_photo = 'storage/profile_photos/' . $filename;
        }

        $admin->save();

        return response()->json([
            'message' => 'Admin berhasil ditambahkan',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'nama_pengguna' => $admin->nama_pengguna,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
                'plain_password' => $admin->plain_password,
            ]
        ]);
    }

    // Tampilkan detail admin berdasarkan ID
    public function show($id_admin)
    {
        $admin = User::where('role', 'admin')->findOrFail($id_admin);

        return response()->json([
            'id' => $admin->id,
            'name' => $admin->name,
            'nama_pengguna' => $admin->nama_pengguna,
            'profile_photo' => $admin->profile_photo,
            'phone_number' => $admin->phone_number,
            'plain_password' => $admin->plain_password,
        ]);
    }

    // Update data admin
    public function update(Request $request, $id_admin)
    {
        $admin = User::where('role', 'admin')->findOrFail($id_admin);

        $request->merge([
            'phone_number' => $request->input('phone_number', '') ?? '',
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'nama_pengguna' => 'sometimes|string|max:255|unique:users,nama_pengguna,' . $admin->id,
            'phone_number' => 'nullable|string|max:20',
            'profile_photo' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $admin->name = $request->name;
        }
        if ($request->has('nama_pengguna')) {
            $admin->nama_pengguna = $request->nama_pengguna;
        }
        if ($request->has('phone_number')) {
            $admin->phone_number = $request->phone_number ?? '';
        }

        if ($request->hasFile('profile_photo')) {
            // Hapus file lama jika ada
            if ($admin->profile_photo && Storage::disk('public')->exists(str_replace('storage/', '', $admin->profile_photo))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $admin->profile_photo));
            }
        
            // Upload file baru
            $file = $request->file('profile_photo');
            $filename = 'profile_' . $admin->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile_photos', $filename, 'public');
            $admin->profile_photo = 'storage/profile_photos/' . $filename;
        }


        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
            $admin->plain_password = $request->password;
        }

        $admin->save();

        return response()->json([
            'message' => 'Admin berhasil diperbarui',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'nama_pengguna' => $admin->nama_pengguna,
                'phone_number' => $admin->phone_number,
                'profile_photo' => $admin->profile_photo,
                'plain_password' => $admin->plain_password,
            ]
        ]);
    }
}