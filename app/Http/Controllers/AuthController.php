<?php
namespace App\Http\Controllers;

use App\Models\{Attempt, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private const GOOGLE_OTP_SESSION = 'google_otp';
    private const GOOGLE_OTP_TTL_MINUTES = 10;
    private const GOOGLE_OTP_MAX_ATTEMPTS = 5;

    public function showLogin() { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    public function showGoogleOtp(Request $r)
    {
        $challenge = $r->session()->get(self::GOOGLE_OTP_SESSION);
        $user = $challenge ? User::find($challenge['user_id'] ?? null) : null;

        if (!$user || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $r->session()->forget(self::GOOGLE_OTP_SESSION);
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi OTP tidak valid atau sudah kedaluwarsa. Silakan masuk dengan Google lagi.',
            ]);
        }

        return view('auth.google-otp', ['email' => $this->maskEmail($user->email)]);
    }

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


    public function googleCallback(Request $r)
    {
        try {
            $g = Socialite::driver('google')->user();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            $g = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal autentikasi Google: ' . $e->getMessage(),
            ]);
        }

        $email = $g->getEmail();

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Google tidak menyediakan alamat email yang valid.',
            ]);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $g->getName() ?: Str::before($email, '@'),
                'password' => Str::random(24),
                'role' => 'mahasiswa'
            ]
        );

        // Proteksi login multi-device saat ujian
        if ($user->role === 'mahasiswa') {
            $activeAttempts = Attempt::with('test')
                ->where('user_id', $user->id)
                ->whereNull('finished_at')
                ->get();

            $hasActiveExam = $activeAttempts->contains(function ($attempt) {
                return $attempt->test && $attempt->test->berlangsung();
            });

            if ($hasActiveExam) {
                $cutoff = now()->subMinutes(
                    (int) config('session.lifetime', 120)
                )->timestamp;

                $activeOtherSession = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $r->session()->getId())
                    ->where('last_activity', '>=', $cutoff)
                    ->exists();

                if ($activeOtherSession) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'Akun Anda sedang aktif mengikuti ujian di perangkat lain. Dilarang login multi-device selama ujian berlangsung.',
                    ]);
                }
            }
        }

        // Jika lolos pengecekan, kirim OTP
        $this->sendGoogleOtp($r, $user);

        return redirect()->route('google.otp');
    }


    public function verifyGoogleOtp(Request $r)
    {
        $data = $r->validate(['code' => 'required|string|digits:6']);
        $challenge = $r->session()->get(self::GOOGLE_OTP_SESSION);
        $user = $challenge ? User::find($challenge['user_id'] ?? null) : null;

        if (!$user || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $r->session()->forget(self::GOOGLE_OTP_SESSION);
            return redirect()->route('login')->withErrors([
                'email' => 'Kode OTP tidak valid atau sudah kedaluwarsa. Silakan masuk dengan Google lagi.',
            ]);
        }

        $challenge['attempts'] = ($challenge['attempts'] ?? 0) + 1;
        $r->session()->put(self::GOOGLE_OTP_SESSION, $challenge);

        if (!Hash::check($data['code'], $challenge['code_hash'] ?? '')) {
            if ($challenge['attempts'] >= self::GOOGLE_OTP_MAX_ATTEMPTS) {
                $r->session()->forget(self::GOOGLE_OTP_SESSION);
                return redirect()->route('login')->withErrors([
                    'email' => 'Batas percobaan OTP tercapai. Silakan masuk dengan Google lagi.',
                ]);
            }

            return back()->withErrors(['code' => 'Kode OTP salah. Silakan coba lagi.']);
        }

        $r->session()->forget(self::GOOGLE_OTP_SESSION);
        $user->forceFill(['email_verified_at' => now()])->save();
        
        Auth::login($user);
        $r->session()->regenerate();

        // Hapus sesi login lama setelah OTP berhasil
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $r->session()->getId())
            ->delete();

        return redirect()->route('dashboard');
    }

    public function resendGoogleOtp(Request $r)
    {
        $challenge = $r->session()->get(self::GOOGLE_OTP_SESSION);
        $user = $challenge ? User::find($challenge['user_id'] ?? null) : null;

        if (!$user) {
            $r->session()->forget(self::GOOGLE_OTP_SESSION);
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi OTP tidak valid. Silakan masuk dengan Google lagi.',
            ]);
        }

        $this->sendGoogleOtp($r, $user);

        return redirect()->route('google.otp')->with('status', 'Kode OTP baru telah dikirim.');
    }

    private function sendGoogleOtp(Request $r, User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $r->session()->regenerate();
        $r->session()->put(self::GOOGLE_OTP_SESSION, [
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::GOOGLE_OTP_TTL_MINUTES)->timestamp,
            'attempts' => 0,
        ]);

        Mail::raw(
            "Kode OTP untuk masuk ke E-Ujian UNIB adalah: {$code}\n\nKode ini berlaku selama " . self::GOOGLE_OTP_TTL_MINUTES . " menit. Jangan berikan kode ini kepada siapa pun.",
            fn ($message) => $message->to($user->email)->subject('Kode OTP Login E-Ujian UNIB')
        );
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);

        return substr($name, 0, 1) . str_repeat('*', max(strlen($name) - 1, 2)) . '@' . $domain;
    }
}
