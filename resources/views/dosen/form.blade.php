@extends('layouts.app')
@section('title','Form Tes')
@section('content')
<div class="container"><div class="panel">
  <div class="page-heading"><a href="{{ route('dosen.index') }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><h2>{{ $test->exists ? 'Edit Tes' : 'Tambah Tes' }}</h2></div><hr>
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ $test->exists ? route('dosen.update',$test) : route('dosen.store') }}">
    @csrf @if($test->exists) @method('PUT') @endif
    <label>Judul Tes</label><input name="judul" value="{{ old('judul',$test->judul) }}" required>
    <label>Tanggal</label><input type="date" name="tanggal" value="{{ old('tanggal',$test->tanggal?->format('Y-m-d')) }}" required>
    <label>Jam Mulai</label><input type="time" name="jam_mulai" value="{{ old('jam_mulai',substr($test->jam_mulai ?? '',0,5)) }}" required>
    <label>Jam Selesai</label><input type="time" name="jam_selesai" value="{{ old('jam_selesai',substr($test->jam_selesai ?? '',0,5)) }}" required>
    <label>Durasi (menit)</label><input type="number" name="durasi" value="{{ old('durasi',$test->durasi) }}" required>
    <label>Sifat</label>
    <select name="sifat">
      <option value="fleksibel" @selected(old('sifat',$test->sifat)=='fleksibel')>Fleksibel (durasi penuh sejak mulai)</option>
      <option value="ketat" @selected(old('sifat',$test->sifat)=='ketat')>Ketat (berhenti di jam selesai)</option>
    </select>
    <div style="margin-top:20px;display:flex;gap:8px"><button class="btn btn-blue">Simpan</button><a href="{{ route('dosen.index') }}" class="btn btn-blue">Batal</a></div>
  </form>
</div></div>
@endsection
