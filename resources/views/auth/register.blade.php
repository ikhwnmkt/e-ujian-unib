@extends('layouts.app')
@section('title','Buat Akun - E-Ujian UNIB')
@section('content')
<div class="login-wrap"><div class="login-box">
  <div class="logo"><img src="{{ asset('images/logo-unib.png') }}" alt="" onerror="this.outerHTML='<b>UNIVERSITAS BENGKULU</b>'"></div>
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('register') }}">@csrf
    <label>Nama Lengkap</label><input name="name" value="{{ old('name') }}" required>
    <label>Daftar sebagai</label>
    <select name="role"><option value="mahasiswa">Mahasiswa</option><option value="dosen">Dosen</option></select>
    <label>NIM / NIP</label><input name="nomor_induk" value="{{ old('nomor_induk') }}" required>
    <label>Email</label><input type="email" name="email" value="{{ old('email') }}" required>
    <label>Password</label><input type="password" name="password" required>
    <label>Konfirmasi Password</label><input type="password" name="password_confirmation" required>
    <button class="btn-pill primary" style="margin-top:18px">Buat Akun</button>
  </form>
  <a href="{{ route('login') }}" class="btn-pill">Sudah punya akun? Masuk</a>
</div></div>
@endsection
