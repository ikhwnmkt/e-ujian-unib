@extends('layouts.app')
@section('title','Nilai Tes')
@section('content')
<div class="container">
  <div class="row"><h2>Nilai: {{ $test->judul }}</h2><a href="{{ route('dosen.index') }}" class="btn btn-gray">Kembali</a></div><hr>
  <table><tr><th>NIM</th><th>Nama</th><th>Mulai</th><th>Selesai</th><th>Nilai</th></tr>
  @forelse($attempts as $a)
    <tr><td>{{ $a->user->nomor_induk }}</td><td>{{ $a->user->name }}</td><td>{{ $a->started_at->format('H:i') }}</td>
    <td>{{ $a->finished_at?->format('H:i') ?? '-' }}</td><td><b>{{ $a->nilai ?? '-' }}</b></td></tr>
  @empty <tr><td colspan="5">Belum ada peserta.</td></tr> @endforelse
  </table>
</div>
@endsection
