<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/hasil.php — Halaman Hasil Kuis + Animasi
// ============================================================
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['siswa_id'])) {
    header('Location: /SAINTARA/login.php'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$_SESSION['siswa_id']]);
$siswa = $stmt->fetch();
if (!$siswa) { session_destroy(); header('Location: /SAINTARA/login.php'); exit; }

// Ambil data hasil dari query string
$benar     = (int)($_GET['benar']     ?? 0);
$salah     = (int)($_GET['salah']     ?? 0);
$xp        = (int)($_GET['xp']        ?? 0);
$total     = (int)($_GET['total']     ?? 0);
$topikId   = (int)($_GET['topik']     ?? 0);
$kelas     = (int)($_GET['kelas']     ?? $siswa['kelas']);
$levelNaik = ($_GET['level_naik'] ?? '0') === '1';
$levelBaru = htmlspecialchars($_GET['level_baru'] ?? '');
$badgesRaw = $_GET['badges'] ?? '[]';
$badges    = json_decode($badgesRaw, true) ?: [];

// Hitung skor persen
$skorPct   = $total > 0 ? round(($benar / $total) * 100) : 0;
$xpBaru    = $siswa['xp']; // sudah diupdate oleh api/quiz.php
$levelName = getLevelName($siswa['level']);
$levelProg = getLevelProgress($siswa['xp']);

$hasil = $_SESSION['flash_hasil_kuis'] ?? ['benar' => 0, 'salah' => 0, 'xp_didapat' => 0];
unset($_SESSION['flash_hasil_kuis']); // Hapus setelah ditampilkan
// Gunakan $hasil['benar'] untuk ditampilkan di HTML

// Pesan motivasi berdasarkan skor
if ($skorPct >= 90)     { $pesan = 'Luar Biasa!';    $submood = 'Kamu hampir sempurna!'; }
elseif ($skorPct >= 70) { $pesan = 'Bagus Sekali!';  $submood = 'Terus tingkatkan ya!'; }
elseif ($skorPct >= 50) { $pesan = 'Lumayan!';       $submood = 'Setengah jalan, terus semangat!'; }
else                    { $pesan = 'Jangan Menyerah!';$submood = 'Coba lagi, kamu pasti bisa!'; }

