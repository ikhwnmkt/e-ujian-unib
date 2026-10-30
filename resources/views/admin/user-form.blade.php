@extends('layouts.app')
@section('title', ($user->exists ? 'Edit ' : 'Tambah ') . ucfirst($role))
@section('content')
<div class="container">
  <div class="panel">
    <h2>{{ $user->exists ? 'Edit Data ' : 'Tambah Data ' }}{{ ucfirst($role) }}</h2><hr>
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', [$role, $user]) : route('admin.users.store', $role) }}">
      @csrf
      @if($user->exists) @method('PUT') @endif
      <label>Nama</label><input name="name" value="{{ old('name', $user->name) }}" required>
      <label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required>
      <label>{{ $role === 'dosen' ? 'NIP' : 'NIM' }}</label><input name="nomor_induk" value="{{ old('nomor_induk', $user->nomor_induk) }}" required>
      <label>No. HP</label><input name="no_hp" value="{{ old('no_hp', $user->no_hp) }}">
      <label>Fakultas</label><input name="fakultas" value="{{ old('fakultas', $user->fakultas ?? 'Teknik') }}" required>
      <label>Program Studi</label><input name="prodi" value="{{ old('prodi', $user->prodi ?? 'Pendidikan Teknik Informatika dan Komputer') }}" required>
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
        <a href="{{ route('admin.users', $role) }}" class="btn btn-gray">Batal</a>
      </div>
    </form>
  </div>
</div>
@endsection
