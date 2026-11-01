<?php
use App\Http\Controllers\{AdminController, AuthController, DosenController, MahasiswaController, ProfileController};
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register']);
    Route::get('/auth/google', [AuthController::class, 'googleRedirect'])->name('google');
    Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
    Route::get('/auth/google/otp', [AuthController::class, 'showGoogleOtp'])->name('google.otp');
    Route::post('/auth/google/otp', [AuthController::class, 'verifyGoogleOtp'])->middleware('throttle:5,1')->name('google.otp.verify');
    Route::post('/auth/google/otp/kirim-ulang', [AuthController::class, 'resendGoogleOtp'])->middleware('throttle:1,1')->name('google.otp.resend');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profil', [ProfileController::class, 'show'])->name('profil');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profil.update');

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => redirect()->route(match (auth()->user()->role) {
        'admin' => 'admin.index', 'dosen' => 'dosen.index', default => 'mhs.index',
    }))->name('dashboard');

    Route::prefix('dosen')->name('dosen.')->middleware(RoleMiddleware::class . ':dosen')->group(function () {
        Route::get('/', [DosenController::class, 'index'])->name('index');
        Route::get('/tes/baru', [DosenController::class, 'create'])->name('create');
        Route::post('/tes', [DosenController::class, 'store'])->name('store');
        Route::get('/tes/{test}/edit', [DosenController::class, 'edit'])->name('edit');
        Route::put('/tes/{test}', [DosenController::class, 'update'])->name('update');
        Route::delete('/tes/{test}', [DosenController::class, 'destroy'])->name('destroy');
        Route::get('/tes/{test}/soal', [DosenController::class, 'soal'])->name('soal');
        Route::post('/tes/{test}/soal', [DosenController::class, 'soalStore'])->name('soal.store');
        Route::delete('/tes/{test}/soal/{question}', [DosenController::class, 'soalDestroy'])->name('soal.destroy');
        Route::get('/tes/{test}/nilai', [DosenController::class, 'nilai'])->name('nilai');
        Route::match(['post', 'put'], '/ujian/{test}/nilai/{attempt}', [DosenController::class, 'updateNilai'])->name('nilai.update');
        Route::match(['post', 'put'], '/tes/{test}/nilai/{attempt}', [DosenController::class, 'updateNilai']);
        Route::get('/ujian/{test}/live-monitoring', [DosenController::class, 'liveMonitoring'])->name('live-monitoring');
        Route::get('/tes/{test}/live-monitoring', [DosenController::class, 'liveMonitoring']);
    });

    Route::prefix('mahasiswa')->name('mhs.')->middleware(RoleMiddleware::class . ':mahasiswa')->group(function () {
        Route::get('/', [MahasiswaController::class, 'index'])->name('index');
        Route::get('/tes/{test}/mulai', [MahasiswaController::class, 'mulai'])->name('mulai');
        Route::match(['get', 'post'], '/tes/{test}/remedial', [MahasiswaController::class, 'remedial'])->name('remedial');
        Route::match(['get', 'post'], '/ujian/{test}/remedial', [MahasiswaController::class, 'remedial']);
        Route::post('/ujian/{test}/autosave', [MahasiswaController::class, 'autosave'])->name('autosave');
        Route::post('/tes/{test}/autosave', [MahasiswaController::class, 'autosave']);
        Route::post('/tes/{test}/kirim', [MahasiswaController::class, 'kirim'])->name('kirim');
        Route::post('/ujian/{test}/kirim', [MahasiswaController::class, 'kirim']);
        Route::get('/tes/{test}/hasil', [MahasiswaController::class, 'hasil'])->name('hasil');
    });

    Route::prefix('admin')->name('admin.')->middleware(RoleMiddleware::class . ':admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/tes/{test}', [AdminController::class, 'detail'])->name('detail');
        Route::get('/pengguna/{role}', [AdminController::class, 'users'])->name('users');
        Route::get('/pengguna/{role}/baru', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/pengguna/{role}', [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/pengguna/{role}/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/pengguna/{role}/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::get('/pengguna/{role}/{user}', [AdminController::class, 'userDetail'])->name('users.show');
    });
});
