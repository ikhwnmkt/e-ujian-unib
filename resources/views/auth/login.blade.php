@extends('layouts.app')
@section('title','Masuk - E-Ujian UNIB')
@section('content')
<div class="login-wrap"><div class="login-box">
  <div class="logo"><img src="{{ asset('images/logo-unib.png') }}" alt="Universitas Bengkulu" onerror="this.outerHTML='<b>UNIVERSITAS BENGKULU</b>'"></div>
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('login') }}">@csrf
    <label>Email</label><input type="email" name="email" value="{{ old('email') }}" required>
    <label>Password</label><input type="password" name="password" required>
    <button class="btn-pill primary" style="margin-top:18px">Masuk</button>
  </form>
  <div class="or">atau</div>
  <a href="{{ route('google') }}" class="btn-pill">G&nbsp; Log in dengan Google</a>
  <a href="{{ route('register') }}" class="btn-pill primary">Buat Akun</a>
</div></div>
@endsection
