<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    public function login(Request $r)
    {
        $cred = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (Auth::attempt($cred, $r->boolean('remember'))) {
            $r->session()->regenerate();
            return redirect()->route('dashboard');
        }
        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function register(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'nomor_induk' => 'required|string|max:30',
            'role' => 'required|in:mahasiswa,dosen',
            'password' => 'required|min:6|confirmed',
        ]);
        Auth::login(User::create($data));
        return redirect()->route('dashboard');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function googleRedirect() { return Socialite::driver('google')->redirect(); }

    public function googleCallback()
    {
        $g = Socialite::driver('google')->user();
        $user = User::firstOrCreate(
            ['email' => $g->getEmail()],
            ['name' => $g->getName(), 'password' => Str::random(24), 'role' => 'mahasiswa']
        );
        Auth::login($user);
        return redirect()->route('dashboard');
    }
}