// Topik info
$topik = null;
if ($topikId) {
    $stmt = $pdo->prepare("SELECT * FROM topik WHERE id = ?");
    $stmt->execute([$topikId]);
    $topik = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title>Hasil Kuis — Saintara</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root {
      --primary:#4DA8DA; --mint:#80D8C3; --yellow:#FFD66B;
      --bg:#F0F8FF; --white:#fff; --navy:#1A2E3A; --muted:#6B8899;
      --fh:'Fredoka',sans-serif; --fb:'Nunito',sans-serif;
    }
    html,body { font-family:var(--fb); background:var(--bg); color:var(--navy); -webkit-font-smoothing:antialiased; overflow-x:hidden; }
    a { text-decoration:none; color:inherit; }
    .page { max-width:480px; margin:0 auto; min-height:100vh; background:var(--bg); }
    .scroll-area { overflow-y:auto; padding-bottom:40px; }

    /* ── HERO HASIL ─────────────────────────────────────────── */
    .hero-hasil {
      background:linear-gradient(155deg,#3A90C2 0%,#4DA8DA 55%,#6EC5E0 100%);
      padding:28px 20px 70px; text-align:center;
      position:relative; overflow:hidden;
    }
    .hh-geo { position:absolute; opacity:0.07; pointer-events:none; border-radius:50%; }
    .hhg1 { width:180px;height:180px;border:3px solid white;top:-60px;right:-50px; }
    .hhg2 { width:90px;height:90px;background:white;bottom:10px;left:-25px; }

    /* Ella hasil */
    .ella-hasil {
      position:relative; z-index:2;
      display:inline-block; margin-bottom:8px;
      animation:ellaResultIn 0.7s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    @keyframes ellaResultIn {
      from{ transform:scale(0) rotate(-20deg); opacity:0; }
      to  { transform:scale(1) rotate(0); opacity:1; }
    }
    .ella-hasil svg { width:90px; height:90px; filter:drop-shadow(0 6px 16px rgba(26,46,58,0.22)); }

    .hasil-pesan {
      font-family:var(--fh); font-size:1.9rem; font-weight:700;
      color:white; position:relative; z-index:2;
      animation:fadeUp 0.5s ease 0.3s both;
    }
    .hasil-sub {
      font-size:0.85rem; color:rgba(255,255,255,0.82);
      position:relative; z-index:2;
      animation:fadeUp 0.5s ease 0.4s both; margin-top:4px;
    }
    .hasil-topik {
      font-family:var(--fh); font-size:0.82rem; font-weight:600;
      color:rgba(255,255,255,0.7); margin-top:6px;
      position:relative; z-index:2;
    }

    .wave-btm { position:absolute; bottom:-1px; left:0; right:0; }

    @keyframes fadeUp {
      from{ opacity:0; transform:translateY(12px); }
      to  { opacity:1; transform:translateY(0); }
    }

    /* ── SKOR DONUT ─────────────────────────────────────────── */
    .skor-wrap {
      display:flex; justify-content:center;
      margin:-36px 0 0; position:relative; z-index:10;
    }
    .skor-circle {
      width:100px; height:100px; position:relative;
      animation:scoreIn 0.6s cubic-bezier(0.34,1.56,0.64,1) 0.3s both;
    }
    @keyframes scoreIn {
      from{ transform:scale(0); }
      to  { transform:scale(1); }
    }
    .skor-circle svg { width:100px; height:100px; transform:rotate(-90deg); }
    .skor-bg   { fill:none; stroke:#E8F4FA; stroke-width:8; }
    .skor-fill { fill:none; stroke:var(--yellow); stroke-width:8; stroke-linecap:round;
                  stroke-dasharray:251.2; stroke-dashoffset:251.2;
                  transition:stroke-dashoffset 1.2s cubic-bezier(0.4,0,0.2,1) 0.5s; }
    .skor-text {
      position:absolute; inset:0; display:flex;
      flex-direction:column; align-items:center; justify-content:center;
    }
    .skor-pct {
      font-family:var(--fh); font-size:1.5rem; font-weight:700; color:var(--navy); line-height:1;
    }
    .skor-lbl {
      font-size:0.6rem; font-weight:800; color:var(--muted); text-transform:uppercase;
    }

    /* ── STAT CARDS ─────────────────────────────────────────── */
    .stat-row {
      display:grid; grid-template-columns:repeat(3,1fr);
      gap:10px; padding:16px 16px 0;
    }
    .stat-card {
      background:white; border-radius:16px; padding:14px 10px;
      display:flex; flex-direction:column; align-items:center; gap:5px;
      box-shadow:0 2px 10px rgba(26,46,58,0.08);
      border:1.5px solid rgba(77,168,218,0.08);
      animation:statIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    .stat-card:nth-child(1){ animation-delay:0.4s; }
    .stat-card:nth-child(2){ animation-delay:0.5s; }
    .stat-card:nth-child(3){ animation-delay:0.6s; }

    @keyframes statIn {
      from{ opacity:0; transform:translateY(16px) scale(0.9); }
      to  { opacity:1; transform:translateY(0) scale(1); }
    }
    .stat-icon {
      width:36px; height:36px; border-radius:10px;
      display:flex; align-items:center; justify-content:center; font-size:1.1rem;
    }
    .si-green  { background:#E1F5EE; color:#0F7A5A; }
    .si-red    { background:#FFE8E8; color:#C04040; }
    .si-yellow { background:#FFF8E6; color:#C07A00; }
    .stat-num {
      font-family:var(--fh); font-size:1.5rem; font-weight:700; color:var(--navy); line-height:1;
    }
    .stat-lbl { font-size:0.68rem; font-weight:800; color:var(--muted); text-align:center; }

    /* ── XP GAINED ──────────────────────────────────────────── */
    .xp-gained-card {
      margin:14px 16px 0;
      background:linear-gradient(135deg,#FFF8E6,#FFF0C0);
      border:2px solid var(--yellow); border-radius:18px;
      padding:16px 20px; display:flex; align-items:center; gap:14px;
      box-shadow:0 4px 16px rgba(255,214,107,0.3);
      animation:xpCardIn 0.6s cubic-bezier(0.34,1.56,0.64,1) 0.7s both;
    }
    @keyframes xpCardIn {
      from{ opacity:0; transform:scale(0.85) rotate(-3deg); }
      to  { opacity:1; transform:scale(1) rotate(0); }
    }
    .xp-icon {
      width:52px; height:52px; border-radius:14px;
      background:var(--yellow); display:flex; align-items:center;
      justify-content:center; font-size:1.5rem; flex-shrink:0;
    }
    .xp-info { flex:1; }
    .xp-label { font-size:0.72rem; font-weight:800; color:#7A5C00; text-transform:uppercase; letter-spacing:0.5px; }
    .xp-num   { font-family:var(--fh); font-size:1.8rem; font-weight:700; color:var(--navy); line-height:1; margin-top:2px; }
    .xp-sub   { font-size:0.72rem; color:#8A6C00; font-weight:600; margin-top:1px; }

    /* ── LEVEL UP ────────────────────────────────────────────── */
    .level-up-card {
      margin:10px 16px 0;
      background:linear-gradient(135deg,#4DA8DA,#3A90C2);
      border-radius:18px; padding:16px 20px;
      display:flex; align-items:center; gap:14px;
      box-shadow:0 6px 20px rgba(77,168,218,0.4);
      animation:levelUpIn 0.7s cubic-bezier(0.34,1.56,0.64,1) 0.8s both;
    }
    @keyframes levelUpIn {
      from{ opacity:0; transform:translateX(-20px) scale(0.9); }
      to  { opacity:1; transform:translateX(0) scale(1); }
    }
    .lu-icon {
      width:52px; height:52px; border-radius:14px;
      background:rgba(255,255,255,0.2); border:2px solid rgba(255,255,255,0.3);
      display:flex; align-items:center; justify-content:center;
      font-size:1.5rem; flex-shrink:0;
    }
    .lu-info { flex:1; }
    .lu-label { font-family:var(--fh); font-size:0.75rem; font-weight:700; color:rgba(255,255,255,0.75); }
    .lu-name  { font-family:var(--fh); font-size:1.1rem; font-weight:700; color:white; }

    /* ── BADGE UNLOCK ────────────────────────────────────────── */
    .badge-section { padding:14px 16px 0; }
    .badge-section-title {
      font-family:var(--fh); font-size:0.9rem; font-weight:700;
      color:var(--navy); margin-bottom:10px;
      display:flex; align-items:center; gap:6px;
    }
    .badge-section-title i { color:var(--yellow); }

    .badge-unlock-card {
      background:linear-gradient(135deg,#FFF8E6,#FFEDAA);
      border:2px solid var(--yellow); border-radius:16px;
      padding:12px 16px; display:flex; align-items:center; gap:12px;
      margin-bottom:8px;
      animation:badgeIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    @keyframes badgeIn {
      from{ opacity:0; transform:scale(0.7) rotate(-10deg); }
      to  { opacity:1; transform:scale(1) rotate(0); }
    }
    .badge-unlock-icon {
      width:44px; height:44px; border-radius:50%;
      background:var(--yellow); display:flex; align-items:center;
      justify-content:center; font-size:1.3rem; flex-shrink:0;
      box-shadow:0 3px 10px rgba(255,214,107,0.5);
    }
    .badge-unlock-info { flex:1; }
    .badge-unlock-label { font-size:0.68rem; font-weight:800; color:#7A5C00; text-transform:uppercase; }
    .badge-unlock-name  { font-family:var(--fh); font-size:0.95rem; font-weight:700; color:var(--navy); }

    /* ── TOMBOL AKSI ─────────────────────────────────────────── */
    .aksi-row { padding:16px; display:flex; flex-direction:column; gap:10px; }

    .btn-coba-lagi {
      display:flex; align-items:center; justify-content:center; gap:10px;
      padding:15px; border:none; border-radius:999px;
      background:linear-gradient(135deg,#4DA8DA,#3A90C2);
      color:white; font-family:var(--fh); font-size:1rem; font-weight:700;
      cursor:pointer; box-shadow:0 6px 18px rgba(77,168,218,0.38);
      transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1);
      text-decoration:none;
    }
    .btn-coba-lagi:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(77,168,218,0.48); }
    .btn-coba-lagi:active { transform:scale(0.97); }
    .btn-coba-lagi i { font-size:1.1rem; }

    .btn-materi, .btn-beranda {
      display:flex; align-items:center; justify-content:center; gap:10px;
      padding:13px; border-radius:999px;
      font-family:var(--fh); font-size:0.95rem; font-weight:700;
      cursor:pointer; transition:all 0.18s; text-decoration:none;
    }
    .btn-materi {
      background:white; color:var(--navy);
      border:2px solid rgba(77,168,218,0.2);
      box-shadow:0 2px 10px rgba(26,46,58,0.07);
    }
    .btn-materi:hover { border-color:var(--primary); background:#EBF6FC; }
    .btn-beranda { background:transparent; color:var(--muted); border:none; }
    .btn-beranda:hover { color:var(--primary); }

    /* ── CONFETTI ────────────────────────────────────────────── */
    .confetti-container { position:fixed; inset:0; pointer-events:none; z-index:999; overflow:hidden; }
    .confetti-piece {
      position:absolute; width:8px; height:10px;
      border-radius:2px; animation:confettiFall linear forwards;
    }
    @keyframes confettiFall {
      0%  { transform:translateY(-20px) rotate(0deg); opacity:1; }
      100%{ transform:translateY(100vh) rotate(720deg); opacity:0; }
    }
  </style>
</head>
<body>
<div class="page">
<div class="scroll-area">

  <!-- CONFETTI CONTAINER -->
  <div class="confetti-container" id="confettiContainer"></div>

  <!-- HERO HASIL -->
  <div class="hero-hasil">
    <div class="hh-geo hhg1"></div>
    <div class="hh-geo hhg2"></div>

    <!-- Ella Hasil -->
    <div class="ella-hasil">
      <svg viewBox="0 0 90 90" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="17" cy="52" rx="13" ry="17" fill="#3A90C2"/>
        <ellipse cx="17" cy="52" rx="8"  ry="11" fill="#80D8C3"/>
        <ellipse cx="73" cy="52" rx="13" ry="17" fill="#3A90C2"/>
        <ellipse cx="73" cy="52" rx="8"  ry="11" fill="#80D8C3"/>
        <circle cx="45" cy="42" r="28" fill="#4DA8DA"/>
        <!-- Ekspresi sesuai skor -->
        <?php if ($skorPct >= 70): ?>
        <!-- Senang -->
        <ellipse cx="35" cy="37" rx="6" ry="7" fill="white"/>
        <circle  cx="36" cy="38" r="4" fill="#1A2E3A"/>
        <circle  cx="37.5" cy="37" r="1.3" fill="white"/>
        <ellipse cx="55" cy="37" rx="6" ry="7" fill="white"/>
        <circle  cx="56" cy="38" r="4" fill="#1A2E3A"/>
        <circle  cx="57.5" cy="37" r="1.3" fill="white"/>
        <ellipse cx="28" cy="47" rx="5.5" ry="3.5" fill="#FFD66B" fill-opacity="0.85"/>
        <ellipse cx="62" cy="47" rx="5.5" ry="3.5" fill="#FFD66B" fill-opacity="0.85"/>
        <path d="M34 49 Q45 58 56 49" stroke="#1A2E3A" stroke-width="2.2" stroke-linecap="round" fill="none"/>
        <?php else: ?>
        <!-- Semangat meski salah -->
        <ellipse cx="35" cy="37" rx="6" ry="7" fill="white"/>
        <circle  cx="36" cy="38" r="4" fill="#1A2E3A"/>
        <circle  cx="37.5" cy="37" r="1.3" fill="white"/>
        <ellipse cx="55" cy="37" rx="6" ry="7" fill="white"/>
        <circle  cx="56" cy="38" r="4" fill="#1A2E3A"/>
        <circle  cx="57.5" cy="37" r="1.3" fill="white"/>
        <ellipse cx="28" cy="47" rx="5.5" ry="3.5" fill="#FFD66B" fill-opacity="0.4"/>
        <ellipse cx="62" cy="47" rx="5.5" ry="3.5" fill="#FFD66B" fill-opacity="0.4"/>
        <path d="M36 52 Q45 47 54 52" stroke="#1A2E3A" stroke-width="2" stroke-linecap="round" fill="none"/>
        <?php endif; ?>
        <path d="M32 50 Q24 54 23 62 Q22 68 28 69" stroke="#3A90C2" stroke-width="5.5" stroke-linecap="round" fill="none"/>
        <circle cx="28" cy="69" r="4" fill="#3A90C2"/>
      </svg>
    </div>

    <div class="hasil-pesan"><?= $pesan ?></div>
    <div class="hasil-sub"><?= $submood ?></div>
    <?php if ($topik): ?>
    <div class="hasil-topik"><i class="bi bi-book-fill"></i> <?= htmlspecialchars($topik['judul']) ?></div>
    <?php endif; ?>

    <svg class="wave-btm" viewBox="0 0 1440 48" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,24 C360,48 1080,0 1440,24 L1440,48 L0,48 Z" fill="#F0F8FF"/>
    </svg>
  </div>

  <!-- SKOR DONUT -->
  <div class="skor-wrap">
    <div class="skor-circle">
      <svg viewBox="0 0 100 100">
        <circle class="skor-bg"   cx="50" cy="50" r="40"/>
        <circle class="skor-fill" cx="50" cy="50" r="40" id="skorFill"/>
      </svg>
      <div class="skor-text">
        <div class="skor-pct" id="skorPct">0%</div>
        <div class="skor-lbl">Skor</div>
      </div>
    </div>
  </div>

  <!-- STAT ROW -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
      <div class="stat-num" style="color:#0F7A5A"><?= $benar ?></div>
      <div class="stat-lbl">Benar</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-red"><i class="bi bi-x-circle-fill"></i></div>
      <div class="stat-num" style="color:#C04040"><?= $salah ?></div>
      <div class="stat-lbl">Salah</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon si-yellow"><i class="bi bi-collection-fill"></i></div>
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-lbl">Total Soal</div>
    </div>
  </div>

  <!-- XP GAINED -->
  <div class="xp-gained-card">
    <div class="xp-icon"><i class="bi bi-star-fill" style="color:#5A3E00"></i></div>
    <div class="xp-info">
      <div class="xp-label">XP Didapat</div>
      <div class="xp-num">+<?= $xp ?> XP</div>
      <div class="xp-sub">Total XP kamu: <?= number_format($siswa['xp']) ?> XP</div>
    </div>
    <div style="font-family:'Fredoka',sans-serif;font-size:1.5rem;font-weight:700;color:#C07A00">
      Level <?= $siswa['level'] ?>
    </div>
  </div>

  <!-- LEVEL UP (jika naik level) -->
  <?php if ($levelNaik): ?>
  <div class="level-up-card">
    <div class="lu-icon"><i class="bi bi-arrow-up-circle-fill" style="color:var(--yellow)"></i></div>
    <div class="lu-info">
      <div class="lu-label">Level Naik!</div>
      <div class="lu-name"><?= htmlspecialchars(getLevelName($siswa['level'])) ?></div>
    </div>
    <i class="bi bi-patch-check-fill" style="font-size:1.8rem;color:var(--yellow)"></i>
  </div>
  <?php endif; ?>

  <!-- BADGE UNLOCK -->
  <?php if (!empty($badges)): ?>
  <div class="badge-section">
    <div class="badge-section-title">
      <i class="bi bi-patch-star-fill"></i>
      Badge Baru Terbuka!
    </div>
    <?php foreach ($badges as $idx => $b): ?>
    <div class="badge-unlock-card" style="animation-delay:<?= 0.1 * $idx ?>s">
      <div class="badge-unlock-icon"><?= htmlspecialchars($b['icon'] ?? '🏅') ?></div>
      <div class="badge-unlock-info">
        <div class="badge-unlock-label">Badge Baru</div>
        <div class="badge-unlock-name"><?= htmlspecialchars($b['nama'] ?? '') ?></div>
      </div>
      <i class="bi bi-check-circle-fill" style="font-size:1.2rem;color:#0F7A5A"></i>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- TOMBOL AKSI -->
  <div class="aksi-row">
    <?php if ($topikId): ?>
    <a href="/SAINTARA/pages/kuis.php?topik=<?= $topikId ?>&kelas=<?= $kelas ?>" class="btn-coba-lagi">
      <i class="bi bi-arrow-repeat"></i> Coba Lagi
    </a>
    <?php endif; ?>
    <a href="/SAINTARA/pages/materi.php?kelas=<?= $kelas ?>" class="btn-materi">
      <i class="bi bi-book-half"></i> Kembali ke Materi
    </a>
    <a href="/SAINTARA/pages/beranda.php" class="btn-beranda">
      <i class="bi bi-house-door"></i> Ke Beranda
    </a>
  </div>

</div><!-- /.scroll-area -->
</div><!-- /.page -->

<script>
  const SKOR_PCT = <?= $skorPct ?>;
  const IS_GOOD  = SKOR_PCT >= 70;

  // Animasi donut skor
  window.addEventListener('load', () => {
    // Donut
    const fill = document.getElementById('skorFill');
    const circumference = 2 * Math.PI * 40;
    setTimeout(() => {
      const offset = circumference - (SKOR_PCT / 100) * circumference;
      fill.style.strokeDashoffset = offset;
    }, 400);

    // Counter skor persen
    const pctEl = document.getElementById('skorPct');
    let current = 0;
    const interval = setInterval(() => {
      current = Math.min(current + 2, SKOR_PCT);
      pctEl.textContent = current + '%';
      if (current >= SKOR_PCT) clearInterval(interval);
    }, 20);

    // Confetti jika skor bagus
    if (IS_GOOD) {
      setTimeout(spawnConfetti, 600);
    }
  });

  // Confetti
  function spawnConfetti() {
    const container = document.getElementById('confettiContainer');
    const colors = ['#4DA8DA','#80D8C3','#FFD66B','#FF7F7F','#A8E6CF','#FFB347'];
    for (let i = 0; i < 60; i++) {
      setTimeout(() => {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        el.style.left    = Math.random() * 100 + 'vw';
        el.style.background = colors[Math.floor(Math.random() * colors.length)];
        el.style.animationDuration = (2 + Math.random() * 2) + 's';
        el.style.animationDelay   = Math.random() * 0.5 + 's';
        el.style.width  = (6 + Math.random() * 6) + 'px';
        el.style.height = (8 + Math.random() * 8) + 'px';
        el.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        container.appendChild(el);
        setTimeout(() => el.remove(), 4000);
      }, i * 40);
    }
  }
</script>
</body>
</html>