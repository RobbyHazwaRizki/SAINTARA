<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/kuis.php — Halaman Kuis Interaktif & Ujian Sesi
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/session.php';

// Pastikan siswa sudah login
requireSiswa(); 

$pdo = getDB();

// 1. Ambil data siswa yang sedang login
$stmtSiswa = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmtSiswa->execute([$_SESSION['siswa_id']]);
$siswa = $stmtSiswa->fetch();

if (!$siswa) { 
    session_destroy(); 
    header('Location: /SAINTARA/login.php'); 
    exit; 
}

// 2. Persiapkan Variabel Parameter
$kelas_siswa = (int)$siswa['kelas'];
$topik_id    = (int)($_GET['id'] ?? 0);
$mode_sesi   = isset($_GET['sesi_id']) ? (int)$_GET['sesi_id'] : 0;
$tipe        = $_GET['tipe'] ?? 'normal';

$soalList   = [];
$topikNama  = "";
$jumlahSoal = 10;
$xpPerSoal  = 10; 

// ============================================================
// 3. LOGIKA PEMILIHAN SOAL (SESI vs MATERI vs HARIAN)
// ============================================================

// Tentukan batas soal Kuis Materi & Harian berdasarkan kelas
$limitSoalBiasa = 5; // Kelas 1 & 2
if ($kelas_siswa == 3 || $kelas_siswa == 4) $limitSoalBiasa = 10;
if ($kelas_siswa == 5 || $kelas_siswa == 6) $limitSoalBiasa = 15;

