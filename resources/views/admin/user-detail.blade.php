@extends('layouts.app')
@section('title','Detail '.ucfirst($role))
@section('content')
<div class="container">
  @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  <div class="row">
    <div class="page-heading"><a href="{{ route('admin.users', $role) }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><h2>Detail {{ ucfirst($role) }}</h2></div>
    <div style="display:flex;gap:8px">
      <a href="{{ route('admin.users.edit', [$role, $user]) }}" class="btn btn-blue">Edit</a>
    </div>
  </div><hr>
  <div class="panel pgrid">
    <div><b>{{ $role === 'dosen' ? 'NIP' : 'NIM' }}</b>{{ $user->nomor_induk }}</div>
    <div><b>Nama</b>{{ $user->name }}</div>
    <div><b>Email</b>{{ $user->email }}</div>
    <div><b>No. HP</b>{{ $user->no_hp ?: '-' }}</div>
    <div><b>Fakultas</b>{{ $user->fakultas }}</div>
    <div><b>Program Studi</b>{{ $user->prodi }}</div>
    <div><b>Tempat, Tanggal Lahir</b>{{ $user->ttl ?: '-' }}</div>
    <div><b>Status</b>{{ ucfirst($user->status) }}</div>
  </div>
</div>
@endsection
