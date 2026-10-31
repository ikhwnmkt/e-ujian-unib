@extends('layouts.app')
@section('title','Kelola Soal')
@section('content')
<div class="container">
  @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  <div class="row">
    <div class="page-heading"><a href="{{ route('dosen.index') }}" class="back-button" aria-label="Kembali" title="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 7-7m-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a><h2>Soal: {{ $test->judul }}</h2></div>
    <button type="button" class="plus" id="toggle-soal" aria-label="Tambah soal" title="Tambah soal"><span aria-hidden="true"></span></button>
  </div><hr>
  <div class="panel" id="form-soal" @if(!$errors->any()) hidden @endif>
    <h3 style="margin-top:0">Tambah Soal</h3>
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('dosen.soal.store',$test) }}" enctype="multipart/form-data">@csrf
      <label>Tipe Soal</label>
      <select name="tipe" id="tipe-soal">
        <option value="pilihan_ganda" @selected(old('tipe','pilihan_ganda')=='pilihan_ganda')>Pilihan Ganda</option>
        <option value="essai" @selected(old('tipe')=='essai')>Esai</option>
      </select>
      <label>Pertanyaan</label><textarea name="pertanyaan" rows="3" required>{{ old('pertanyaan') }}</textarea>
      <div id="pilihan-ganda">
        @foreach(['a','b','c','d'] as $o)<label>Opsi {{ strtoupper($o) }}</label><input name="opsi_{{ $o }}" value="{{ old('opsi_'.$o) }}">@endforeach
        <label>Jawaban Benar</label>
        <select name="jawaban_benar">@foreach(['a','b','c','d'] as $o)<option value="{{ $o }}" @selected(old('jawaban_benar')==$o)>{{ strtoupper($o) }}</option>@endforeach</select>
      </div>
      <label>Media (opsional)</label>
      <select name="media_type" id="media-type">
        <option value="">Tanpa media</option>
        <option value="gambar" @selected(old('media_type')=='gambar')>Gambar (unggah file)</option>
        <option value="link" @selected(old('media_type')=='link')>Link</option>
      </select>
      <div id="media-gambar" hidden><label>File Gambar</label><input type="file" name="media_file" accept="image/*"></div>
      <div id="media-link" hidden><label>URL Link</label><input type="url" name="media_url" value="{{ old('media_url') }}" placeholder="https://..."></div>
      <button class="btn btn-blue" style="margin-top:16px">Tambah Soal</button>
    </form>
  </div>
  <h3>Daftar Soal ({{ $test->questions->count() }})</h3>
  @foreach($test->questions as $i => $q)
  <div class="card" style="margin-bottom:12px"><div class="body">
    <b>{{ $i+1 }}. [{{ $q->tipe === 'essai' ? 'Esai' : 'Pilihan Ganda' }}] {{ $q->pertanyaan }}</b>
    @if($q->media_type === 'gambar' && $q->media_url)<img src="{{ $q->media_url }}" alt="Media soal {{ $i+1 }}" style="max-width:100%;max-height:300px">@elseif($q->media_type === 'link' && $q->media_url)<a href="{{ $q->media_url }}" target="_blank" rel="noopener noreferrer">{{ $q->media_url }}</a>@endif
    @if($q->tipe === 'pilihan_ganda')
      @foreach(['a','b','c','d'] as $o)<div @class(['good'=>$q->jawaban_benar==$o])>{{ strtoupper($o) }}. {{ $q['opsi_'.$o] }}</div>@endforeach
    @endif
  </div>
  <div class="foot"><span></span><form method="POST" action="{{ route('dosen.soal.destroy',[$test,$q]) }}" onsubmit="return confirm('Hapus soal?')">@csrf @method('DELETE')<button class="btn btn-red">Hapus</button></form></div></div>
  @endforeach
</div>
<script>
const tipeSoal = document.getElementById('tipe-soal');
const pilihanGanda = document.getElementById('pilihan-ganda');
const formSoal = document.getElementById('form-soal');
const mediaType = document.getElementById('media-type');
const mediaGambar = document.getElementById('media-gambar');
const mediaLink = document.getElementById('media-link');
document.getElementById('toggle-soal').addEventListener('click', () => {
  formSoal.hidden = !formSoal.hidden;
});
function togglePilihanGanda() {
  const enabled = tipeSoal.value === 'pilihan_ganda';
  pilihanGanda.hidden = !enabled;
  pilihanGanda.querySelectorAll('input, select').forEach(field => field.required = enabled);
}
tipeSoal.addEventListener('change', togglePilihanGanda);
togglePilihanGanda();
function toggleMedia() {
  mediaGambar.hidden = mediaType.value !== 'gambar';
  mediaLink.hidden = mediaType.value !== 'link';
  mediaGambar.querySelector('input').required = mediaType.value === 'gambar';
  mediaLink.querySelector('input').required = mediaType.value === 'link';
}
mediaType.addEventListener('change', toggleMedia);
toggleMedia();
</script>
@endsection
