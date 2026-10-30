@extends('layouts.app')
@section('title','Dashboard Admin')
@section('content')
<div class="container">
  <div class="tabs">
    <a href="{{ route('admin.index') }}" class="active">Daftar Tes</a>
    <a href="{{ route('admin.users','mahasiswa') }}">Data Mahasiswa</a>
    <a href="{{ route('admin.users','dosen') }}">Data Dosen</a>
  </div>
  <div class="grid">
    @forelse($tests as $t)
    <div class="card"><div class="body"><b>{{ $t->judul }}</b>
      Mulai : {{ $t->tanggal_indo }}<br>Waktu : {{ $t->waktu }}<br>Durasi : {{ $t->durasi }} Menit<br>Sifat : {{ ucfirst($t->sifat) }}</div>
      <div class="foot" style="justify-content:flex-end"><a href="{{ route('admin.detail',$t) }}" class="btn btn-blue">Detail</a></div></div>
    @empty <p>Belum ada tes.</p> @endforelse
  </div>
</div>
@endsection
