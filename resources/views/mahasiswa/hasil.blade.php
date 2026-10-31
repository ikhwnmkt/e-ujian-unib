@extends('layouts.app')
@section('title','Hasil Tes')
@section('content')
<div class="container">
  <div class="page-heading"><a href="{{ route('mhs.index') }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><h2>Hasil: {{ $test->judul }}</h2></div><hr>
  <div class="panel" style="text-align:center"><div>Nilai pilihan ganda</div><div style="font-size:48px;font-weight:700;color:#3182e0">@if($attempt->nilai !== null){{ (float)$attempt->nilai }}@else &mdash; @endif</div>
    @if($attempt->nilai === null)<div>Tidak ada soal pilihan ganda pada ujian ini.</div>@endif
  </div>
  <div class="panel" style="margin-top:14px">
    <b>Durasi pengerjaan:</b> {{ gmdate('H:i:s', $durasiDetik) }}
    @if($attempt->answers->contains(fn ($answer) => $answer->question->tipe === 'essai'))
      <div style="margin-top:8px">Jawaban esai ditampilkan untuk ditinjau. Nilai otomatis hanya menghitung soal pilihan ganda.</div>
    @endif
  </div>
  <h3>Pembahasan</h3>
  @foreach($attempt->answers as $i => $a)
  <div class="card" style="margin-bottom:12px"><div class="body {{ $a->benar ? 'good' : 'bad' }}">
    <b>{{ $i+1 }}. [{{ $a->question->tipe === 'essai' ? 'Esai' : 'Pilihan Ganda' }}] {{ $a->question->pertanyaan }}</b>
    @if($a->question->tipe === 'essai')
      <div>Jawaban Anda:</div><div style="white-space:pre-wrap">{{ $a->jawaban_essai ?: '-' }}</div>
    @else
      Jawaban Anda: {{ $a->jawaban ? strtoupper($a->jawaban) : '-' }} &middot; Kunci: {{ strtoupper($a->question->jawaban_benar) }}
    @endif
  </div></div>
  @endforeach
</div>
@endsection
