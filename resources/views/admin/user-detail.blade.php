@extends('layouts.app')
@section('title','Detail '.ucfirst($role))
@section('content')
<div class="container">
  @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  <div class="row">
    <h2>Detail {{ ucfirst($role) }}</h2>
    <div style="display:flex;gap:8px">
      <a href="{{ route('admin.users.edit', [$role, $user]) }}" class="btn btn-blue">Edit</a>
      <a href="{{ route('admin.users', $role) }}" class="btn btn-gray">Kembali</a>
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
