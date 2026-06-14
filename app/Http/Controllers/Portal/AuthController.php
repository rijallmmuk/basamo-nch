<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Nagari;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->withInput();
        }

        if (! in_array(Auth::user()->role, ['warga', 'umkm_owner'])) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Akun ini tidak memiliki akses portal warga.'])
                ->withInput();
        }

        $request->session()->regenerate();

        return redirect()->route('portal.home');
    }

    public function showRegister(): View
    {
        $nagaris = Nagari::where('status', 'active')->orderBy('nama')->get();

        return view('portal.auth.register', compact('nagaris'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'nagari_id' => 'required|exists:nagaris,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'nagari_id' => $data['nagari_id'],
            'role' => 'warga',
            'status' => 'active',
        ]);

        $user->assignRole('warga');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.home');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