if ($mode_sesi > 0) {
    // ------------------------------------------
    // A. MODE UJIAN SESI (TRY OUT) - 1 KALI SAJA
    // ------------------------------------------
    $topikNama = "Ujian Sesi (Try Out)";
    
    // Cek Sesi Aktif
    $stmtSesi = $pdo->prepare("SELECT id FROM sesi WHERE id = ? AND aktif = 1");
    $stmtSesi->execute([$mode_sesi]);
    if (!$stmtSesi->fetch()) {
        header("Location: /SAINTARA/pages/beranda.php?error=sesi_berakhir"); 
        exit;
    }

    // Cek apakah sudah pernah ujian
    $stmtSudah = $pdo->prepare("SELECT id FROM xp_sesi WHERE sesi_id = ? AND siswa_id = ?");
    $stmtSudah->execute([$mode_sesi, $siswa['id']]);
    if ($stmtSudah->fetch()) {
        header("Location: /SAINTARA/pages/ranking.php?tab=sesi&status=sudah_ujian"); 
        exit;
    }

    // Tentukan jumlah soal Try Out (Lebih banyak dari kuis biasa)
    $jumlahSoalTryOut = 10; // Kelas 1 & 2
    if ($kelas_siswa == 3 || $kelas_siswa == 4) $jumlahSoalTryOut = 20;
    if ($kelas_siswa == 5 || $kelas_siswa == 6) $jumlahSoalTryOut = 30;

    // Ambil Soal Campuran sesuai kelas
    $stmt = $pdo->prepare("
        SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban 
        FROM soal WHERE kelas = ? ORDER BY RAND() LIMIT $jumlahSoalTryOut
    ");
    $stmt->execute([$kelas_siswa]);
    $soalList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($topik_id > 0 && $tipe !== 'harian') {
    // ------------------------------------------
    // B. MODE KUIS MATERI (BELAJAR TOPIK TERTENTU)
    // ------------------------------------------
    $stmtTopik = $pdo->prepare("SELECT judul, kelas FROM topik WHERE id = ?");
    $stmtTopik->execute([$topik_id]);
    $topik = $stmtTopik->fetch();

    if (!$topik) { header("Location: /SAINTARA/pages/beranda.php?error=topik_tidak_valid"); exit; }
    if ((int)$topik['kelas'] !== $kelas_siswa) { header("Location: /SAINTARA/pages/beranda.php?error=akses_ditolak"); exit; }
    
    $topikNama = "Kuis: " . $topik['judul'];
    
    // Ambil Soal KHUSUS Topik dengan limit dinamis
    $stmt = $pdo->prepare("
        SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban 
        FROM soal WHERE topik_id = ? ORDER BY RAND() LIMIT $limitSoalBiasa
    ");
    $stmt->execute([$topik_id]);
    $soalList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // ------------------------------------------
    // C. MODE KUIS HARIAN (LATIHAN BEBAS)
    // ------------------------------------------
    $topikNama = "Kuis Harian Kelas " . $kelas_siswa;
    
    // Ambil Soal Acak sesuai kelas dengan limit dinamis
    $stmt = $pdo->prepare("
        SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban 
        FROM soal WHERE kelas = ? ORDER BY RAND() LIMIT $limitSoalBiasa
    ");
    $stmt->execute([$kelas_siswa]);
    $soalList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// 4. JIKA DATABASE SOAL MASIH KOSONG
if (empty($soalList)) {
    die("
    <div style='padding:20px; font-family:sans-serif; text-align:center;'>
        <h3>Oups! Kuis Belum Siap.</h3>
        <p>Soal untuk kelas atau topik ini belum ditambahkan ke dalam Database.</p>
        <a href='/SAINTARA/pages/beranda.php' style='display:inline-block; margin-top:10px; padding:10px 20px; background:#4DA8DA; color:#fff; text-decoration:none; border-radius:8px;'>Kembali ke Beranda</a>
    </div>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title>Kuis — <?= htmlspecialchars($topikNama) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root {
      --primary:#4DA8DA; --primary-dk:#3590C2; --mint:#80D8C3;
      --yellow:#FFD66B;  --bg:#F0F8FF; --white:#fff;
      --navy:#1A2E3A;    --muted:#6B8899; --error:#E05555;
      --fh:'Fredoka',sans-serif; --fb:'Nunito',sans-serif;
    }
    html,body { font-family:var(--fb); background:var(--bg); color:var(--navy); -webkit-font-smoothing:antialiased; overflow-x:hidden; }
    .page { max-width:480px; margin:0 auto; min-height:100vh; background:var(--bg); position:relative; }

    /* ── QUIZ HEADER ─────────────────────────────────────────── */
    .quiz-header {
      background:linear-gradient(155deg,#3A90C2 0%,#4DA8DA 55%,#6EC5E0 100%);
      padding:16px 20px 20px; position:relative; overflow:hidden;
    }
    .qh-geo { position:absolute; opacity:0.07; pointer-events:none; border-radius:50%; }
    .qhg1 { width:120px;height:120px;border:3px solid white;top:-40px;right:-30px; }
    .qhg2 { width:60px;height:60px;background:white;bottom:5px;left:-15px; }

    .qh-top {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:14px; position:relative; z-index:2;
    }
    .qh-back {
      width:38px; height:38px; border-radius:50%;
      background:rgba(255,255,255,0.18); border:1.5px solid rgba(255,255,255,0.3);
      display:flex; align-items:center; justify-content:center;
      color:white; font-size:1rem; cursor:pointer; transition:background 0.18s;
      flex-shrink:0; text-decoration:none;
    }
    .qh-back:hover { background:rgba(255,255,255,0.28); }
    .qh-title {
      font-family:var(--fh); font-size:1rem; font-weight:700; color:white;
      flex:1; text-align:center; padding:0 8px;
    }
    .qh-xp-live {
      display:flex; align-items:center; gap:4px;
      background:rgba(255,255,255,0.18); border:1.5px solid rgba(255,255,255,0.28);
      border-radius:999px; padding:5px 12px;
      font-family:var(--fh); font-size:0.92rem; font-weight:700; color:var(--yellow);
      flex-shrink:0; transition:transform 0.2s;
    }
    .qh-xp-live.pop { animation:xpPop 0.4s cubic-bezier(0.34,1.56,0.64,1); }
    @keyframes xpPop {
      0%,100%{ transform:scale(1); }
      50%    { transform:scale(1.25); }
    }

    /* Progress bar soal */
    .qh-progress {
      position:relative; z-index:2;
    }
    .qh-prog-info {
      display:flex; justify-content:space-between;
      font-size:0.72rem; color:rgba(255,255,255,0.75); font-weight:700;
      margin-bottom:6px;
    }
    .qh-prog-bg {
      height:8px; background:rgba(255,255,255,0.2);
      border-radius:999px; overflow:hidden;
    }
    .qh-prog-fill {
      height:100%; background:var(--yellow);
      border-radius:999px; transition:width 0.4s ease;
    }

    /* ── TIMER BAR ───────────────────────────────────────────── */
    .timer-bar-wrap {
      height:5px; background:rgba(77,168,218,0.12); overflow:hidden;
    }
    .timer-bar {
      height:100%; background:var(--primary);
      border-radius:0 999px 999px 0;
      transition:width 1s linear, background 0.5s;
    }
    .timer-bar.warn   { background:var(--yellow); }
    .timer-bar.danger { background:var(--error); }

    /* ── ELLA REACTION ───────────────────────────────────────── */
    .ella-reaction-area {
      display:flex; justify-content:center; padding:14px 0 6px;
    }
    .ella-react-svg {
      width:72px; height:72px;
      filter:drop-shadow(0 4px 12px rgba(26,46,58,0.18));
      transition:transform 0.2s;
    }

    /* ── SOAL CARD ───────────────────────────────────────────── */
    .soal-wrap { padding:0 16px; }
    .soal-card {
      background:white; border-radius:20px; padding:20px 18px;
      box-shadow:0 4px 16px rgba(26,46,58,0.09);
      border:1.5px solid rgba(77,168,218,0.1);
      text-align:center; margin-bottom:14px;
      animation:soalIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes soalIn {
      from{ opacity:0; transform:translateY(12px) scale(0.97); }
      to  { opacity:1; transform:translateY(0) scale(1); }
    }
    .soal-nomor {
      font-size:0.72rem; font-weight:800; color:var(--muted);
      text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;
    }
    .soal-text {
      font-family:var(--fh); font-size:1.05rem; font-weight:600;
      color:var(--navy); line-height:1.5;
    }

    /* ── OPSI JAWABAN ────────────────────────────────────────── */
    .opsi-grid {
      display:flex; flex-direction:column; gap:9px;
      padding:0 16px 16px;
    }
    .opsi-btn {
      background:white; border:2px solid rgba(77,168,218,0.15);
      border-radius:16px; padding:13px 16px;
      display:flex; align-items:center; gap:13px;
      cursor:pointer; text-align:left; width:100%;
      font-family:var(--fb); transition:all 0.18s cubic-bezier(0.34,1.56,0.64,1);
      box-shadow:0 2px 8px rgba(26,46,58,0.05);
      position:relative; overflow:hidden;
    }
    .opsi-btn::before {
      content:''; position:absolute; inset:0;
      background:linear-gradient(135deg,rgba(77,168,218,0.06),transparent);
      opacity:0; transition:opacity 0.18s;
    }
    .opsi-btn:hover::before { opacity:1; }
    .opsi-btn:hover { border-color:var(--primary); transform:translateX(4px); }
    .opsi-btn:active { transform:scale(0.97); }
    .opsi-btn:disabled { cursor:not-allowed; pointer-events:none; }

    .opsi-huruf {
      width:34px; height:34px; border-radius:50%;
      background:#F0F8FF; border:2px solid rgba(77,168,218,0.2);
      display:flex; align-items:center; justify-content:center;
      font-family:var(--fh); font-size:0.88rem; font-weight:700;
      color:var(--primary); flex-shrink:0; transition:all 0.18s;
    }
    .opsi-teks {
      font-size:0.9rem; font-weight:600; color:var(--navy); flex:1; line-height:1.4;
    }

    .opsi-btn.benar {
      border-color:#0F7A5A; background:linear-gradient(135deg,#E1F5EE,#D0F5E8);
      transform:translateX(4px); box-shadow:0 4px 14px rgba(15,122,90,0.2);
      animation:benarPulse 0.4s ease;
    }
    .opsi-btn.benar .opsi-huruf { background:#0F7A5A; border-color:#0F7A5A; color:white; }
    .opsi-btn.benar .opsi-teks  { color:#0A5A40; }
    @keyframes benarPulse {
      0%,100%{ transform:translateX(4px) scale(1); }
      50%    { transform:translateX(4px) scale(1.02); }
    }

    .opsi-btn.salah {
      border-color:var(--error); background:linear-gradient(135deg,#FFE8E8,#FFD5D5);
      animation:salahShake 0.4s ease;
    }
    .opsi-btn.salah .opsi-huruf { background:var(--error); border-color:var(--error); color:white; }
    .opsi-btn.salah .opsi-teks  { color:#8A2020; }
    @keyframes salahShake {
      0%,100%{ transform:translateX(0); }
      20%    { transform:translateX(-8px); }
      60%    { transform:translateX(8px); }
    }

    .opsi-btn.benar-reveal {
      border-color:#0F7A5A; background:#E8FFF5;
    }
    .opsi-btn.benar-reveal .opsi-huruf { background:#80D8C3; border-color:#0F7A5A; color:white; }

    /* ── FEEDBACK OVERLAY ────────────────────────────────────── */
    .feedback-overlay {
      position:fixed; inset:0; z-index:500;
      display:flex; flex-direction:column;
      align-items:center; justify-content:center;
      pointer-events:none; opacity:0;
      transition:opacity 0.25s;
    }
    .feedback-overlay.show { opacity:1; pointer-events:all; }
    .feedback-overlay.bg-benar { background:rgba(15,122,90,0.12); }
    .feedback-overlay.bg-salah { background:rgba(224,85,85,0.1); }

    .feedback-icon {
      font-size:4.5rem; animation:feedbackBounce 0.45s cubic-bezier(0.34,1.56,0.64,1);
    }
    .feedback-text {
      font-family:var(--fh); font-size:1.5rem; font-weight:700;
      margin-top:8px; animation:feedbackBounce 0.45s cubic-bezier(0.34,1.56,0.64,1) 0.05s both;
    }
    .feedback-text.benar { color:#0F7A5A; }
    .feedback-text.salah { color:var(--error); }
    .feedback-xp {
      font-family:var(--fh); font-size:1rem; font-weight:700;
      color:#4DA8DA; margin-top:4px;
      animation:feedbackBounce 0.45s cubic-bezier(0.34,1.56,0.64,1) 0.1s both;
    }
    @keyframes feedbackBounce {
      from{ transform:scale(0) rotate(-10deg); opacity:0; }
      to  { transform:scale(1) rotate(0); opacity:1; }
    }

    /* ── ANTI CHEAT OVERLAY ──────────────────────────────────── */
    .anti-cheat-overlay {
      position:fixed; inset:0; z-index:9999;
      background:rgba(26,46,58,0.95); backdrop-filter:blur(5px);
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      opacity:0; pointer-events:none; transition:opacity 0.3s;
    }
    .anti-cheat-overlay.show { opacity:1; pointer-events:all; }
    .anti-cheat-box {
      background:white; padding:30px 20px; border-radius:24px; text-align:center;
      max-width:320px; box-shadow:0 10px 40px rgba(0,0,0,0.3);
      animation:cheatBounce 0.5s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes cheatBounce {
      0% { transform:scale(0.8) translateY(20px); opacity:0; }
      100% { transform:scale(1) translateY(0); opacity:1; }
    }
    .anti-cheat-icon { font-size:4rem; margin-bottom:10px; }
    .anti-cheat-title { font-family:var(--fh); color:var(--error); font-size:1.4rem; margin-bottom:8px; }
    .anti-cheat-desc { color:var(--muted); font-size:0.95rem; line-height:1.5; margin-bottom:20px; font-weight:600; }
    .anti-cheat-btn {
      background:var(--error); color:white; font-family:var(--fh); border:none; border-radius:12px;
      padding:12px 24px; font-size:1rem; font-weight:700; cursor:pointer;
    }

    /* CSS UNTUK TOMBOL SOUND MENGAMBANG */
    .sound-toggle-btn {
      position: fixed;
      bottom: 20px;
      left: 20px;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: white;
      border: 3px solid #EBF6FC;
      color: #6B8899;
      font-size: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 15px rgba(26,46,58,0.1);
      cursor: pointer;
      z-index: 999;
      transition: all 0.2s cubic-bezier(0.34,1.56,0.64,1);
    }
    .sound-toggle-btn.sound-on { color: #4DA8DA; border-color: #4DA8DA; }
    .sound-toggle-btn:hover { transform: scale(1.1) rotate(-10deg); }
    .sound-toggle-btn:active { transform: scale(0.9); }
  </style>
</head>
<body>
<div class="page">

  <div id="quizData"
       data-soal='<?= json_encode(array_values($soalList), JSON_UNESCAPED_UNICODE) ?>'
       data-jumlah="<?= count($soalList) ?>"
       data-kelas="<?= $kelas_siswa ?>"
       data-topik-id="<?= $topik_id ?>"
       data-sesi-id="<?= $mode_sesi ?>" 
       data-topik-nama="<?= htmlspecialchars($topikNama) ?>"
       data-xp-per-soal="<?= $xpPerSoal ?>"
       data-siswa-xp="<?= htmlspecialchars($siswa['xp']) ?>"
       data-siswa-nama="<?= htmlspecialchars($siswa['nama']) ?>"
  ></div>

  <div class="quiz-header" id="quizHeader">
    <div class="qh-geo qhg1"></div>
    <div class="qh-geo qhg2"></div>
    <div class="qh-top">
      <a href="/SAINTARA/pages/materi.php" class="qh-back" id="btnBack" aria-label="Keluar kuis">
        <i class="bi bi-x-lg"></i>
      </a>
      <div class="qh-title" id="qhTitle"><?= htmlspecialchars($topikNama) ?></div>
      <div class="qh-xp-live" id="xpLive">
        <i class="bi bi-star-fill"></i>
        <span id="xpLiveNum">0</span> XP
      </div>
    </div>
    <div class="qh-progress">
      <div class="qh-prog-info">
        <span id="progText">Soal 1 dari <?= count($soalList) ?></span>
        <span id="timerText">⏱ 30s</span>
      </div>
      <div class="qh-prog-bg">
        <div class="qh-prog-fill" id="progFill" style="width:0%"></div>
      </div>
    </div>
  </div>

  <div class="timer-bar-wrap">
    <div class="timer-bar" id="timerBar" style="width:100%"></div>
  </div>

  <div class="ella-reaction-area">
    <svg class="ella-react-svg" id="ellaSvg" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
      <ellipse cx="15" cy="44" rx="11" ry="14" fill="#3A90C2"/>
      <ellipse cx="15" cy="44" rx="7"  ry="9"  fill="#80D8C3"/>
      <ellipse cx="65" cy="44" rx="11" ry="14" fill="#3A90C2"/>
      <ellipse cx="65" cy="44" rx="7"  ry="9"  fill="#80D8C3"/>
      <circle cx="40" cy="38" r="24" fill="#4DA8DA"/>
      <ellipse cx="31" cy="34" rx="5.5" ry="6.5" fill="white"/>
      <circle  cx="32" cy="35" r="3.5" fill="#1A2E3A" id="ellaLeftEye"/>
      <circle  cx="33" cy="34" r="1.2" fill="white"/>
      <ellipse cx="49" cy="34" rx="5.5" ry="6.5" fill="white"/>
      <circle  cx="50" cy="35" r="3.5" fill="#1A2E3A" id="ellaRightEye"/>
      <circle  cx="51" cy="34" r="1.2" fill="white"/>
      <ellipse cx="24" cy="42" rx="5" ry="3" fill="#FFD66B" fill-opacity="0.55" id="ellaBlushL"/>
      <ellipse cx="56" cy="42" rx="5" ry="3" fill="#FFD66B" fill-opacity="0.55" id="ellaBlushR"/>
      <path id="ellaMouth" d="M33 44 Q40 50 47 44" stroke="#1A2E3A" stroke-width="2" stroke-linecap="round" fill="none"/>
      <path d="M28 44 Q22 48 21 54 Q20 59 25 60" stroke="#3A90C2" stroke-width="5" stroke-linecap="round" fill="none"/>
      <circle cx="25" cy="60" r="4" fill="#3A90C2"/>
    </svg>
  </div>

  <div class="soal-wrap">
    <div class="soal-card" id="soalCard">
      <div class="soal-nomor" id="soalNomor">Soal 1</div>
      <div class="soal-text"  id="soalText">Memuat soal...</div>
    </div>
  </div>

  <div class="opsi-grid" id="opsiGrid"></div>

  <div class="feedback-overlay" id="feedbackOverlay">
    <div class="feedback-icon" id="feedbackIcon"></div>
    <div class="feedback-text" id="feedbackText"></div>
    <div class="feedback-xp"  id="feedbackXp"></div>
  </div>

  <div class="anti-cheat-overlay" id="antiCheatOverlay">
    <div class="anti-cheat-box">
      <div class="anti-cheat-icon">👀</div>
      <div class="anti-cheat-title">Hayo, mau ke mana?</div>
      <div class="anti-cheat-desc">Jangan membuka tab atau aplikasi lain selama ujian berlangsung. <br><br><span style="color:var(--error)">Waktu kamu dikurangi 5 detik!</span></div>
      <button class="anti-cheat-btn" onclick="closeAntiCheat()">Saya Mengerti</button>
    </div>
  </div>

  <audio id="bgmAudio" src="/SAINTARA/assets/audio/nengjemping-cheerful-little-boy-352347.mp3" loop></audio>
  <audio id="sfxPop" src="/SAINTARA/assets/audio/soundreality-pop-click-312649.mp3"></audio>
  <audio id="sfxMenang" src="/SAINTARA/assets/audio/koiroylers-correct-356013.mp3"></audio>
  <audio id="sfxGagal" src="/SAINTARA/assets/audio/freesound_community-wrong-47985.mp3"></audio>

  <button id="btnSoundToggle" class="sound-toggle-btn">
    <i class="bi bi-volume-mute-fill"></i>
  </button>

</div>

<script>
// ============================================================
// SAINTARA QUIZ ENGINE (FRONTEND JAVASCRIPT)
// ============================================================

const DATA     = document.getElementById('quizData');
const soalArr  = JSON.parse(DATA.dataset.soal);
const TOTAL    = parseInt(DATA.dataset.jumlah);
const KELAS    = parseInt(DATA.dataset.kelas);
const TOPIK_ID = parseInt(DATA.dataset.topikId);
const SESI_ID  = parseInt(DATA.dataset.sesiId) || 0; 
const XP_PER   = parseInt(DATA.dataset.xpPerSoal);

let soalIdx    = 0;
let xpGained   = 0;
let benarCount = 0;
let salahCount = 0;
let timerVal   = KELAS <= 4 ? 30 : 35;
let timerMax   = timerVal;
let timerInterval;
let answered   = false;
let startTime  = Date.now();
let durasi     = 0;
let fastCount  = 0; 

const ellaEl    = document.getElementById('ellaSvg');
const soalCard  = document.getElementById('soalCard');
const soalNomor = document.getElementById('soalNomor');
const soalText  = document.getElementById('soalText');
const opsiGrid  = document.getElementById('opsiGrid');
const progFill  = document.getElementById('progFill');
const progText  = document.getElementById('progText');
const timerText = document.getElementById('timerText');
const timerBar  = document.getElementById('timerBar');
const xpLive    = document.getElementById('xpLive');
const xpLiveNum = document.getElementById('xpLiveNum');
const feedbackOverlay = document.getElementById('feedbackOverlay');
const feedbackIcon=document.getElementById('feedbackIcon');
const feedbackTxt =document.getElementById('feedbackText');
const feedbackXp  =document.getElementById('feedbackXp');
const btnBack   = document.getElementById('btnBack');

// --- MESIN AUDIO KHUSUS KUIS ---
const bgmAudio = document.getElementById('bgmAudio');
const sfxPop = document.getElementById('sfxPop');
const sfxMenang = document.getElementById('sfxMenang');
const sfxGagal = document.getElementById('sfxGagal');
const btnSound = document.getElementById('btnSoundToggle');

bgmAudio.volume = 0.10; 
sfxPop.volume = 0.8;
sfxMenang.volume = 1.0;
sfxGagal.volume = 0.8;

let isSoundOn = localStorage.getItem('saintara_sound') === 'on';

// TUNGGU MP3 siap, lalu lanjutkan dari detik sebelumnya
bgmAudio.addEventListener('loadedmetadata', () => {
    const savedTime = localStorage.getItem('saintara_bgm_time');
    if (savedTime && isSoundOn) {
        bgmAudio.currentTime = parseFloat(savedTime);
    }
});

// Simpan detik lagu tepat saat ujian selesai / pindah halaman
window.addEventListener('beforeunload', () => {
    localStorage.setItem('saintara_bgm_time', bgmAudio.currentTime);
});
window.addEventListener('pagehide', () => {
    localStorage.setItem('saintara_bgm_time', bgmAudio.currentTime);
});

function updateSoundUI() {
  if (isSoundOn) {
    btnSound.classList.add('sound-on');
    btnSound.innerHTML = '<i class="bi bi-volume-up-fill"></i>';
  } else {
    btnSound.classList.remove('sound-on');
    btnSound.innerHTML = '<i class="bi bi-volume-mute-fill"></i>';
  }
}

if (isSoundOn) {
    setTimeout(() => { bgmAudio.play().catch(e => console.log('Autoplay dicegah')); }, 100);
}
updateSoundUI();

btnSound.addEventListener('click', () => {
  isSoundOn = !isSoundOn;
  localStorage.setItem('saintara_sound', isSoundOn ? 'on' : 'off');
  
  if (isSoundOn) {
    bgmAudio.play().catch(e => console.log('Autoplay dicegah'));
  } else {
    bgmAudio.pause();
  }
  updateSoundUI();
});

function playSFX(audioElement) {
  if (isSoundOn) {
    audioElement.currentTime = 0; 
    audioElement.play().catch(e => console.log('SFX dicegah'));
  }
}
// ------------------------------------------------
// --- ANTI CHEAT (DETEKSI WINDOW BLUR) ---
const antiCheatOverlay = document.getElementById('antiCheatOverlay');

window.addEventListener('blur', () => {
  // Hanya berlaku untuk Mode Sesi (Try Out) saat soal belum selesai
  if (SESI_ID > 0 && !answered && soalIdx < TOTAL) {
    antiCheatOverlay.classList.add('show');
    timerVal = Math.max(1, timerVal - 5); // Penalti kurangi 5 detik
    updateTimerUI();
    playSFX(sfxGagal); // Bunyikan suara peringatan
  }
});

function closeAntiCheat() {
  antiCheatOverlay.classList.remove('show');
}

// --- INISIALISASI ---
loadSoal(soalIdx);
startTimer();

function loadSoal(idx) {
  answered = false;
  const s  = soalArr[idx];

  progText.textContent = `Soal ${idx + 1} dari ${TOTAL}`;
  soalNomor.textContent= `Soal ${idx + 1}`;
  soalText.textContent = s.pertanyaan;
  progFill.style.width = ((idx / TOTAL) * 100) + '%';

  soalCard.style.animation = 'none';
  void soalCard.offsetWidth;
  soalCard.style.animation = 'soalIn 0.35s cubic-bezier(0.34,1.56,0.64,1)';

  opsiGrid.innerHTML = '';
  const opsiKeys  = ['a','b','c','d'];
  const opsiLabel = ['A','B','C','D'];

  opsiKeys.forEach((k, i) => {
    const teks = s['opsi_' + k];
    if (!teks) return;

    const btn = document.createElement('button');
    btn.className = 'opsi-btn';
    btn.dataset.key = k;
    btn.innerHTML = `
      <div class="opsi-huruf">${opsiLabel[i]}</div>
      <div class="opsi-teks">${teks}</div>
    `;
    btn.addEventListener('click', () => pilihJawaban(btn, k, s.jawaban));
    opsiGrid.appendChild(btn);
  });

  setEllaEkspresi('happy');
  clearInterval(timerInterval);
  timerVal = timerMax;
  updateTimerUI();
  startTimer();
}

function pilihJawaban(btn, pilihan, jawaban) {
  if (answered) return;
  answered = true;
  clearInterval(timerInterval);

  playSFX(sfxPop); // Bunyikan efek klik tombol

  const benar   = pilihan === jawaban;
  const elapsed = (Date.now() - startTime) / 1000;
  if (benar && elapsed < 6) fastCount++;

  if (benar) {
    btn.classList.add('benar');
    xpGained   += XP_PER;
    benarCount++;
    xpLiveNum.textContent = xpGained;
    xpLive.classList.add('pop');
    setTimeout(() => xpLive.classList.remove('pop'), 400);
    setEllaEkspresi('excited');
    playSFX(sfxMenang); // Bunyikan suara jawaban benar
    showFeedback(true, XP_PER);
  } else {
    btn.classList.add('salah');
    salahCount++;
    document.querySelectorAll('.opsi-btn').forEach(b => {
      if (b.dataset.key === jawaban) b.classList.add('benar-reveal');
    });
    setEllaEkspresi('sad');
    playSFX(sfxGagal); // Bunyikan suara jawaban salah
    showFeedback(false, 0);
  }

  document.querySelectorAll('.opsi-btn').forEach(b => b.disabled = true);

  setTimeout(() => {
    hideFeedback();
    nextSoal();
  }, 1400);
}

function startTimer() {
  startTime = Date.now();
  timerInterval = setInterval(() => {
    timerVal--;
    updateTimerUI();
    if (timerVal <= 0) {
      clearInterval(timerInterval);
      if (!answered) autoSalah();
    }
  }, 1000);
}

function updateTimerUI() {
  const pct = (timerVal / timerMax) * 100;
  timerBar.style.width = pct + '%';
  timerText.textContent = '⏱ ' + timerVal + 's';
  timerBar.className = 'timer-bar';
  if (pct <= 30) timerBar.classList.add('danger');
  else if (pct <= 55) timerBar.classList.add('warn');
  if (timerVal <= 5) setEllaEkspresi('thinking');
}

function autoSalah() {
  answered = true;
  salahCount++;
  const s = soalArr[soalIdx];
  document.querySelectorAll('.opsi-btn').forEach(b => {
    if (b.dataset.key === s.jawaban) b.classList.add('benar-reveal');
    b.disabled = true;
  });
  setEllaEkspresi('sad');
  playSFX(sfxGagal); // Bunyikan suara jawaban salah
  showFeedback(false, 0, true);
  setTimeout(() => { hideFeedback(); nextSoal(); }, 1400);
}

function nextSoal() {
  soalIdx++;
  if (soalIdx >= TOTAL) {
    selesaiKuis();
  } else {
    loadSoal(soalIdx);
  }
}

function selesaiKuis() {
  clearInterval(timerInterval);
  durasi = Math.round((Date.now() - startTime) / 1000 + (soalIdx * timerMax));

  const body = new URLSearchParams({
    topik_id:  TOPIK_ID,
    sesi_id:   SESI_ID, 
    kelas:     KELAS,
    benar:     benarCount,
    salah:     salahCount,
    xp_gained: xpGained,
    durasi:    durasi,
    fast_count:fastCount,
  });

  fetch('/SAINTARA/api/quiz.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString()
  })
  .then(r => r.json())
  .then(data => {
    const params = new URLSearchParams({
      benar:     benarCount,
      salah:     salahCount,
      xp:        xpGained,
      total:     TOTAL,
      topik:     TOPIK_ID,
      kelas:     KELAS,
      badges:    JSON.stringify(data.badges_unlocked || []),
      level_naik:data.level_naik ? '1' : '0',
      level_baru:data.level_baru || '',
    });
    window.location.href = '/SAINTARA/pages/hasil.php?' + params.toString();
  })
  .catch(() => {
    window.location.href = '/SAINTARA/pages/beranda.php';
  });
}

function showFeedback(isBenar, xp, habisWaktu = false) {
  feedbackOverlay.className = 'feedback-overlay show ' + (isBenar ? 'bg-benar' : 'bg-salah');
  if (habisWaktu) {
    feedbackIcon.textContent = '⏰';
    feedbackTxt.textContent  = 'Waktu Habis!';
    feedbackTxt.className    = 'feedback-text salah';
    feedbackXp.textContent   = '';
  } else if (isBenar) {
    feedbackIcon.textContent = '🎉';
    feedbackTxt.textContent  = 'Benar!';
    feedbackTxt.className    = 'feedback-text benar';
    feedbackXp.textContent   = '+' + xp + ' XP';
  } else {
    feedbackIcon.textContent = '😅';
    feedbackTxt.textContent  = 'Salah!';
    feedbackTxt.className    = 'feedback-text salah';
    feedbackXp.textContent   = 'Coba lagi ya!';
  }
}

function hideFeedback() {
  feedbackOverlay.className = 'feedback-overlay';
}

function setEllaEkspresi(mood) {
  const mouth = document.getElementById('ellaMouth');
  const blushL= document.getElementById('ellaBlushL');
  const blushR= document.getElementById('ellaBlushR');
  const leftEye = document.getElementById('ellaLeftEye');
  const rightEye= document.getElementById('ellaRightEye');

  ellaEl.style.animation = 'none';
  void ellaEl.offsetWidth;

  switch(mood) {
    case 'excited':
      mouth.setAttribute('d','M30 44 Q40 53 50 44');
      blushL.setAttribute('fill-opacity','0.85');
      blushR.setAttribute('fill-opacity','0.85');
      ellaEl.style.animation = 'ellaJump 0.5s cubic-bezier(0.34,1.56,0.64,1)';
      break;
    case 'sad':
      mouth.setAttribute('d','M33 50 Q40 43 47 50');
      blushL.setAttribute('fill-opacity','0.2');
      blushR.setAttribute('fill-opacity','0.2');
      ellaEl.style.animation = 'ellaShake 0.4s ease';
      break;
    case 'thinking':
      mouth.setAttribute('d','M34 47 Q40 47 46 47');
      blushL.setAttribute('fill-opacity','0.4');
      blushR.setAttribute('fill-opacity','0.4');
      leftEye.setAttribute('ry','2');
      rightEye.setAttribute('ry','2');
      break;
    default: // happy
      mouth.setAttribute('d','M33 44 Q40 50 47 44');
      blushL.setAttribute('fill-opacity','0.55');
      blushR.setAttribute('fill-opacity','0.55');
      leftEye.setAttribute('ry','3.5');
      rightEye.setAttribute('ry','3.5');
  }
}

const st = document.createElement('style');
st.textContent = `
  @keyframes ellaJump {
    0%  { transform:translateY(0) scale(1); }
    40% { transform:translateY(-16px) scale(1.08); }
    70% { transform:translateY(-6px) scale(0.96); }
    100%{ transform:translateY(0) scale(1); }
  }
  @keyframes ellaShake {
    0%,100%{ transform:translateX(0); }
    25%    { transform:translateX(-8px) rotate(-5deg); }
    75%    { transform:translateX(8px) rotate(5deg); }
  }
`;
document.head.appendChild(st);

btnBack.addEventListener('click', (e) => {
  if (soalIdx > 0 && soalIdx < TOTAL) {
    e.preventDefault();
    if (confirm('Yakin mau keluar? Ujian yang belum selesai akan dianggap gugur dan tidak bisa diulang.')) {
      window.location.href = btnBack.href;
    }
  }
});
</script>

</body>
</html>