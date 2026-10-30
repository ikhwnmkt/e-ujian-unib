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
  - `$attempts` (`Illuminate\Database\Eloquent\Collection<App\Models\Attempt>`): Seluruh riwayat pengerjaan mahasiswa beserta eager loading `user` dan `answers.question`.
    - Properti Attempt: `id`, `test_id`, `user_id`, `started_at`, `finished_at`, `nilai`, `is_passed`.
    - *Catatan Multi-Attempt*: Saat ini mengembalikan seluruh attempt mahasiswa (muncul per attempt jika remedial). Kebijakan nilai akhir tunggal (apakah nilai tertinggi atau nilai attempt terakhir) masih menunggu konfirmasi Product Owner (PO).

#### `POST|PUT /dosen/ujian/{test}/nilai/{attempt}`
- **Route Name**: `dosen.nilai.update` (Alias URL: `/dosen/tes/{test}/nilai/{attempt}`)
- **Controller**: `DosenController@updateNilai`
- **Kegunaan**: Dosen menginput atau memperbarui nilai ujian essai mahasiswa.
- **Request Form / JSON**:
  - `nilai`: `required|numeric|min:0|max:100`
- **Response**:
  - **Jika JSON / AJAX**: `{ "status": "success", "message": "Nilai berhasil disimpan.", "attempt_id": 1, "nilai": 88.5 }`
  - **Jika Form Standar**: Redirect `back()->with('ok', 'Nilai mahasiswa berhasil diperbarui.')`.

#### `GET /dosen/ujian/{test}/live-monitoring`
- **Route Name**: `dosen.live-monitoring` (Alias URL: `/dosen/tes/{test}/live-monitoring`)
- **Controller**: `DosenController@liveMonitoring`
- **Kegunaan**: Polling AJAX near real-time oleh frontend dosen untuk memantau ketikan jawaban essai mahasiswa.
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
      "jumlah_soal_tampil": 30
    },
    "server_time": "2026-10-31T01:00:00.000000Z",
    "attempts": [
      {
        "attempt_id": 12,
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

#### `POST /mahasiswa/ujian/{test}/autosave`
- **Route Name**: `mhs.autosave` (Alias URL: `/mahasiswa/tes/{test}/autosave`)
- **Controller**: `MahasiswaController@autosave`
- **Request Headers**:
  - `Content-Type: application/json`
  - `X-CSRF-TOKEN`: nilai token CSRF
- **Request Payload JSON**:
  - `question_id`: `required|exists:questions,id`
  - `jawaban_essai`: `nullable|string|max:65535`
- **Response JSON**: `{ "status": "saved", "updated_at": "2026-10-31T00:44:12.000000Z" }`

#### `POST /mahasiswa/tes/{test}/kirim`
- **Route Name**: `mhs.kirim` (Alias URL: `/mahasiswa/ujian/{test}/kirim`)
- **Controller**: `MahasiswaController@kirim`
- **Logika Penilaian & Idempotensi**:
  - **Idempotensi**: Jika attempt sudah berstatus selesai (`finished_at !== null`), request `kirim` kedua langsung redirect ke `mhs.hasil` tanpa menilai ulang dan tanpa menimpa nilai yang sudah ada.
  - **Pilihan Ganda**:
    - Pembagi persentase nilai dihitung berdasarkan jumlah soal dari `attempt->question_ids` yang **MASIH ADA** di database (`whereIn('id', question_ids)`). Jika ada soal yang dihapus oleh dosen di tengah ujian, pembagi otomatis menyesuaikan tanpa menimbulkan error atau division by zero.
    - Nilai tersimpan di `attempts.nilai`.
  - **Essai**:
    - `attempts.nilai` bernilai `null` (Menunggu Penilaian).
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
  - `$test` (`App\Models\Test` eager load `dosen`, `attempts.user` dan count `questions`).

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
