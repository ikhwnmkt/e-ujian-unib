@extends('layouts.app')
@section('title','Nilai Tes')
@section('content')
<div class="container">
  <div class="page-heading"><a href="{{ route('dosen.index') }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><h2>Nilai: {{ $test->judul }}</h2></div><hr>
  <table><tr><th>NIM</th><th>Nama</th><th>Mulai</th><th>Selesai</th><th>Nilai</th></tr>
  @forelse($attempts as $a)
    <tr><td>{{ $a->user->nomor_induk }}</td><td>{{ $a->user->name }}</td><td>{{ $a->started_at->format('H:i') }}</td>
    <td>{{ $a->finished_at?->format('H:i') ?? '-' }}</td><td><b>{{ $a->nilai ?? '-' }}</b></td></tr>
  @empty <tr><td colspan="5">Belum ada peserta.</td></tr> @endforelse
  </table>
</div>
@endsection
