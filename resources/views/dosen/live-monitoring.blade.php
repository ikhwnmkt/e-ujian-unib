@extends('layouts.app')
@section('title', 'Live Monitoring - ' . $test->judul)
@section('content')
<div class="container">
  <div class="row">
    <div>
      <h2>Live Monitoring: {{ $test->judul }}</h2>
      <div style="font-size:14px;color:#555">Materi: <b>{{ $test->materi ?: '-' }}</b> &middot; Tipe: <b>{{ $test->tipe_ujian_label }}</b></div>
    </div>
    <div style="display:flex;gap:10px">
      <button onclick="fetchData()" class="btn btn-blue" id="btn-refresh">Perbarui Data</button>
      <a href="{{ route('dosen.index') }}" class="btn btn-gray">Kembali</a>
    </div>
  </div>
  <hr>

  <div class="panel" style="padding:14px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <div>
      <span id="polling-badge" style="display:inline-block;padding:4px 12px;border-radius:14px;background:#e3f6df;color:#256c1a;font-weight:700;font-size:13px">
        🟢 Live Polling Aktif (Tiap 3 Detik)
      </span>
      <span style="font-size:13px;color:#666;margin-left:12px">Pembaruan Terakhir: <b id="last-sync">-</b></span>
    </div>
    <div style="font-size:14px">
      Peserta Terdeteksi: <b id="peserta-count">{{ $test->peserta_count }}</b> Mahasiswa
    </div>
  </div>

  <div id="attempts-container" style="display:flex;flex-direction:column;gap:18px">
    <div class="panel" style="text-align:center;padding:40px;color:#666">
      Sedang menghubungkan ke stream live monitoring...
    </div>
  </div>
</div>

<script>
  const endpoint = "{{ route('dosen.live-monitoring', $test) }}";
  const container = document.getElementById('attempts-container');
  const syncLabel = document.getElementById('last-sync');
  const countLabel = document.getElementById('peserta-count');
  let isFetching = false;

  async function fetchData() {
    if (isFetching) return;
    isFetching = true;

    try {
      const res = await fetch(endpoint, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      if (!res.ok) throw new Error('Gagal mengambil data monitoring.');
      const data = await res.json();
      renderData(data);
      syncLabel.textContent = new Date().toLocaleTimeString('id-ID');
    } catch (err) {
      console.error(err);
      syncLabel.textContent = 'Gagal sync (' + new Date().toLocaleTimeString('id-ID') + ')';
    } finally {
      isFetching = false;
    }
  }

  function renderData(data) {
    if (data.test && typeof data.test.peserta_count !== 'undefined') {
      countLabel.textContent = data.test.peserta_count;
    }

    if (!data.attempts || data.attempts.length === 0) {
      container.innerHTML = `
        <div class="panel" style="text-align:center;padding:40px;color:#777">
          Belum ada mahasiswa yang sedang membuka atau mengerjakan ujian ini.
        </div>
      `;
      return;
    }

    let html = '';
    data.attempts.forEach(item => {
      const u = item.user;
      const isFinished = item.is_finished;
      const statusBadge = isFinished 
        ? '<span style="background:#e3f6df;color:#256c1a;padding:3px 8px;border-radius:10px;font-size:12px;font-weight:700">Selesai Dikirim</span>'
        : '<span style="background:#eaf2fd;color:#1d65c1;padding:3px 8px;border-radius:10px;font-size:12px;font-weight:700">⚡ Sedang Mengerjakan</span>';
      
      const attemptBadge = item.attempt_count > 1 
        ? `<span style="background:#fff3cd;color:#856404;padding:3px 8px;border-radius:10px;font-size:12px;font-weight:700">Percobaan ke-${item.attempt_count}</span>` 
        : '';

      const lastActivityTime = item.updated_at ? new Date(item.updated_at).toLocaleTimeString('id-ID') : '-';

      html += `
        <div class="card" style="box-shadow:0 2px 8px rgba(0,0,0,.1)">
          <div class="body" style="background:#fafbfc;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
              <b style="font-size:16px;margin:0">${escapeHtml(u.name)}</b>
              <div style="font-size:13px;color:#666">NPM: <b>${escapeHtml(u.nomor_induk || '-')}</b></div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              ${attemptBadge}
              ${statusBadge}
              <span style="font-size:12px;color:#777">Aktivitas Terakhir: <b>${lastActivityTime}</b></span>
            </div>
          </div>
          <div class="body" style="padding:16px">
            <h4 style="margin:0 0 12px;font-size:14px;color:#333">Pantauan Jawaban Essai Real-Time:</h4>
      `;

      if (!item.answers || item.answers.length === 0) {
        html += `<div style="font-style:italic;color:#888;font-size:13px">Mahasiswa belum mulai mengetik jawaban pada butir soal mana pun.</div>`;
      } else {
        item.answers.forEach((ans, i) => {
          const answerText = ans.jawaban_essai && ans.jawaban_essai.trim() !== '' 
            ? escapeHtml(ans.jawaban_essai) 
            : '<span style="color:#aaa;font-style:italic">(Belum ada ketikan jawaban)</span>';
          
          const answerTime = ans.updated_at ? new Date(ans.updated_at).toLocaleTimeString('id-ID') : '-';

          html += `
            <div style="margin-bottom:14px;padding:12px;background:#f8f9fa;border-left:4px solid #3182e0;border-radius:4px">
              <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
                <b>Soal: ${escapeHtml(ans.pertanyaan || 'Soal')}</b>
                <small style="color:#777">Update: ${answerTime}</small>
              </div>
              <div style="white-space:pre-wrap;font-family:inherit;font-size:14px;background:#fff;padding:10px;border-radius:6px;border:1px solid #ddd;min-height:48px;line-height:1.6">${answerText}</div>
            </div>
          `;
        });
      }

      html += `
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // Load awal dan polling tiap 3 detik
  fetchData();
  setInterval(fetchData, 3000);
</script>
@endsection
