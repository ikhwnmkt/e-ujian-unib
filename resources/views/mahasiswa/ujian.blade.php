@extends('layouts.app')
@section('title',$test->judul)
@section('content')
<div class="timer">Sisa waktu: <span id="t">--:--:--</span></div>
<div class="container">
  <h2>{{ $test->judul }}</h2><hr>
  <form method="POST" action="{{ route('mhs.kirim',$test) }}" id="f">@csrf
    @if($questions->isEmpty())
      <div class="alert err">Belum ada soal untuk ujian ini.</div>
      <button class="btn btn-blue" onclick="return confirm('Kumpulkan jawaban sekarang?')">Kumpulkan</button>
    @else
      <div class="panel" style="margin-bottom:16px">
        <b>Nomor soal</b>
        <div class="question-nav" id="question-nav" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
          @foreach($questions as $i => $q)
            <button type="button" class="question-number" data-index="{{ $i }}" aria-label="Buka soal {{ $i+1 }}"
              style="width:38px;height:38px;border:1px solid #bbb;border-radius:5px;background:#fff;color:#111;cursor:pointer">{{ $i+1 }}</button>
          @endforeach
        </div>
        <div style="font-size:13px;margin-top:12px">
          <span style="display:inline-block;background:#3182e0;color:white;padding:3px 8px;border-radius:4px">Dijawab</span>
          <span style="display:inline-block;background:#f2c94c;color:#111;padding:3px 8px;border-radius:4px;margin-left:5px">Ragu-ragu</span>
          <span style="display:inline-block;border:1px solid #bbb;color:#111;padding:2px 8px;border-radius:4px;margin-left:5px">Belum dijawab</span>
        </div>
      </div>
      @foreach($questions as $i => $q)
        <section class="panel exam-question" data-index="{{ $i }}" style="margin-bottom:14px" @if($i !== 0) hidden @endif>
          <b>{{ $i+1 }}. {{ $q->pertanyaan }}</b>
          @if($q->media_type === 'gambar' && $q->media_url)
            <div style="margin:12px 0"><img src="{{ $q->media_url }}" alt="Media soal {{ $i+1 }}" style="display:block;max-width:100%;max-height:400px"></div>
          @elseif($q->media_type === 'link' && $q->media_url)
            <div style="margin:12px 0"><a href="{{ $q->media_url }}" target="_blank" rel="noopener noreferrer">{{ $q->media_url }}</a></div>
          @endif
          @php $saved = $savedAnswers[$q->id] ?? null; @endphp
          @if($q->tipe === 'essai')
            <label for="essay-{{ $q->id }}">Jawaban esai</label>
            <textarea id="essay-{{ $q->id }}" name="jawaban_essai[{{ $q->id }}]" rows="6" class="answer-input" data-qid="{{ $q->id }}">{{ $saved?->jawaban_essai }}</textarea>
          @else
            @foreach(['a','b','c','d'] as $o)
              <label class="opt">
                <input class="answer-input" type="radio" name="jawaban[{{ $q->id }}]" value="{{ $o }}" data-qid="{{ $q->id }}" {{ ($saved && $saved->pilihan === $o) ? 'checked' : '' }}>
                {{ strtoupper($o) }}. {{ $q['opsi_'.$o] }}
              </label>
            @endforeach
          @endif
          <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-gray prev-question">Sebelumnya</button>
            <button type="button" class="btn btn-gray mark-doubt">Tandai ragu-ragu</button>
            <button type="button" class="btn btn-blue next-question">Selanjutnya</button>
          </div>
        </section>
      @endforeach
      <button class="btn btn-blue" onclick="return confirm('Kumpulkan jawaban sekarang?')">Kumpulkan</button>
    @endif
  </form>
</div>
<script>
let s = {{ (int)$sisaDetik }};
const el = document.getElementById('t');
const form = document.getElementById('f');
(function tick(){
  const h=String(Math.floor(s/3600)).padStart(2,'0'), m=String(Math.floor(s%3600/60)).padStart(2,'0'), d=String(s%60).padStart(2,'0');
  el.textContent = h+':'+m+':'+d;
  if(s<=0){ form.submit(); return; }
  s--; setTimeout(tick,1000);
})();

const questions = [...document.querySelectorAll('.exam-question')];
const numbers = [...document.querySelectorAll('.question-number')];
let current = 0;
const doubtful = new Set();
function answered(index) {
  return [...questions[index].querySelectorAll('input[type="radio"]')].some(input => input.checked)
    || [...questions[index].querySelectorAll('textarea')].some(input => input.value.trim() !== '');
}
function render() {
  questions.forEach((question, index) => question.hidden = index !== current);
  numbers.forEach((button, index) => {
    const isDoubtful = doubtful.has(index);
    button.style.backgroundColor = isDoubtful ? '#f2c94c' : answered(index) ? '#3182e0' : '#fff';
    button.style.color = isDoubtful || !answered(index) ? '#111' : '#fff';
    button.style.borderColor = index === current ? '#1d2b6b' : isDoubtful ? '#d9ad22' : '#bbb';
    button.style.boxShadow = index === current ? '0 0 0 2px rgba(29,43,107,.2)' : 'none';
  });
  const prev = questions[current].querySelector('.prev-question');
  const next = questions[current].querySelector('.next-question');
  prev.disabled = current === 0;
  next.disabled = current === questions.length - 1;
}
numbers.forEach(button => button.addEventListener('click', () => {
  current = Number(button.dataset.index);
  render();
}));
questions.forEach((question, index) => {
  question.querySelectorAll('.answer-input').forEach(input => input.addEventListener('input', render));
  question.querySelector('.prev-question').addEventListener('click', () => { if (index > 0) { current = index - 1; render(); } });
  question.querySelector('.next-question').addEventListener('click', () => { if (index < questions.length - 1) { current = index + 1; render(); } });
  question.querySelector('.mark-doubt').addEventListener('click', event => {
    if (doubtful.has(index)) {
      doubtful.delete(index);
      event.currentTarget.textContent = 'Tandai ragu-ragu';
    } else {
      doubtful.add(index);
      event.currentTarget.textContent = 'Hapus tanda ragu';
    }
    render();
  });
});
render();

// Autosave Engine Real-Time
const autosaveUrl = "{{ route('mhs.autosave', $test) }}";
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}";
const autosaveTimers = {};

function saveAnswer(qid, payload) {
  fetch(autosaveUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(Object.assign({ question_id: qid }, payload))
  }).then(async res => {
    if (res.status === 409) {
      const data = await res.json();
      alert(data.message || 'Waktu ujian telah habis.');
      window.location.href = data.redirect || "{{ route('mhs.hasil', $test) }}";
    }
  }).catch(err => console.error('Autosave error:', err));
}

document.querySelectorAll('input[type="radio"].answer-input').forEach(radio => {
  radio.addEventListener('change', () => {
    saveAnswer(radio.dataset.qid, { pilihan: radio.value });
  });
});

document.querySelectorAll('textarea.answer-input').forEach(textarea => {
  textarea.addEventListener('input', () => {
    const qid = textarea.dataset.qid;
    clearTimeout(autosaveTimers[qid]);
    autosaveTimers[qid] = setTimeout(() => {
      saveAnswer(qid, { jawaban_essai: textarea.value });
    }, 800);
  });
});
</script>
@endsection
