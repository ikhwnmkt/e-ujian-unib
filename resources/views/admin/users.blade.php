@extends('layouts.app')
@section('title','Data '.ucfirst($role))
@section('content')
<div class="container">
  <div class="tabs">
    <a href="{{ route('admin.index') }}">Daftar Tes</a>
    <a href="{{ route('admin.users','mahasiswa') }}" @class(['active'=>$role=='mahasiswa'])>Data Mahasiswa</a>
    <a href="{{ route('admin.users','dosen') }}" @class(['active'=>$role=='dosen'])>Data Dosen</a>
  </div>
  @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <div class="row" style="margin-bottom:14px">
    <h2>Data {{ ucfirst($role) }}</h2>
    <a href="{{ route('admin.users.create', $role) }}" class="btn btn-blue">Tambah {{ $role }}</a>
  </div>
  <table>
    <tr><th>{{ $role=='dosen' ? 'NIP' : 'NIM' }}</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Prodi</th><th>Status</th><th>Aksi</th></tr>
    @forelse($users as $u)
      <tr>
        <td>{{ $u->nomor_induk }}</td><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->no_hp ?: '-' }}</td>
        <td>{{ $u->prodi }}</td><td>{{ ucfirst($u->status) }}</td>
        <td style="white-space:nowrap">
          <a href="{{ route('admin.users.show', [$role, $u]) }}" class="btn btn-blue">Detail</a>
          <a href="{{ route('admin.users.edit', [$role, $u]) }}" class="btn btn-gray">Edit</a>
        </td>
      </tr>
    @empty <tr><td colspan="7">Belum ada data.</td></tr> @endforelse
  </table>
</div>
@endsection
