<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/pretest.php — Ujian Evaluasi (Kids UI Version)
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/session.php';

requireSiswa(); 
$pdo = getDB();

$kelas = (int)($_GET['kelas'] ?? $_SESSION['siswa_kelas']);
$topik_id = (int)($_GET['topik'] ?? 0);

// === PENTING: UBAH INI JADI 'posttest' JIKA MEMBUAT FILE posttest.php ===
$tipe_tes = 'posttest'; 

// Cek apakah sudah mengerjakan
$tabel = ($tipe_tes === 'pretest') ? 'hasil_pretest' : 'hasil_posttest';
$stmtCek = $pdo->prepare("SELECT skor FROM $tabel WHERE siswa_id = ? AND kelas = ?");
$stmtCek->execute([$_SESSION['siswa_id'], $kelas]);
$sudahTes = $stmtCek->fetch();

$judul_tes = ($tipe_tes === 'pretest') ? 'Pre-Test' : 'Post-Test';

// JIKA SUDAH PERNAH MENGERJAKAN (Tampilan Mencegah Ujian Ulang)
if ($sudahTes) {
    die('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sudah Ujian - Saintara</title><link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><style>body{background:#F0F8FF;font-family:"Nunito",sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;box-sizing:border-box;}.card{background:white;border-radius:24px;padding:40px 20px;text-align:center;box-shadow:0 10px 30px rgba(77,168,218,0.15);border:3px solid #EBF6FC;max-width:400px;width:100%;}.icon-done{font-size:4rem;color:#80D8C3;margin-bottom:10px;animation:bounce 2s infinite;}@keyframes bounce{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}h2{font-family:"Fredoka",sans-serif;color:#1A2E3A;font-size:1.5rem;margin-bottom:10px;}p{color:#6B8899;margin-bottom:20px;}.skor-box{background:linear-gradient(135deg,#FFD66B,#FFC107);border-radius:16px;padding:15px;color:#5A3E00;font-family:"Fredoka",sans-serif;font-size:1.8rem;font-weight:700;margin-bottom:25px;}.btn-back{display:inline-block;background:linear-gradient(135deg,#4DA8DA,#3A90C2);color:white;text-decoration:none;padding:14px 24px;border-radius:999px;font-family:"Fredoka",sans-serif;font-size:1.1rem;box-shadow:0 6px 20px rgba(77,168,218,0.4);transition:transform 0.2s;}.btn-back:hover{transform:scale(1.05);}</style></head><body><div class="card"><div class="icon-done"><i class="bi bi-patch-check-fill"></i></div><h2>Hore! Kamu sudah tuntas!</h2><p>Kamu sudah menyelesaikan <strong>'.$judul_tes.'</strong> untuk materi ini sebelumnya.</p><div class="skor-box">Skor Kamu: '.(int)$sudahTes['skor'].'</div><a href="/SAINTARA/pages/materi-detail.php?id='.$topik_id.'" class="btn-back"><i class="bi bi-arrow-left-circle-fill"></i> Kembali Belajar</a></div></body></html>');
}

