<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Mengelola autentikasi pengguna (login, registrasi, logout) serta verifikasi profil dan KTP penyewa.
 */
class AuthController extends Controller
{
    /**
     * @param AuthService $authService Layanan autentikasi dan verifikasi penyewa
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Menampilkan halaman form login.
     *
     * @return View
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses permintaan login pengguna menggunakan email dan password.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Menampilkan halaman form registrasi akun penyewa baru.
     *
     * @return View
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Memvalidasi data pendaftaran penyewa baru, mengunggah KTP, dan memulai sesi login.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik'       => ['required', 'digits:16', 'unique:users,nik'],
            'name'      => ['required', 'string', 'max:120'],
            'phone'     => ['required', 'string', 'max:20'],
            'email'     => ['required', 'email', 'max:120', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
            'ktp_image' => ['required', 'image', 'max:2048'],
        ]);

        $user = $this->authService->registerTenant($validated, $request->file('ktp_image'));
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Registrasi berhasil. Menunggu verifikasi KTP oleh Admin.');
    }

    /**
     * Mengakhiri sesi autentikasi pengguna (logout).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Menampilkan daftar seluruh penyewa beserta status verifikasi KTP di panel Admin.
     *
     * @return View
     */
    public function tenants(): View
    {
        $tenants = User::where('role', 'tenant')->latest()->get();

        return view('admin.tenants', compact('tenants'));
    }

    /**
     * Memproses persetujuan (approve) atau penolakan (reject) verifikasi KTP penyewa oleh Admin.
     *
     * @param Request $request
     * @param User $user Model akun penyewa
     * @return RedirectResponse
     */
    public function verifyTenant(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        try {
            $this->authService->verifyTenant($user, $validated['status']);
            return back()->with('success', 'Status verifikasi penyewa diperbarui.');
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Menampilkan halaman profil dan status verifikasi KTP milik penyewa yang sedang login.
     *
     * @return View
     */
    public function profile(): View
    {
        return view('tenant.profile', ['user' => Auth::user()]);
    }

    /**
     * Memproses unggah ulang foto KTP oleh penyewa.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateKtp(Request $request): RedirectResponse
    {
        $request->validate([
            'ktp_image' => ['required', 'image', 'max:2048'],
        ]);

        $this->authService->updateKtp(Auth::user(), $request->file('ktp_image'));

        return back()->with('success', 'Foto KTP diperbarui dan menunggu verifikasi ulang.');
    }
}