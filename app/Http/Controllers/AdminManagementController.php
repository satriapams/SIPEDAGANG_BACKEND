<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminManagementController extends Controller
{
    // Tampilkan form tambah admin
    public function create()
    {
        return view('superadmin.create-admin'); // kita buat view ini nanti
    }

    // Simpan admin baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nama_pengguna' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = User::create([
            'name' => $request->name,
            'nama_pengguna' => $request->nama_pengguna,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'status' => 'active', // set default status
        ]);

        return response()->json([
            'message' => 'Admin berhasil ditambahkan',
            'admin' => $admin,
        ]);
    }
}