// Ambil soal tes
$stmt = $pdo->prepare("SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d FROM soal_tes WHERE tipe = ? AND kelas = ? ORDER BY urutan ASC");
$stmt->execute([$tipe_tes, $kelas]);
$soalList = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($soalList)) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Soal belum tersedia untuk kelas ini.</h3><a href='/SAINTARA/pages/materi-detail.php?id=$topik_id'>Kembali</a></div>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?= $judul_tes ?> Kelas <?= $kelas ?> - Saintara</title>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root {
      --primary:#4DA8DA; --mint:#80D8C3; --yellow:#FFD66B; --bg:#F0F8FF;
      --navy:#1A2E3A; --muted:#6B8899; --fh:'Fredoka',sans-serif; --fb:'Nunito',sans-serif;
    }
    body { font-family:var(--fb); background:var(--bg); color:var(--navy); -webkit-font-smoothing:antialiased; overflow-x:hidden; }
    .page { max-width:480px; margin:0 auto; min-height:100vh; position:relative; padding-bottom:100px; }
    
    /* HEADER KECIL & PROGRESS */
    .tes-header { background:white; padding:15px 20px; border-bottom:2px solid #EBF6FC; display:flex; align-items:center; gap:15px; position:sticky; top:0; z-index:10; }
    .btn-close { width:36px; height:36px; border-radius:50%; background:#FFF0F0; color:#E05555; display:flex; align-items:center; justify-content:center; text-decoration:none; font-size:1.2rem; flex-shrink:0; }
    .prog-wrap { flex:1; }
    .prog-title { font-family:var(--fh); font-size:0.9rem; font-weight:700; color:var(--navy); display:flex; justify-content:space-between; margin-bottom:5px; }
    .prog-bg { height:10px; background:#EBF6FC; border-radius:999px; overflow:hidden; }
    .prog-fill { height:100%; background:linear-gradient(90deg,var(--primary),var(--mint)); border-radius:999px; transition:width 0.4s cubic-bezier(0.34,1.56,0.64,1); }

    /* ELLA MASCOT (MEMBERI SEMANGAT) */
    .ella-cheer { display:flex; align-items:center; gap:15px; padding:20px 20px 0; }
    .ella-svg { width:65px; height:65px; filter:drop-shadow(0 4px 10px rgba(77,168,218,0.2)); animation:float 3s ease-in-out infinite; }
    @keyframes float { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-8px);} }
    .ella-bubble { background:white; padding:10px 15px; border-radius:16px 16px 16px 0; border:2px solid #EBF6FC; font-size:0.85rem; font-weight:700; color:var(--primary); position:relative; box-shadow:0 4px 12px rgba(26,46,58,0.05); }

    /* KARTU SOAL */
    .soal-area { padding:20px; }
    .soal-card { background:white; border-radius:24px; padding:25px 20px; box-shadow:0 8px 24px rgba(26,46,58,0.06); border:2px solid rgba(77,168,218,0.1); margin-bottom:20px; animation:slideUp 0.4s cubic-bezier(0.34,1.56,0.64,1); }
    @keyframes slideUp { from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:translateY(0);} }
    .soal-pertanyaan { font-family:var(--fh); font-size:1.15rem; font-weight:600; line-height:1.5; color:var(--navy); text-align:center; }

    /* OPSI JAWABAN (GAMEFEEL) */
    .opsi-grid { display:flex; flex-direction:column; gap:12px; }
    .opsi-btn { background:white; border:3px solid #EBF6FC; border-radius:20px; padding:15px 20px; display:flex; align-items:center; gap:15px; cursor:pointer; font-family:var(--fb); transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1); position:relative; overflow:hidden; text-align:left; outline:none; }
    .opsi-btn:hover { border-color:var(--primary); transform:translateY(-2px); }
    .opsi-btn:active { transform:scale(0.96); }
    .opsi-huruf { width:36px; height:36px; border-radius:50%; background:#F0F8FF; color:var(--primary); display:flex; align-items:center; justify-content:center; font-family:var(--fh); font-size:1rem; font-weight:700; flex-shrink:0; transition:all 0.2s; }
    .opsi-teks { font-size:0.95rem; font-weight:700; color:var(--navy); flex:1; line-height:1.4; z-index:2; }
    
    /* STATE TERPILIH (Tidak Tahu Benar/Salah) */
    .opsi-btn.selected { border-color:var(--primary); background:#EBF6FC; transform:scale(1.02); box-shadow:0 6px 16px rgba(77,168,218,0.25); }
    .opsi-btn.selected .opsi-huruf { background:var(--primary); color:white; }

    /* TOMBOL NEXT BAWAH */
    .bottom-bar { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:white; padding:15px 20px; border-top:2px solid #EBF6FC; z-index:10; }
    .btn-next { width:100%; background:var(--primary); color:white; border:none; border-radius:18px; padding:16px; font-family:var(--fh); font-size:1.1rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 6px 20px rgba(77,168,218,0.35); transition:all 0.2s; }
    .btn-next:disabled { background:#D0E1EA; box-shadow:none; cursor:not-allowed; transform:none; }
    .btn-next:not(:disabled):active { transform:scale(0.96); }

    /* SELEBRASI MODAL */
    .modal-overlay { position:fixed; inset:0; background:rgba(26,46,58,0.6); z-index:999; display:none; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
    .modal-overlay.show { display:flex; animation:fadeIn 0.3s ease; }
    .modal-card { background:white; width:90%; max-width:340px; border-radius:30px; padding:30px 20px; text-align:center; transform:scale(0.8); transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1); box-shadow:0 20px 40px rgba(0,0,0,0.2); }
    .modal-overlay.show .modal-card { transform:scale(1); }
    .trophy-icon { font-size:4rem; color:var(--yellow); margin-bottom:10px; animation:bounce 2s infinite; }
    .modal-title { font-family:var(--fh); font-size:1.5rem; color:var(--navy); margin-bottom:5px; }
    .modal-sub { color:var(--muted); font-size:0.9rem; font-weight:700; margin-bottom:20px; }
    .skor-lingkaran { width:120px; height:120px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--mint)); margin:0 auto 25px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:white; box-shadow:0 10px 25px rgba(77,168,218,0.4); }
    .skor-angka { font-family:var(--fh); font-size:3rem; font-weight:700; line-height:1; }
    .skor-label { font-size:0.8rem; font-weight:800; opacity:0.9; }
    .btn-kembali { display:inline-block; background:var(--navy); color:white; text-decoration:none; padding:15px 30px; border-radius:999px; font-family:var(--fh); font-weight:700; transition:transform 0.2s; }
    .btn-kembali:hover { transform:scale(1.05); }

    @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
    #quizData { display:none; }
  </style>
</head>
<body>
<div class="page">
  <div id="quizData"
       data-soal='<?= json_encode($soalList, JSON_UNESCAPED_UNICODE) ?>'
       data-tipe="<?= $tipe_tes ?>"
       data-kelas="<?= $kelas ?>"
       data-topik="<?= $topik_id ?>"></div>

  <!-- HEADER -->
  <div class="tes-header">
    <a href="/SAINTARA/pages/materi-detail.php?id=<?= $topik_id ?>" class="btn-close" aria-label="Batal"><i class="bi bi-x-lg"></i></a>
    <div class="prog-wrap">
      <div class="prog-title">
        <span><?= $judul_tes ?></span>
        <span id="progText">1/10</span>
      </div>
      <div class="prog-bg"><div class="prog-fill" id="progFill" style="width:0%;"></div></div>
    </div>
  </div>

  <!-- ELLA CHEER -->
  <div class="ella-cheer">
    <svg class="ella-svg" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
      <ellipse cx="15" cy="44" rx="11" ry="14" fill="#3A90C2"/><ellipse cx="15" cy="44" rx="7" ry="9" fill="#80D8C3"/>
      <ellipse cx="65" cy="44" rx="11" ry="14" fill="#3A90C2"/><ellipse cx="65" cy="44" rx="7" ry="9" fill="#80D8C3"/>
      <circle cx="40" cy="38" r="24" fill="#4DA8DA"/>
      <ellipse cx="31" cy="34" rx="5.5" ry="6.5" fill="white"/><circle cx="32" cy="35" r="3.5" fill="#1A2E3A"/>
      <circle cx="33" cy="34" r="1.2" fill="white"/>
      <ellipse cx="49" cy="34" rx="5.5" ry="6.5" fill="white"/><circle cx="50" cy="35" r="3.5" fill="#1A2E3A"/>
      <circle cx="51" cy="34" r="1.2" fill="white"/>
      <ellipse cx="24" cy="42" rx="5" ry="3" fill="#FFD66B" fill-opacity="0.55"/>
      <ellipse cx="56" cy="42" rx="5" ry="3" fill="#FFD66B" fill-opacity="0.55"/>
      <path d="M30 44 Q40 53 50 44" stroke="#1A2E3A" stroke-width="2" stroke-linecap="round" fill="none"/>
      <path d="M28 44 Q22 48 21 54 Q20 59 25 60" stroke="#3A90C2" stroke-width="5" stroke-linecap="round" fill="none"/>
      <circle cx="25" cy="60" r="4" fill="#3A90C2"/>
    </svg>
    <div class="ella-bubble" id="ellaMsg">Fokus dan baca pertanyaannya dengan teliti ya! 🌟</div>
  </div>

  <!-- KARTU SOAL -->
  <div class="soal-area">
    <div class="soal-card" id="soalContainer">
      <div class="soal-pertanyaan" id="soalText">Memuat pertanyaan...</div>
    </div>
    <div class="opsi-grid" id="opsiContainer"></div>
  </div>

  <!-- TOMBOL BAWAH -->
  <div class="bottom-bar">
    <button class="btn-next" id="btnNext" onclick="nextSoal()" disabled>
      Pilih Jawaban Dulu
    </button>
  </div>
</div>

<!-- MODAL SELEBRASI (DIBUKA SAAT SELESAI) -->
<div class="modal-overlay" id="resultModal">
  <div class="modal-card">
    <div class="trophy-icon"><i class="bi bi-trophy-fill"></i></div>
    <h2 class="modal-title">Ujian Selesai!</h2>
    <div class="modal-sub">Kerja bagus! Inilah nilai kamu:</div>
    <div class="skor-lingkaran">
      <div class="skor-angka" id="finalScore">100</div>
      <div class="skor-label">POIN</div>
    </div>
    <a href="/SAINTARA/pages/materi-detail.php?id=<?= $topik_id ?>" class="btn-kembali">Kembali ke Materi</a>
  </div>
</div>

<script>
  const DATA = document.getElementById('quizData');
  const soalArr = JSON.parse(DATA.dataset.soal);
  const TOTAL = soalArr.length;
  const TIPE = DATA.dataset.tipe;
  const KELAS = DATA.dataset.kelas;
  const TOPIK_ID = DATA.dataset.topik;

  let currentIndex = 0;
  let jawabanSiswa = {}; 
  let startTime = Date.now();

  const progFill = document.getElementById('progFill');
  const progText = document.getElementById('progText');
  const soalContainer = document.getElementById('soalContainer');
  const soalText = document.getElementById('soalText');
  const opsiContainer = document.getElementById('opsiContainer');
  const btnNext = document.getElementById('btnNext');
  const ellaMsg = document.getElementById('ellaMsg');

  // Kalimat penyemangat Ella
  const semangatElla = [
    "Wah, kamu pintar sekali! Lanjut terus ya! 🚀",
    "Baca dengan teliti, pasti kamu bisa! 🧐",
    "Satu soal lagi terlewati, kamu hebat! 👏",
    "Jangan menyerah, kamu pasti tahu jawabannya! 💡",
    "Sedikit lagi selesai, semangat! 🔥"
  ];

  function renderSoal() {
    const s = soalArr[currentIndex];
    
    // Update Progress
    progText.textContent = `${currentIndex + 1}/${TOTAL}`;
    progFill.style.width = (((currentIndex) / TOTAL) * 100) + '%';
    
    // Update Pesan Ella sesekali
    if(currentIndex > 0) {
        ellaMsg.textContent = semangatElla[currentIndex % semangatElla.length];
    }

    // Animasi Ganti Soal
    soalContainer.style.animation = 'none';
    void soalContainer.offsetWidth; 
    soalContainer.style.animation = 'slideUp 0.4s cubic-bezier(0.34,1.56,0.64,1)';

    soalText.textContent = s.pertanyaan;
    opsiContainer.innerHTML = '';

    const opsiKeys = ['a', 'b', 'c', 'd'];
    const labelKeys = ['A', 'B', 'C', 'D'];
    
    opsiKeys.forEach((k, idx) => {
      if (!s['opsi_' + k]) return;
      
      const btn = document.createElement('button');
      btn.className = 'opsi-btn';
      
      // Jika sudah terpilih sebelumnya
      if (jawabanSiswa[s.id] === k) btn.classList.add('selected');
      
      btn.innerHTML = `
        <div class="opsi-huruf">${labelKeys[idx]}</div>
        <div class="opsi-teks">${s['opsi_' + k]}</div>
      `;
      btn.onclick = () => pilihJawaban(s.id, k);
      opsiContainer.appendChild(btn);
    });

    updateBtnNext();
  }

  function pilihJawaban(idSoal, jawaban) {
    jawabanSiswa[idSoal] = jawaban;
    
    // Update tampilan opsi yang diklik
    const buttons = opsiContainer.querySelectorAll('.opsi-btn');
    buttons.forEach((btn, idx) => {
      const keys = ['a','b','c','d'];
      if(keys[idx] === jawaban) {
          btn.classList.add('selected');
      } else {
          btn.classList.remove('selected');
      }
    });

    updateBtnNext();
  }

  function updateBtnNext() {
    const currentId = soalArr[currentIndex].id;
    if (jawabanSiswa[currentId]) {
      btnNext.disabled = false;
      if (currentIndex === TOTAL - 1) {
        btnNext.innerHTML = 'Kirim Ujian <i class="bi bi-send-check-fill"></i>';
      } else {
        btnNext.innerHTML = 'Lanjut <i class="bi bi-arrow-right-circle-fill"></i>';
      }
    } else {
      btnNext.disabled = true;
      btnNext.innerHTML = 'Pilih Jawaban Dulu';
    }
  }

  function nextSoal() {
    if (currentIndex < TOTAL - 1) {
      currentIndex++;
      renderSoal();
    } else {
      submitTes();
    }
  }

  function submitTes() {
    btnNext.disabled = true;
    btnNext.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengoreksi...';
    progFill.style.width = '100%';

    const durasi = Math.round((Date.now() - startTime) / 1000);
    const body = new URLSearchParams({
      tipe: TIPE,
      kelas: KELAS,
      durasi: durasi,
      jawaban_siswa: JSON.stringify(jawabanSiswa)
    });

    fetch('/SAINTARA/api/tes.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'ok') {
        // Tampilkan Modal Selebrasi
        document.getElementById('finalScore').textContent = Math.round(data.skor);
        document.getElementById('resultModal').classList.add('show');
      } else {
        alert('Gagal menyimpan: ' + data.message);
        btnNext.disabled = false;
      }
    })
    .catch(err => {
      alert('Terjadi kesalahan jaringan.');
      btnNext.disabled = false;
    });
  }

  // Mulai Render Pertama
  renderSoal();
</script>
</body>
</html>