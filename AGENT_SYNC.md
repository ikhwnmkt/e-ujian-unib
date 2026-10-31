# AGENT_SYNC.md - Sinkronisasi Frontend & Backend (Blade Monorepo)

Dokumen ini adalah kontrak sinkronisasi antara **Senior Laravel Backend Developer** dan **Frontend Agent** untuk proyek monorepo Laravel Blade **E-Ujian UNIB**.

---

## 📌 Aturan Kolaborasi
1. **Backend Agent**:
   - Menangani Migration, Model, Logic, Controller, Form Request, dan Route.
   - Tidak mengubah desain UI pada file `.blade.php` (hanya membuat skeleton jika diminta).
   - **WAJIB** memperbarui dokumen ini setiap kali membuat/mengubah Route, Controller, struktur data ke View, atau validasi form.
   - **WAJIB** membaca bagian [Frontend Requests / Backlog](#-frontend-requests--backlog) sebelum memulai fitur baru.
2. **Frontend Agent**:
   - Menangani desain, komponen, styling, dan struktur tampilan Blade.
   - Menulis permintaan penambahan/perubahan endpoint atau variabel data di bagian [Frontend Requests / Backlog](#-frontend-requests--backlog).
   - Menggunakan data variabel dan nama route sesuai dokumentasi di file ini.

---

## 📥 Frontend Requests / Backlog
> *Frontend Agent dapat menambahkan permintaan kebutuhan data, perubahan struktur variabel, atau endpoint baru di bawah ini.*

*Belum ada request aktif dari Frontend Agent saat ini.*

---

## 📋 Status Sinkronisasi Endpoint & View

### 1. Autentikasi (`guest` & `auth`)

#### `GET /login`
- **Route Name**: `login`
- **Controller**: `AuthController@showLogin`
- **Target Blade**: `resources/views/auth/login.blade.php`
- **Data ke View**: *(tidak ada)*
- **Form Submission**: `POST /login`
  - `email`: `required|email`
  - `password`: `required`
  - `remember`: `boolean (optional)`
  - **Proteksi Single Active Device & Lock Ujian Aktif**:
    - **Mahasiswa Sedang Ujian**: Jika akun mahasiswa terdeteksi sedang memiliki sesi ujian aktif yang belum diserahkan (`Attempt::finished_at == null`) pada jadwal tes yang sedang berlangsung, upaya login dari perangkat/browser lain **DITOLAK** dengan pesan error:
      `"Akun Anda sedang aktif mengikuti ujian di perangkat lain. Dilarang login multi-device selama ujian berlangsung."`
    - **Kebijakan Single Active Device (Semua Role)**: Setiap kali login baru berhasil, sesi lama milik akun tersebut di perangkat lain otomatis dibatalkan/dihapus dari tabel `sessions`.

#### `GET /daftar`
- **Route Name**: `register`
- **Controller**: `AuthController@showRegister`
- **Target Blade**: `resources/views/auth/register.blade.php`
- **Data ke View**: *(tidak ada)*
- **Form Submission**: `POST /daftar`
  - `name`: `required|string|max:255`
  - `email`: `required|email|unique:users`
  - `nomor_induk`: `required|string|max:30`
  - `role`: `required|in:mahasiswa,dosen`
  - `password`: `required|min:6|confirmed`

#### `GET /auth/google` & `GET /auth/google/callback`
- **Route Name**: `google`
- **Controller**: `AuthController@googleRedirect` & `googleCallback`
- **Target Blade**: *(Redirect ke Google OAuth / Dashboard)*

#### `POST /logout`
- **Route Name**: `logout`
- **Controller**: `AuthController@logout`

---

### 2. Profil Pengguna (`auth`)

#### `GET /profil`
- **Route Name**: `profil`
- **Controller**: `ProfileController@show`
- **Target Blade**: `resources/views/profil.blade.php`
- **Data ke View**:
  - `$user` (`App\Models\User`): Data pengguna yang sedang login (`auth()->user()`).
    - Properti: `id`, `name`, `email`, `role`, `nomor_induk`, `fakultas`, `prodi`, `tempat_lahir`, `tanggal_lahir`, `no_hp`, `avatar`, `status`.
- **Form Submission**: `POST /profil` (`route('profil.update')`)
  - `name`: `required|string|max:255`
  - `tempat_lahir`: `nullable|string|max:100`
  - `tanggal_lahir`: `nullable|date`
  - `no_hp`: `nullable|string|max:20`
  - `avatar`: `nullable|image|max:2048`

---

### 3. Dashboard Dosen (`prefix: dosen`, `middleware: auth, role:dosen`)

#### Model `Test` (Pembaruan Skema Lengkap)
- **Kolom Tabel `tests`**:
  - `id` (`bigint`)
  - `dosen_id` (`foreignId -> users`)
  - `judul` (`string`)
  - `materi` (`string`, `nullable`) *(Baru)*: Nama materi/topik bahasan ujian.
  - `tipe_ujian` (`enum: 'pilihan_ganda', 'essai'`)
  - `jumlah_soal_tampil` (`integer`, `default: 30`) *(Baru)*: Jumlah soal yang ditarik secara acak per attempt (subset random).
  - `kkm` (`integer`, `default: 70`) *(Baru)*: Batas nilai kelulusan / passing grade mahasiswa.
  - `tanggal` (`date`)
  - `jam_mulai` (`time`)
  - `jam_selesai` (`time`)
  - `durasi` (`unsignedInteger`, menit)
  - `sifat` (`enum: 'fleksibel', 'ketat'`)
- **Helper Model `Test`**:
  - `$test->isPilihanGanda()`: `bool`
  - `$test->isEssai()`: `bool`
  - `$test->tipe_ujian_label`: `string` ('Pilihan Ganda' | 'Esai')
  - `$test->mulai_at`, `$test->selesai_at`, `$test->berlangsung()`, `$test->selesai()`

#### Model `Attempt` (Pembaruan Skema & Remedial)
- **Kolom Tabel `attempts`**:
  - `id` (`bigint`)
  - `test_id` (`foreignId -> tests`)
  - `user_id` (`foreignId -> users`)
  - `question_ids` (`json/array`, `nullable`) *(Baru)*: Menyimpan array ID soal yang diacak untuk attempt ini agar urutan dan butir soal konsisten saat refresh.
  - `started_at` (`datetime`)
  - `finished_at` (`datetime`, `nullable`)
  - `nilai` (`decimal(5,2)`, `nullable`)
  - *Catatan Skema*: Unique constraint `(test_id, user_id)` telah dihapus untuk mendukung riwayat attempt multiple / remedial.
- **Helper Model `Attempt`**:
  - `$attempt->isPassed()` / `$attempt->is_passed`: `bool` — bernilai `true` jika `$attempt->nilai !== null && $attempt->nilai >= $test->kkm`.
  - `$attempt->canRemedial()`: `bool` — bernilai `true` jika ujian pilihan ganda, sudah diserahkan (`finished_at !== null`), nilai di bawah KKM, dan waktu tes masih aktif (`$test->berlangsung()`).

#### `GET /dosen`
- **Route Name**: `dosen.index`
- **Controller**: `DosenController@index`
- **Target Blade**: `resources/views/dosen/index.blade.php`
- **Data ke View**:
  - `$aktif` (`Illuminate\Support\Collection<App\Models\Test>`): Daftar tes yang belum selesai (`!$t->selesai()`).
  - `$riwayat` (`Illuminate\Support\Collection<App\Models\Test>`): Daftar tes yang sudah lewat/selesai (`$t->selesai()`).

#### `GET /dosen/tes/baru`
- **Route Name**: `dosen.create`
- **Controller**: `DosenController@create`
- **Target Blade**: `resources/views/dosen/form.blade.php`
- **Data ke View**:
  - `$test` (`App\Models\Test`): Instance baru Test dengan nilai awal:
    - `durasi = 90`, `sifat = 'fleksibel'`, `tipe_ujian = 'pilihan_ganda'`, `jumlah_soal_tampil = 30`, `kkm = 70`.
- **Form Submission**: `POST /dosen/tes` (`route('dosen.store')`)
  - `judul`: `required|string|max:255`
  - `materi`: `nullable|string|max:255` *(Baru)*
  - `tipe_ujian`: `required|in:pilihan_ganda,essai` (default: `pilihan_ganda`)
  - `jumlah_soal_tampil`: `nullable|integer|min:1|max:1000` (default: 30) *(Baru)*
  - `kkm`: `nullable|integer|min:0|max:100` (default: 70) *(Baru)*
  - `tanggal`: `required|date`
  - `jam_mulai`: `required|date_format:H:i`
  - `jam_selesai`: `required|date_format:H:i|after:jam_mulai`
  - `durasi`: `required|integer|min:1|max:600`
  - `sifat`: `required|in:fleksibel,ketat`

#### `GET /dosen/tes/{test}/edit`
- **Route Name**: `dosen.edit`
- **Controller**: `DosenController@edit`
- **Target Blade**: `resources/views/dosen/form.blade.php`
- **Data ke View**:
  - `$test` (`App\Models\Test`): Data tes milik dosen bersangkutan.
- **Form Submission**: `PUT /dosen/tes/{test}` (`route('dosen.update', $test)`)
  - Aturan validasi sama dengan `dosen.store`.

#### `DELETE /dosen/tes/{test}`
- **Route Name**: `dosen.destroy`
- **Controller**: `DosenController@destroy`

#### `GET /dosen/tes/{test}/soal`
- **Route Name**: `dosen.soal`
- **Controller**: `DosenController@soal`
- **Target Blade**: `resources/views/dosen/soal.blade.php`
- **Data ke View**:
  - `$test` (`App\Models\Test` eager load `questions`): Instance model `Test` beserta koleksi `questions`.
- **Form Submission**: `POST /dosen/tes/{test}/soal` (`route('dosen.soal.store', $test)`)
  - `tipe`: `nullable|in:pilihan_ganda,essai` (otomatis mengikuti `$test->tipe_ujian` jika tidak disertakan)
  - `pertanyaan`: `required|string`
  - `media_type`: `nullable|in:gambar,link`
  - `media_url`: `nullable|url|max:2048|required_with:media_type`
  - Jika `tipe == 'pilihan_ganda'`:
    - `opsi_a`: `required|string|max:255`
    - `opsi_b`: `required|string|max:255`
    - `opsi_c`: `required|string|max:255`
    - `opsi_d`: `required|string|max:255`
    - `jawaban_benar`: `required|in:a,b,c,d`

#### `DELETE /dosen/tes/{test}/soal/{question}`
- **Route Name**: `dosen.soal.destroy`
- **Controller**: `DosenController@soalDestroy`

#### `GET /dosen/tes/{test}/nilai`
- **Route Name**: `dosen.nilai`
- **Controller**: `DosenController@nilai`
- **Target Blade**: `resources/views/dosen/nilai.blade.php`
- **Data ke View**:
  - `$test` (`App\Models\Test`): Data tes.
  - `$attempts` (`Illuminate\Support\Collection<App\Models\Attempt>`): Koleksi **Attempt utama ter-deduplikasi (1 baris per mahasiswa)**, di mana mahasiswa yang remedial tidak muncul sebagai baris ganda.
    - **Aturan Pemilihan Record Utama**: Menggunakan attempt dengan nilai tertinggi (`best_score`), atau attempt paling baru jika nilai sama/belum dinilai.
    - **Properti Model Attempt Bawaan**: `id`, `test_id`, `user_id`, `started_at`, `finished_at`, `nilai`, `is_passed`, `user`.
    - **Atribut Dinamis (Siap Dipakai Frontend Blade)**:
      - `attempt_count` (`int`): Total berapa kali mahasiswa tersebut mencoba ujian ini (misal: `1` untuk peserta reguler, `2` atau lebih untuk yang remedial). Frontend dapat menampilkan badge seperti `Percobaan ke-2`.
      - `first_score` (`float|null`): Nilai pada percobaan pertama mahasiswa.
      - `latest_score` (`float|null`): Nilai pada percobaan terakhir mahasiswa.
      - `best_score` (`float|null`): Nilai tertinggi yang diraih dari seluruh percobaan.
      - `history` (`Illuminate\Support\Collection<App\Models\Attempt>`): Riwayat lengkap seluruh percobaan mahasiswa (terurut dari percobaan ke-1 hingga terakhir) untuk kebutuhan dropdown/accordion audit detail.

#### `POST|PUT /dosen/ujian/{test}/nilai/{attempt}`
- **Route Name**: `dosen.nilai.update` (Alias URL: `/dosen/tes/{test}/nilai/{attempt}`)
- **Controller**: `DosenController@updateNilai`
- **Kegunaan**: Dosen menginput atau memperbarui nilai ujian essai mahasiswa.
- **Guard Tipe Ujian**: Penilaian manual **HANYA diizinkan untuk ujian bertipe `essai`**. Jika dipanggil pada ujian pilihan ganda, request ditolak dengan **HTTP 422 Unprocessable Content**:
  `{ "status": "error", "message": "Penilaian manual hanya diizinkan untuk ujian bertipe essai." }`
- **Request Form / JSON**:
  - `nilai`: `required|numeric|min:0|max:100`
- **Response**:
  - **Jika JSON / AJAX**: `{ "status": "success", "message": "Nilai berhasil disimpan.", "attempt_id": 1, "nilai": 88.5 }`
  - **Jika Form Standar**: Redirect `back()->with('ok', 'Nilai mahasiswa berhasil diperbarui.')`.

#### `GET /dosen/ujian/{test}/live-monitoring`
- **Route Name**: `dosen.live-monitoring` (Alias URL: `/dosen/tes/{test}/live-monitoring`)
- **Controller**: `DosenController@liveMonitoring`
- **Target Blade**: `resources/views/dosen/live-monitoring.blade.php` (Jika diakses via browser HTML biasa)
- **Kegunaan**:
  - **Akses Browser (HTML)**: Dosen langsung disajikan halaman visual Live Monitoring dengan dashboard realtime, counter peserta, dan polling otomatis setiap 3 detik.
  - **Akses AJAX/JSON**: Mengembalikan payload JSON near real-time untuk komponen frontend / polling JavaScript.
- **Deduplikasi**: Mahasiswa yang remedial **hanya muncul 1 kali** (mengambil attempt paling mutakhir / `latest('id')`), dengan penanda `attempt_count`.
- **Response JSON**:
  ```json
  {
    "status": "success",
    "test": {
      "id": 1,
      "judul": "Ujian Algoritma",
      "materi": "Sorting & Searching",
      "tipe_ujian": "pilihan_ganda",
      "kkm": 70,
      "jumlah_soal_tampil": 30,
      "peserta_count": 1
    },
    "server_time": "2026-10-31T01:00:00.000000Z",
    "attempts": [
      {
        "attempt_id": 15,
        "attempt_count": 2,
        "user": {
          "id": 5,
          "name": "Bahlil Lahadalia",
          "nomor_induk": "2405090032",
          "avatar": null
        },
        "started_at": "2026-10-31T00:10:00.000000Z",
        "finished_at": null,
        "is_finished": false,
        "nilai": null,
        "is_passed": false,
        "updated_at": "2026-10-31T00:44:50.000000Z",
        "answers": [
          {
            "id": 101,
            "question_id": 3,
            "pertanyaan": "Jelaskan konsep quick sort...",
            "tipe": "essai",
            "jawaban_essai": "Quick sort menggunakan strategi divide and conquer...",
            "updated_at": "2026-10-31T00:44:50.000000Z"
          }
        ]
      }
    ]
  }
  ```

---

### 4. Portal Mahasiswa (`prefix: mahasiswa`, `middleware: auth, role:mahasiswa`)

#### `GET /mahasiswa`
- **Route Name**: `mhs.index`
- **Controller**: `MahasiswaController@index`
- **Target Blade**: `resources/views/mahasiswa/index.blade.php`
- **Data ke View**:
  - `$aktif` (`Illuminate\Support\Collection<App\Models\Test>`): Ujian aktif yang belum diselesaikan ATAU ujian pilihan ganda yang memenuhi syarat remedial (`canRemedial() == true`).
  - `$riwayat` (`Illuminate\Support\Collection<App\Models\Test>`): Ujian yang telah lulus atau jadwal ujian telah selesai.
  - `$attempts` (`Illuminate\Support\Collection<int, App\Models\Attempt>`): Map attempt mahasiswa terbaru keyed by `test_id`.

#### `GET /mahasiswa/tes/{test}/mulai`
- **Route Name**: `mhs.mulai`
- **Controller**: `MahasiswaController@mulai`
- **Target Blade**: `resources/views/mahasiswa/ujian.blade.php`
- **Logika & Alur**:
  1. **Transaksional & Anti Dobel Attempt (Idempotent)**:
     - Dibungkus dalam `DB::transaction` dan diserialisasi per user menggunakan `User::lockForUpdate()`.
     - Menjamin untuk satu pasangan `(test_id, user_id)` **tidak akan pernah ada lebih dari satu attempt aktif (`finished_at = null`)**. Panggilan kedua/simultan akan melanjutkan attempt aktif yang sama.
  2. **Guard Bank Soal Kosong**:
     - Jika jumlah soal pada bank soal tes = 0, pembuatan attempt **ditolak** dan diredirect ke dashboard dengan flash session `err`:
       `"Soal ujian belum tersedia. Silakan hubungi dosen pengampu."`
  3. **Auto-Finalize Attempt Kedaluwarsa (Expired)**:
     - Jika mahasiswa kembali ke attempt yang belum selesai namun waktu pengerjaan telah habis (`now() > deadline`), sistem secara otomatis memfinalisasi attempt tersebut (`finished_at = deadline`, nilai dihitung dari jawaban tersimpan), lalu mengevaluasi kelayakan remedial.
  4. **Attempt Baru & Randomisasi Subset**:
     - Sistem mengambil `min(totalSoal, test->jumlah_soal_tampil)` soal secara acak (`inRandomOrder()`).
     - Array ID soal disimpan di `attempt->question_ids`.
  5. **Konsistensi Soal (Anti Re-shuffle saat Refresh)**:
     - Soal di-query dan diurutkan tepat sesuai urutan di `attempt->question_ids`.
  6. **Mekanisme Remedial**:
     - Jika mahasiswa sudah pernah submit ujian pilihan ganda dan nilainya di bawah KKM (`nilai < test->kkm`), dan jadwal tes masih berlangsung, halaman ini otomatis membuat **Attempt Baru** dengan himpunan acak soal yang baru.
     - Jika mahasiswa sudah lulus (`nilai >= test->kkm`) atau ujian bertipe essai, pembuatan attempt baru dicegah dan diarahkan ke halaman hasil (`mhs.hasil`).
- **Data ke View**:
  - `$test` (`App\Models\Test`): Objek ujian lengkap dengan `materi`, `kkm`, dan `tipe_ujian`.
  - `$questions` (`Illuminate\Support\Collection<App\Models\Question>`): Soal subset yang terkunci pada attempt ini.
  - `$sisaDetik` (`int`): Sisa durasi ujian dalam detik.

#### `POST|GET /mahasiswa/tes/{test}/remedial`
- **Route Name**: `mhs.remedial` (Alias URL: `/mahasiswa/ujian/{test}/remedial`)
- **Controller**: `MahasiswaController@remedial`
- **Kegunaan**: Endpoint eksplisit untuk tombol "Ulangi Ujian" / "Mulai Remedial" di frontend (dapat diakses via POST form maupun link GET). Me-redirect ke `route('mhs.mulai', $test)`. Frontend bebas memakai `<a href="{{ route('mhs.mulai', $test) }}">` ataupun form submit `route('mhs.remedial', $test)`.

#### `POST /mahasiswa/ujian/{test}/autosave`
- **Route Name**: `mhs.autosave` (Alias URL: `/mahasiswa/tes/{test}/autosave`)
- **Controller**: `MahasiswaController@autosave`
- **Kegunaan**:
  - **Pilihan Ganda**: Menyimpan pilihan radio button (A/B/C/D) secara instan saat opsi diklik mahasiswa, sekaligus menghitung status kebenaran (`benar`) di DB. Jika waktu ujian habis tanpa menekan tombol "Kirim", nilai akhir dihitung dari data yang tersimpan di DB ini (`finalizeAttempt`).
  - **Essai**: Menyimpan ketikan jawaban essai secara live untuk monitoring dosen.
- **Request Headers**:
  - `Content-Type: application/json`
  - `X-CSRF-TOKEN`: nilai token CSRF
- **Request Payload JSON**:
  - `question_id`: `required|exists:questions,id`
  - `pilihan`: `nullable|in:a,b,c,d` *(Baru - untuk Pilihan Ganda)*
  - `jawaban`: `nullable|in:a,b,c,d` *(Alias untuk `pilihan`)*
  - `jawaban_essai`: `nullable|string|max:65535` *(Untuk Essai)*
- **Response**:
  - **Status Normal (HTTP 200 OK)**:
    ```json
    { "status": "saved", "updated_at": "2026-10-31T00:44:12.000000Z" }
    ```
  - **Status Kedaluwarsa / Selesai (HTTP 409 Conflict)**:
    Jika attempt sudah `finished_at !== null` atau waktu ujian telah habis (`now() > deadline`), attempt otomatis difinalisasi via `finalizeAttempt()` dan mengembalikan response 409 agar frontend mahasiswa menghentikan autosave timer dan me-redirect browser:
    ```json
    {
      "expired": true,
      "message": "Waktu ujian telah habis.",
      "redirect": "http://domain.test/mahasiswa/tes/1/hasil"
    }
    ```

#### `POST /mahasiswa/tes/{test}/kirim`
- **Route Name**: `mhs.kirim` (Alias URL: `/mahasiswa/ujian/{test}/kirim`)
- **Controller**: `MahasiswaController@kirim`
- **Toleransi Keterlambatan (Grace Period 60 Detik)**:
  - Menggunakan konfigurasi `config('exam.submit_grace_seconds', 60)` (default 60 detik).
  - **Di dalam masa grace period (`now() <= deadline + 60s`)**: Request pengerjaan diterima dan dievaluasi secara normal dari payload form yang dikirim mahasiswa.
  - **Setelah lewat grace period (`now() > deadline + 60s`)**: Payload jawaban baru dari form request diabaikan, sistem memanggil `finalizeAttempt()` (menghitung nilai dari jawaban yang sudah tersimpan di database untuk pilgan, atau `null` untuk essai), lalu redirect ke `mhs.hasil` dengan flash session `err`: `"Waktu ujian telah habis."`.
- **Logika Penilaian & Idempotensi**:
  - **Idempotensi**: Jika attempt sudah berstatus selesai (`finished_at !== null`), request `kirim` kedua langsung redirect ke `mhs.hasil` tanpa menilai ulang dan tanpa menimpa nilai yang sudah ada.
  - **Pilihan Ganda**:
    - Pembagi persentase nilai dihitung berdasarkan jumlah soal dari `attempt->question_ids` yang **MASIH ADA** di database (`whereIn('id', question_ids)`). Jika ada soal yang dihapus oleh dosen di tengah ujian, pembagi otomatis menyesuaikan tanpa menimbulkan error atau division by zero.
    - Nilai tersimpan di `attempts.nilai`.
  - **Essai**:
    - `attempts.nilai` bernilai `null` (Menunggu Penilaian). Kolom `nilai` tidak pernah dihitung angka secara otomatis pada alur finalisasi ujian essai.
- **Form Submission**:
  - `jawaban`: `nullable|array` (Key: `question_id`, Value: `a`|`b`|`c`|`d`)
  - `jawaban.*`: `nullable|in:a,b,c,d`
  - `jawaban_essai`: `nullable|array` (Key: `question_id`, Value: `string`)
  - `jawaban_essai.*`: `nullable|string|max:65535`
- **Redirect**: `route('mhs.hasil', $test)`

#### `GET /mahasiswa/tes/{test}/hasil`
- **Route Name**: `mhs.hasil`
- **Controller**: `MahasiswaController@hasil`
- **Target Blade**: `resources/views/mahasiswa/hasil.blade.php`
- **Data ke View**:
  - `$test` (`App\Models\Test`): Objek ujian.
  - `$attempt` (`App\Models\Attempt` eager load `answers.question`): Data attempt paling mutakhir/terbaru.
    - Properti tambahan: `$attempt->is_passed` (`bool`), `$attempt->canRemedial()` (`bool`).
  - `$allAttempts` (`Illuminate\Database\Eloquent\Collection<App\Models\Attempt>`): Seluruh riwayat pengerjaan mahasiswa untuk tes ini (diurutkan dari attempt terbaru ke terlama), dapat digunakan oleh frontend untuk menampilkan timeline remedial / riwayat skor.
  - `$durasiDetik` (`int`): Total waktu pengerjaan attempt terakhir dalam detik.

---

### 5. Portal Admin (`prefix: admin`, `middleware: auth, role:admin`)

#### `GET /admin`
- **Route Name**: `admin.index`
- **Controller**: `AdminController@index`
- **Target Blade**: `resources/views/admin/index.blade.php`
- **Data ke View**:
  - `$tests` (`Illuminate\Database\Eloquent\Collection<App\Models\Test>`): Seluruh daftar ujian terurut dari tanggal terbaru.

#### `GET /admin/tes/{test}`
- **Route Name**: `admin.detail`
- **Controller**: `AdminController@detail`
- **Target Blade**: `resources/views/admin/detail.blade.php`
- **Data ke View**:
  - `$test` (`App\Models\Test` eager load `dosen`, count `questions`, dan relasi `attempts` yang telah di-deduplikasi 1 record per mahasiswa):
    - `$test->attempts`: Berisi koleksi record Attempt utama (nilai terbaik/terakhir) lengkap dengan atribut dinamis `attempt_count`, `first_score`, `latest_score`, `best_score`, dan `history`. Mahasiswa remedial tidak muncul sebagai baris ganda pada tabel peserta admin.

#### `GET /admin/pengguna/{role}`
- **Route Name**: `admin.users`
- **Controller**: `AdminController@users`
- **Target Blade**: `resources/views/admin/users.blade.php`
- **Data ke View**:
  - `$role` (`string`): `mahasiswa` atau `dosen`.
  - `$users` (`Illuminate\Database\Eloquent\Collection<App\Models\User>`): Daftar user sesuai role terurut nama.

#### `GET /admin/pengguna/{role}/baru`
- **Route Name**: `admin.users.create`
- **Controller**: `AdminController@createUser`
- **Target Blade**: `resources/views/admin/user-form.blade.php`
- **Data ke View**:
  - `$role` (`string`): `mahasiswa` atau `dosen`.
  - `$user` (`App\Models\User`): Instance kosong model User.

#### `GET /admin/pengguna/{role}/{user}`
- **Route Name**: `admin.users.show`
- **Controller**: `AdminController@userDetail`
- **Target Blade**: `resources/views/admin/user-detail.blade.php`
- **Data ke View**:
  - `$role` (`string`): `mahasiswa` atau `dosen`.
  - `$user` (`App\Models\User`): Data user detail.

#### `GET /admin/pengguna/{role}/{user}/edit`
- **Route Name**: `admin.users.edit`
- **Controller**: `AdminController@editUser`
- **Target Blade**: `resources/views/admin/user-form.blade.php`
- **Data ke View**:
  - `$role` (`string`): `mahasiswa` atau `dosen`.
  - `$user` (`App\Models\User`): Data user yang akan diedit.

---

### 6. Struktur Database Migration & Seeder Modular

#### A. Struktur Migration (1 Tabel = 1 File Migration)
Arsitektur migrasi database telah dirapikan menjadi format 1 tabel per file tanpa file alter tambahan:
1. `0001_01_01_000000_create_users_table.php` &rarr; Tabel `users` (lengkap dengan atribut profil, role, dan prodi).
2. `0001_01_01_000001_create_password_reset_tokens_table.php` &rarr; Tabel `password_reset_tokens`.
3. `0001_01_01_000002_create_sessions_table.php` &rarr; Tabel `sessions`.
4. `0001_01_01_000003_create_cache_table.php` &rarr; Tabel `cache` & `cache_locks`.
5. `0001_01_01_000004_create_jobs_table.php` &rarr; Tabel `jobs`, `job_batches`, & `failed_jobs`.
6. `2026_10_30_000001_create_tests_table.php` &rarr; Tabel `tests` (lengkap dengan `tipe_ujian`, `materi`, `kkm`, `jumlah_soal_tampil`).
7. `2026_10_30_000002_create_questions_table.php` &rarr; Tabel `questions` (lengkap dengan `tipe`, `pertanyaan`, opsi A-D, kunci, media).
8. `2026_10_30_000003_create_attempts_table.php` &rarr; Tabel `attempts` (lengkap dengan `question_ids`, `started_at`, `finished_at`, `nilai`, dan index non-unique untuk remedial).
9. `2026_10_30_000004_create_answers_table.php` &rarr; Tabel `answers` (lengkap dengan `jawaban`, `jawaban_essai`, `benar`).

#### B. Seeder Modular
Seeder dapat dijalankan sekaligus via `php artisan db:seed` atau secara terpisah per modul:
* `Database\Seeders\UserSeeder` &rarr; Mengisi akun Admin, Dosen, dan 3 Mahasiswa.
* `Database\Seeders\TestPilganSeeder` &rarr; Mengisi Ujian Pilgan (50 butir bank soal) dan riwayat remedial Mahasiswa 2.
* `Database\Seeders\TestEssaiSeeder` &rarr; Mengisi Ujian Essai (5 butir soal essai), attempt live monitoring Mahasiswa 2, dan attempt siap nilai Mahasiswa 3.
* `Database\Seeders\DatabaseSeeder` &rarr; Master seeder yang memanggil ketiga seeder di atas.

#### C. Kredensial Login & Skenario Data Demo (`php artisan migrate:fresh --seed`)

Untuk keperluan pengujian UI/Frontend, database telah diisi data demo realistis:

#### Akun Pengguna (Password seluruh akun: `password`)
| Role | Email | Password | Nomor Induk / NPM | Nama Lengkap | Skenario Demo |
|---|---|---|---|---|---|
| **Admin** | `admin@unib.ac.id` | `password` | - | Administrator UNIB | Kelola pengguna & lihat detail ujian peserta ter-deduplikasi. |
| **Dosen** | `dosen@unib.ac.id` | `password` | `198001012005012001` | Dr. Ir. Dosen Pengampu, M.Kom. | Pembuat ujian, Live Monitoring ketikan essai, dan input nilai essai manual. |
| **Mahasiswa 1** | `mhs1@unib.ac.id` | `password` | `G1A021001` | Aditya Pratama | **Fresh User**: Belum memiliki attempt sama sekali. Siap untuk demo klik "Mulai Ujian" dari nol (acak 30 soal dari 50 bank soal). |
| **Mahasiswa 2** | `mhs2@unib.ac.id` | `password` | `G1A021002` | Budi Santoso | **Multi-Attempt & Live**: <br>1. *Ujian Pilgan*: Punya 2 attempt (Attempt 1 gagal skor 40, Attempt 2 lulus skor 80) -> Siap uji deduplikasi & riwayat remedial.<br>2. *Ujian Essai*: Sedang aktif mengerjakan (attempt belum selesai) dengan 2 jawaban live monitoring. |
| **Mahasiswa 3** | `mhs3@unib.ac.id` | `password` | `G1A021003` | Citra Lestari | **Siap Dinilai**: <br>Sudah submit ujian essai lengkap (`nilai = null`, Menunggu Penilaian). Siap untuk demo form input/update nilai oleh Dosen. |

#### Daftar Ujian Tersedia (Aktif Hari Ini)
1. **Ujian Pilihan Ganda**:
   - Judul: *Ujian Akhir Semester: Struktur Data & Algoritma*
   - Materi: *Struktur Data & Algoritma*
   - Bank Soal: **50 soal** (diambil acak **30 soal** per attempt)
   - Durasi: **60 menit**, Sifat: **Fleksibel**, KKM: **70**
2. **Ujian Essai**:
   - Judul: *Ujian Tengah Semester: Analisis Kompleksitas Algoritma*
   - Materi: *Analisis Kompleksitas Algoritma*
   - Soal: **5 butir soal essai**
   - Durasi: **90 menit**, Sifat: **Fleksibel**, KKM: **70**

