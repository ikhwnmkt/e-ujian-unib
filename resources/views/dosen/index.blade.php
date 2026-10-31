@extends('layouts.app')
@section('title','Dashboard Dosen')
@section('content')
<div class="container">
  @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  <div class="row"><h2>DAFTAR TES</h2><a href="{{ route('dosen.create') }}" class="plus" title="Tambah tes" aria-label="Tambah tes"><span aria-hidden="true"></span></a></div><hr>
  <h3>Sedang Berlangsung</h3>
  <div class="grid">
    @forelse($aktif as $t)
    <div class="card"><div class="body"><b>{{ $t->judul }}</b>
      Mulai : {{ $t->tanggal_indo }}<br>Waktu : {{ $t->waktu }}<br>Durasi : {{ $t->durasi }} Menit<br>Sifat : {{ ucfirst($t->sifat) }}</div>      
      <div class="foot actions">
        <a href="{{ route('dosen.live-monitoring', $t) }}" class="btn btn-green">Live Monitoring</a>
        <a href="{{ route('dosen.soal', $t) }}" class="btn btn-blue">Tambah Soal</a>
        <a href="{{ route('dosen.edit', $t) }}" class="btn btn-blue">Edit</a>
        <form method="POST" action="{{ route('dosen.destroy', $t) }}" onsubmit="return confirm('Hapus tes ini?')">
          @csrf
          @method('DELETE')
          <button class="btn btn-red">Hapus</button>
        </form>
      </div>
    </div>
    @empty <p>Belum ada tes.</p> @endforelse
  </div>
  
  <h3>Riwayat Nilai</h3>
  <div class="grid">
    @forelse($riwayat as $t)
    <div class="card"><div class="body"><b>{{ $t->judul }}</b>
      Mulai : {{ $t->tanggal_indo }}<br>Waktu : {{ $t->waktu }}<br>Durasi : {{ $t->durasi }} Menit<br>Sifat : {{ ucfirst($t->sifat) }}</div>
      <div class="foot">
        <a href="{{ route('dosen.nilai', $t) }}" class="btn btn-blue">Lihat Nilai</a>
        <a href="{{ route('dosen.live-monitoring', $t) }}" class="btn btn-gray">Monitoring</a>
      </div></div>
    @empty <p>Belum ada riwayat.</p> @endforelse
  </div>
</div>
@endsection
