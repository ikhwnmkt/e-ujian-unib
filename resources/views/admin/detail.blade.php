@extends('layouts.app')
@section('title','Detail Tes')
@section('content')
<div class="container">
  <div class="page-heading"><a href="{{ route('admin.index') }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><h2>{{ $test->judul }}</h2></div><hr>
  <div class="panel pgrid">
    <div><b>Dosen Pengampu</b>{{ $test->dosen->name }}</div><div><b>Tanggal</b>{{ $test->tanggal_indo }}</div>
    <div><b>Waktu</b>{{ $test->waktu }}</div><div><b>Durasi</b>{{ $test->durasi }} Menit</div>
    <div><b>Sifat</b>{{ ucfirst($test->sifat) }}</div><div><b>Jumlah Soal</b>{{ $test->questions_count }}</div>
  </div>
  <h3>Peserta</h3>
  <table><tr><th>NIM</th><th>Nama</th><th>Nilai</th></tr>
  @forelse($test->attempts as $a)<tr><td>{{ $a->user->nomor_induk }}</td><td>{{ $a->user->name }}</td><td>{{ $a->nilai ?? '-' }}</td></tr>
  @empty <tr><td colspan="3">Belum ada peserta.</td></tr> @endforelse</table>
</div>
@endsection
