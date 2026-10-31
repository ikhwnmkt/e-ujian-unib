<?php
namespace App\Http\Controllers;

use App\Models\{Attempt, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash};
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    public function login(Request $r)
    {
        $cred = $r->validate(['email' => 'required|email', 'password' => 'required']);
        
        $user = User::where('email', $cred['email'])->first();
        if (!$user || !Hash::check($cred['password'], $user->password)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        // 1. Larangan Multi-Device saat Mahasiswa sedang Ujian Aktif
        if ($user->role === 'mahasiswa') {
            $activeAttempts = Attempt::with('test')
                ->where('user_id', $user->id)
                ->whereNull('finished_at')
                ->get();

            $hasActiveExam = $activeAttempts->contains(function ($attempt) {
                return $attempt->test && $attempt->test->berlangsung();
            });

            if ($hasActiveExam) {
                $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;
                $activeOtherSession = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $r->session()->getId())
                    ->where('last_activity', '>=', $cutoff)
                    ->exists();

                if ($activeOtherSession) {
                    return back()->withErrors([
                        'email' => 'Akun Anda sedang aktif mengikuti ujian di perangkat lain. Dilarang login multi-device selama ujian berlangsung.',
                    ])->onlyInput('email');
                }
            }
        }

        // 2. Single Active Device Policy: Invalidate sesi lain milik user ini di perangkat lain
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $r->session()->getId())
            ->delete();

        Auth::login($user, $r->boolean('remember'));
        $r->session()->regenerate();
        return redirect()->route('dashboard');
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

        if ($user->role === 'mahasiswa') {
            $activeAttempts = Attempt::with('test')
                ->where('user_id', $user->id)
                ->whereNull('finished_at')
                ->get();

            $hasActiveExam = $activeAttempts->contains(function ($attempt) {
                return $attempt->test && $attempt->test->berlangsung();
            });

            if ($hasActiveExam) {
                $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;
                $activeOtherSession = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', request()->session()->getId())
                    ->where('last_activity', '>=', $cutoff)
                    ->exists();

                if ($activeOtherSession) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'Akun Anda sedang aktif mengikuti ujian di perangkat lain. Dilarang login multi-device selama ujian berlangsung.',
                    ]);
                }
            }
        }

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', request()->session()->getId())
            ->delete();

        Auth::login($user);
        return redirect()->route('dashboard');
    }
}
