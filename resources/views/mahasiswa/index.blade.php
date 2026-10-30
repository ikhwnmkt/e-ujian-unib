@extends('layouts.app')
@section('title','Dashboard Mahasiswa')
@section('content')
<div class="container">
  @if(session('err'))<div class="alert err">{{ session('err') }}</div>@endif
  <h2>DAFTAR SESI TES</h2><hr>
  <h3>Sedang Berlangsung</h3>
  <div class="grid">
    @forelse($aktif as $t)
    <div class="card"><div class="body"><b>{{ $t->judul }}</b>
      Mulai : {{ $t->tanggal_indo }}<br>Waktu : {{ $t->waktu }}<br>Durasi : {{ $t->durasi }} Menit<br>Sifat : {{ ucfirst($t->sifat) }}</div>
      <div class="foot split"><span>Nilai</span>
        <a href="{{ route('mhs.mulai',$t) }}" @class(['btn','btn-green','off'=>!$t->berlangsung() && !$attempts->has($t->id)])>{{ $attempts->has($t->id) ? 'Lanjut' : 'Mulai' }}</a></div></div>
    @empty <p>Tidak ada sesi tes.</p> @endforelse
  </div>
  <h3>Riwayat Tes</h3>
  <div class="grid">
    @forelse($riwayat as $t)
    @php $a = $attempts->get($t->id); @endphp
    <div class="card"><div class="body"><b>{{ $t->judul }}</b>
      Mulai : {{ $t->tanggal_indo }}<br>Waktu : {{ $t->waktu }}<br>Durasi : {{ $t->durasi }} Menit<br>Sifat : {{ ucfirst($t->sifat) }}</div>
      <div class="foot split"><span>Nilai {{ $a?->nilai !== null ? ': '.(float)$a->nilai : '' }}</span>
        @if($a?->finished_at)<a href="{{ route('mhs.hasil',$t) }}" class="btn btn-blue">Hasil</a>
        @else<span class="btn btn-red off">Kosong</span>@endif</div></div>
    @empty <p>Belum ada riwayat.</p> @endforelse
  </div>
</div>
@endsection
