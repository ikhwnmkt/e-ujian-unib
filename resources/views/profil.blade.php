@extends('layouts.app')
@section('title','Profil')
@section('content')
<div class="container"><div class="panel">
  @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <div class="profile-head">
    <div class="avatar" style="{{ $user->avatar ? 'background-image:url('.asset('storage/'.$user->avatar).')' : '' }}"></div>
    <b>{{ $user->name }}</b><div style="color:#777;font-size:13px">{{ $user->subtitle }}</div>
  </div>
  <div class="page-heading" style="margin-top:20px"><a href="{{ route('dashboard') }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><h2>Profil</h2></div><hr>
  <form method="POST" action="{{ route('profil.update') }}" enctype="multipart/form-data">@csrf
    <div class="pgrid">
      <div><label>Nama</label><input name="name" value="{{ old('name',$user->name) }}" required>
        <label>Tempat Lahir</label><input name="tempat_lahir" value="{{ old('tempat_lahir',$user->tempat_lahir) }}">
        <label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir',$user->tanggal_lahir?->format('Y-m-d')) }}">
        <label>Nomor Handphone</label><input name="no_hp" value="{{ old('no_hp',$user->no_hp) }}">
        <label>Foto Profil</label><input type="file" name="avatar" accept="image/*"></div>
      <div><b>Email</b>{{ $user->email }}<br><br>
        <b>Status</b>{{ $user->role=='dosen' ? 'Dosen' : 'Mahasiswa' }} {{ $user->status }}<br><br>
        <b>Fakultas</b>{{ $user->fakultas }}<br><br>
        <b>Prodi</b>{{ $user->prodi }}<br><br>
        <b>Tempat, Tanggal Lahir</b>{{ $user->ttl }}</div>
    </div>
    <button class="btn btn-blue" style="margin-top:10px">Simpan Perubahan</button>
    <a href="{{ route('dashboard') }}" class="btn btn-blue">Kembali</a>
  </form>
</div></div>
@endsection
