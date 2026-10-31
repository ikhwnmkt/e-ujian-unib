@extends('layouts.app')
@section('title','Verifikasi OTP - E-Ujian UNIB')
@section('content')
<div class="login-wrap"><div class="login-box">
  <div class="logo"><img src="{{ asset('images/logo-unib.png') }}" alt="Universitas Bengkulu" onerror="this.outerHTML='<b>UNIVERSITAS BENGKULU</b>'"></div>
  <h2 style="text-align:center">Verifikasi kode OTP</h2>
  <p style="text-align:center">Masukkan kode yang dikirim ke {{ $email }}. Kode berlaku selama 10 menit.</p>
  @if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('google.otp.verify') }}">@csrf
    <label for="code">Kode OTP</label>
    <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
    <button class="btn-pill primary" style="margin-top:18px">Verifikasi dan masuk</button>
  </form>
  <form method="POST" action="{{ route('google.otp.resend') }}">@csrf
    <button class="btn-pill">Kirim ulang kode</button>
  </form>
</div></div>
@endsection
