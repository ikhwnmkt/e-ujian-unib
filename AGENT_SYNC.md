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

#### Model `Test` (Pembaruan Skema)
- **Kolom Tabel `tests`**:
  - `id` (`bigint`)
  - `dosen_id` (`foreignId -> users`)
  - `judul` (`string`)
  - `tipe_ujian` (`enum: 'pilihan_ganda', 'essai'`) *(Baru)*
  - `tanggal` (`date`)
  - `jam_mulai` (`time`)
  - `jam_selesai` (`time`)
  - `durasi` (`unsignedInteger`, menit)
  - `sifat` (`enum: 'fleksibel', 'ketat'`)
- **Helper Model**:
  - `$test->isPilihanGanda()`: `bool`
  - `$test->isEssai()`: `bool`
  - `$test->tipe_ujian_label`: `string` ('Pilihan Ganda' | 'Esai')
  - `$test->mulai_at`, `$test->selesai_at`, `$test->berlangsung()`, `$test->selesai()`

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
  - `$test` (`App\Models\Test`): Instance baru Test dengan default `durasi = 90`, `sifat = 'fleksibel'`, `tipe_ujian = 'pilihan_ganda'`.
- **Form Submission**: `POST /dosen/tes` (`route('dosen.store')`)
  - `judul`: `required|string|max:255`
  - `tipe_ujian`: `required|in:pilihan_ganda,essai` (default jika kosong: `pilihan_ganda`)
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
  - `$attempts` (`Illuminate\Database\Eloquent\Collection<App\Models\Attempt>`): Daftar pengerjaan mahasiswa beserta eager loading `user` dan `answers.question`.
    - Relasi: `$attempt->user` (`App\Models\User`), `$attempt->answers` (`Collection<App\Models\Answer>`).
    - Properti Attempt: `id`, `test_id`, `user_id`, `started_at`, `finished_at`, `nilai` (bernilai `null` jika ujian esai belum dinilai).

#### `POST|PUT /dosen/ujian/{test}/nilai/{attempt}` *(Baru)*
- **Route Name**: `dosen.nilai.update` (Alias URL: `/dosen/tes/{test}/nilai/{attempt}`)
- **Controller**: `DosenController@updateNilai`
- **Kegunaan**: Dosen menginput atau memperbarui nilai ujian essai mahasiswa.
- **Request Form / JSON**:
  - `nilai`: `required|numeric|min:0|max:100`
- **Response**:
  - **Jika JSON / AJAX**:
    ```json
    {
      "status": "success",
      "message": "Nilai berhasil disimpan.",
      "attempt_id": 1,
      "nilai": 88.5
    }
    ```
  - **Jika Form Standar**: Redirect `back()->with('ok', 'Nilai mahasiswa berhasil diperbarui.')`.

#### `GET /dosen/ujian/{test}/live-monitoring` *(Baru)*
- **Route Name**: `dosen.live-monitoring` (Alias URL: `/dosen/tes/{test}/live-monitoring`)
- **Controller**: `DosenController@liveMonitoring`
- **Kegunaan**: Polling AJAX near real-time oleh frontend dosen untuk memantau ketikan jawaban essai mahasiswa yang sedang berlangsung.
- **Response JSON**:
  ```json
  {
    "status": "success",
    "test": {
      "id": 1,
      "judul": "Ujian Akhir Semester Essai",
      "tipe_ujian": "essai"
    },
    "server_time": "2026-10-31T00:45:00.000000Z",
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
        "updated_at": "2026-10-31T00:44:50.000000Z",
        "answers": [
          {
            "id": 101,
            "question_id": 3,
            "pertanyaan": "Jelaskan perbedaan synchronous dan asynchronous programming!",
            "tipe": "essai",
            "jawaban_essai": "Synchronous mengeksekusi proses secara sekuensial, sedangkan...",
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
  - `$aktif` (`Illuminate\Support\Collection<App\Models\Test>`): Ujian aktif dan belum diselesaikan mahasiswa.
  - `$riwayat` (`Illuminate\Support\Collection<App\Models\Test>`): Ujian yang sudah diselesaikan atau sudah lewat jadwal.
  - `$attempts` (`Illuminate\Support\Collection<int, App\Models\Attempt>`): Map attempt mahasiswa keyed by `test_id`.

#### `GET /mahasiswa/tes/{test}/mulai`
- **Route Name**: `mhs.mulai`
- **Controller**: `MahasiswaController@mulai`
- **Target Blade**: `resources/views/mahasiswa/ujian.blade.php`
- **Data ke View**:
  - `$test` (`App\Models\Test`): Ujian yang sedang dikerjakan. Memiliki atribut `$test->tipe_ujian` (`pilihan_ganda`|`essai`).
  - `$questions` (`Illuminate\Database\Eloquent\Collection<App\Models\Question>`): Daftar butir soal.
  - `$sisaDetik` (`int`): Sisa durasi ujian dalam detik.

#### `POST /mahasiswa/ujian/{test}/autosave` *(Baru)*
- **Route Name**: `mhs.autosave` (Alias URL: `/mahasiswa/tes/{test}/autosave`)
- **Controller**: `MahasiswaController@autosave`
- **Kegunaan**: AJAX auto-save saat mahasiswa sedang mengetik jawaban essai (gunakan debounce misal 1-2 detik pada event `input`/`keyup`).
- **Request Headers**:
  - `Content-Type: application/json`
  - `X-CSRF-TOKEN`: nilai dari `<meta name="csrf-token" content="{{ csrf_token() }}">`
- **Request Payload JSON**:
  - `question_id`: `required|exists:questions,id`
  - `jawaban_essai`: `nullable|string|max:65535`
- **Response JSON**:
  - **Success (200)**:
    ```json
    {
      "status": "saved",
      "updated_at": "2026-10-31T00:44:12.000000Z"
    }
    ```
  - **Error (403 / 404)**:
    ```json
    {
      "status": "error",
      "message": "Waktu pengerjaan ujian telah berakhir."
    }
    ```

#### `POST /mahasiswa/tes/{test}/kirim`
- **Route Name**: `mhs.kirim` (Alias URL: `/mahasiswa/ujian/{test}/kirim`)
- **Controller**: `MahasiswaController@kirim`
- **Logika Penilaian & Penyimpanan**:
  - Menggunakan `Answer::updateOrCreate()` agar sinkron dengan auto-save dan tidak menimbulkan record duplikat.
  - **Jika `test.tipe_ujian == 'pilihan_ganda'`**:
    - Nilai dihitung otomatis dari persentase jawaban benar.
    - Nilai disimpan ke `attempts.nilai`.
  - **Jika `test.tipe_ujian == 'essai'`**:
    - Nilai pada `attempts.nilai` dibiarkan **`null`** (Status: **Menunggu Penilaian**).
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
  - `$attempt` (`App\Models\Attempt` eager load `answers.question`): Data pengerjaan user.
    - `$attempt->nilai`: Jika `test.tipe_ujian == 'essai'` dan belum dinilai dosen, nilai ini bernilai `null` (Frontend dapat menampilkan badge: `Menunggu Penilaian Dosen`).
  - `$durasiDetik` (`int`): Total waktu pengerjaan dalam detik.

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
