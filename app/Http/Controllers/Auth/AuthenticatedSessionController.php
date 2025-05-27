<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Cari user berdasarkan nama_pengguna
        $user = User::where('nama_pengguna', $request->get('nama_pengguna'))->first();

        // Cek apakah user ditemukan dan statusnya aktif
        if (!$user || $user->status !== 'active') {
            return back()->withErrors([
                'nama_pengguna' => 'Nama pengguna salah atau akun tidak aktif.',
            ]);
        }

        // Lakukan autentikasi
        $request->authenticate();

        // Regenerasi session
        $request->session()->regenerate();

        // Redirect sesuai role
        if ($user->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Default redirect jika role tidak sesuai
        return redirect('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
