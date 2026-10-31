@extends('layouts.app')
@section('title', ($user->exists ? 'Edit ' : 'Tambah ') . ucfirst($role))
@section('content')
<div class="container">
  <div class="panel">
    <div class="page-heading">
      <a href="{{ route('admin.users', $role) }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
      <h2>{{ $user->exists ? 'Edit Data ' : 'Tambah Data ' }}{{ ucfirst($role) }}</h2>
    </div><hr>
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', [$role, $user]) : route('admin.users.store', $role) }}">
      @csrf
      @if($user->exists) @method('PUT') @endif
      <label>Nama</label><input name="name" value="{{ old('name', $user->name) }}" required>
      <label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required>
      <label>{{ $role === 'dosen' ? 'NIP' : 'NIM' }}</label><input name="nomor_induk" value="{{ old('nomor_induk', $user->nomor_induk) }}" required>
      <label>No. HP</label><input name="no_hp" value="{{ old('no_hp', $user->no_hp) }}">
      <label>Fakultas</label>
      <select name="fakultas" id="fakultas" required>
        <option value="">Pilih fakultas</option>
        @foreach(['Teknik', 'Hukum', 'Ilmu Keolahragaan', 'Kedokteran', 'Matematika dan Ilmu Pengetahuan', 'Ilmu Pendidikan dan Psikologi'] as $fakultas)
          <option value="{{ $fakultas }}" @selected(old('fakultas', $user->fakultas) === $fakultas)>{{ $fakultas }}</option>
        @endforeach
      </select>
      <label>Program Studi</label>
      <select id="prodi-select" required>
        <option value="">Pilih program studi</option>
        <option value="__custom">Isi program studi lainnya...</option>
      </select>
      <input id="prodi-custom" type="text" placeholder="Masukkan program studi" style="display:none;margin-top:8px">
      <input type="hidden" name="prodi" id="prodi" value="{{ old('prodi', $user->prodi) }}">
      <label>Tempat Lahir</label><input name="tempat_lahir" value="{{ old('tempat_lahir', $user->tempat_lahir) }}">
      <label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $user->tanggal_lahir?->format('Y-m-d')) }}">
      <label>Status</label>
      <select name="status" required>
        <option value="aktif" @selected(old('status', $user->status ?? 'aktif') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(old('status', $user->status ?? 'aktif') === 'nonaktif')>Nonaktif</option>
      </select>
      <label>{{ $user->exists ? 'Password baru (kosongkan jika tidak diubah)' : 'Password' }}</label>
      <input type="password" name="password" @required(!$user->exists) autocomplete="new-password">
      <label>Konfirmasi Password</label><input type="password" name="password_confirmation" @required(!$user->exists) autocomplete="new-password">
      <div style="margin-top:20px;display:flex;gap:8px">
        <button class="btn btn-blue">Simpan</button>
        <a href="{{ route('admin.users', $role) }}" class="btn btn-blue">Batal</a>
      </div>
    </form>
  </div>
</div>
<script>
const prodiByFaculty = @json($prodiByFaculty);
const facultySelect = document.getElementById('fakultas');
const prodiSelect = document.getElementById('prodi-select');
const prodiInput = document.getElementById('prodi');
const customProdi = document.getElementById('prodi-custom');
const initialProdi = @json(old('prodi', $user->prodi));
function updateProdiOptions(initial = false) {
  const options = prodiByFaculty[facultySelect.value] || [];
  prodiSelect.replaceChildren(new Option('Pilih program studi', ''));
  options.forEach(value => prodiSelect.add(new Option(value, value)));
  prodiSelect.add(new Option('Isi program studi lainnya...', '__custom'));
  if (initial && options.includes(initialProdi)) {
    prodiSelect.value = initialProdi;
    customProdi.style.display = 'none';
    customProdi.required = false;
    prodiInput.value = initialProdi;
  } else if (initialProdi && initial && !options.includes(initialProdi)) {
    prodiSelect.value = '__custom';
    customProdi.style.display = 'block';
    customProdi.required = true;
    customProdi.value = initialProdi;
    prodiInput.value = customProdi.value;
  } else {
    prodiSelect.value = '';
    customProdi.style.display = 'none';
    customProdi.required = false;
    customProdi.value = '';
    prodiInput.value = '';
  }
}
prodiSelect.addEventListener('change', () => {
  const custom = prodiSelect.value === '__custom';
  customProdi.style.display = custom ? 'block' : 'none';
  customProdi.required = custom;
  prodiInput.value = custom ? customProdi.value : prodiSelect.value;
  if (custom) customProdi.focus();
});
customProdi.addEventListener('input', () => prodiInput.value = customProdi.value);
facultySelect.addEventListener('change', () => {
  customProdi.value = '';
  prodiInput.value = '';
  updateProdiOptions();
});
updateProdiOptions(true);
</script>
@endsection
