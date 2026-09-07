<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/materi-detail.php — Halaman Baca Materi (Dynamic DB)
// ============================================================
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['siswa_id'])) {
    header('Location: /SAINTARA/login.php'); exit;
}

$pdo = getDB(); // Pastikan menggunakan variabel koneksi $pdo yang benar

$topik_id = (int)($_GET['id'] ?? 0);
$tipe = $_GET['tipe'] ?? ''; 

// 1. Validasi Akses Kelas
$stmtSiswa = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmtSiswa->execute([$_SESSION['siswa_id']]);
$siswa = $stmtSiswa->fetch();

if (!$siswa) { session_destroy(); header('Location: /SAINTARA/login.php'); exit; }
$kelas_siswa = (int)($siswa['kelas'] ?? 1);

// 2. Validasi Topik
if ($topik_id > 0 && $tipe !== 'harian') {
    $stmtTopik = $pdo->prepare("SELECT * FROM topik WHERE id = ?");
    $stmtTopik->execute([$topik_id]);
    $topik = $stmtTopik->fetch();

    if ($topik) {
        if ((int)$topik['kelas'] !== $kelas_siswa) {
            header("Location: /SAINTARA/pages/beranda.php?error=akses_ditolak_beda_kelas"); exit;
        }
    } else {
        header("Location: /SAINTARA/pages/beranda.php?error=topik_tidak_valid"); exit;
    }
} else {
    header('Location: /SAINTARA/pages/materi.php'); exit;
}

// 3. Cek Progress Topik Ini
$stmtProg = $pdo->prepare("SELECT * FROM progress WHERE siswa_id = ? AND topik_id = ?");
$stmtProg->execute([$siswa['id'], $topik_id]);
$progress = $stmtProg->fetch();
$sudahSelesai = $progress && $progress['selesai'];

// 4. Hitung Jumlah Soal Kuis Tersedia
$stmtSoal = $pdo->prepare("SELECT COUNT(*) FROM soal WHERE topik_id = ?");
$stmtSoal->execute([$topik_id]);
$totalSoalTersedia = (int)$stmtSoal->fetchColumn();

// Terapkan limit berjenjang sesuai kelas (harus SINKRON dengan pages/kuis.php)
$limitSoalBiasa = 5; // Kelas 1 & 2
if ($kelas_siswa == 3 || $kelas_siswa == 4) $limitSoalBiasa = 10;
if ($kelas_siswa == 5 || $kelas_siswa == 6) $limitSoalBiasa = 15;

// Jumlah yang ditampilkan = jumlah yang BENERAN akan dipakai saat kuis dimulai
$jumlahSoal = min($totalSoalTersedia, $limitSoalBiasa);

// ============================================================
// 5. AMBIL KONTEN MATERI DARI DATABASE (Tabel: materi_konten)
// ============================================================
$stmtKonten = $pdo->prepare("SELECT * FROM materi_konten WHERE topik_id = ? ORDER BY urutan ASC");
$stmtKonten->execute([$topik_id]);
$materiData = $stmtKonten->fetchAll(PDO::FETCH_ASSOC);

$konten = [
    'intro' => '',
    'sections' => [],
    'fun_fact' => ''
];

// Warna CSS yang tersedia agar tampilan tetap cantik
$cssClasses = ['sec-blue', 'sec-green', 'sec-yellow', 'sec-mint', 'sec-pink'];
$classIdx = 0;

foreach ($materiData as $row) {
    if ($row['tipe'] === 'intro') {
        $konten['intro'] = $row['isi'];
    } elseif ($row['tipe'] === 'fakta') {
        $konten['fun_fact'] = $row['isi'];
    } elseif ($row['tipe'] === 'seksi') {
        $konten['sections'][] = [
            'judul' => $row['judul'],
            'icon'  => $row['icon'] ?: 'bi-book-fill',
            'warna' => $cssClasses[$classIdx % 5],
            'isi'   => $row['isi']
        ];
        $classIdx++;
    }
}

// 6. FALLBACK JIKA MATERI DI DATABASE KOSONG / BELUM DIBUAT
if (empty($materiData)) {
    $konten['intro'] = $topik['deskripsi'] ?? 'Materi ini sedang disiapkan oleh guru. Silakan datang lagi nanti!';
    $konten['sections'][] = [
        'judul' => 'Pengantar Materi',
        'icon'  => 'bi-book-fill',
        'warna' => 'sec-blue',
        'isi'   => 'Guru sedang menyiapkan konten materi ini. Sementara itu, kamu bisa mencoba mengerjakan kuis untuk topik ini di bawah!'
    ];
}

// Pastikan selalu ada teks jika fun fact atau intro tidak diisi di DB
if (empty($konten['fun_fact'])) $konten['fun_fact'] = 'Terus semangat belajar ya! Ilmu pengetahuan itu seru banget!';
if (empty($konten['intro']))    $konten['intro']    = 'Ayo pelajari materi ' . htmlspecialchars($topik['judul']) . ' bersama-sama!';

$xpReward = getXpReward($topik['kelas']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title><?= htmlspecialchars($topik['judul']) ?> — Saintara</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root {
      --primary:#4DA8DA; --primary-dk:#3590C2; --mint:#80D8C3;
      --yellow:#FFD66B;  --bg:#F0F8FF; --white:#fff;
      --navy:#1A2E3A;    --muted:#6B8899;
      --fh:'Fredoka',sans-serif; --fb:'Nunito',sans-serif;
    }
    html,body { font-family:var(--fb); background:var(--bg); color:var(--navy); -webkit-font-smoothing:antialiased; }
    a { text-decoration:none; color:inherit; }
    .page { max-width:480px; margin:0 auto; min-height:100vh; background:var(--bg); }
    .scroll-area { overflow-y:auto; padding-bottom:100px; }

    /* TOP BAR */
    .top-bar {
      background:linear-gradient(155deg,#3A90C2 0%,#4DA8DA 55%,#6EC5E0 100%);
      padding:20px 20px 28px; position:relative; overflow:hidden;
    }
    .tbg { position:absolute; opacity:0.08; pointer-events:none; }
    .tbg1 { width:140px;height:140px;border:3px solid white;border-radius:50%;top:-50px;right:-30px; }
    .tbg2 { width:70px;height:70px;background:white;border-radius:50%;bottom:5px;left:-20px; }
    .tb-row {
      display:flex; align-items:center; gap:12px;
      position:relative; z-index:2;
    }
    .tb-back {
      width:40px; height:40px; border-radius:50%;
      background:rgba(255,255,255,0.18); border:1.5px solid rgba(255,255,255,0.3);
      display:flex; align-items:center; justify-content:center;
      cursor:pointer; color:white; font-size:1.1rem; flex-shrink:0;
      transition:background 0.18s;
    }
    .tb-back:hover { background:rgba(255,255,255,0.3); }
    .tb-info { flex:1; }
    .tb-kelas {
      font-size:0.72rem; color:rgba(255,255,255,0.72); font-weight:600;
      display:flex; align-items:center; gap:4px; margin-bottom:2px;
    }
    .tb-kelas i { font-size:0.85rem; }
    .tb-topik-title {
      font-family:var(--fh); font-size:1.15rem; font-weight:700; color:white; line-height:1.2;
    }
    .tb-done-badge {
      display:inline-flex; align-items:center; gap:4px;
      background:rgba(255,255,255,0.18); border:1.5px solid rgba(255,255,255,0.3);
      border-radius:999px; padding:4px 10px;
      font-size:0.72rem; font-weight:700; color:var(--yellow); flex-shrink:0;
    }

    /* ILUSTRASI ANIMASI */
    .ilustrasi-wrap {
      margin:0 16px; margin-top:16px;
      border-radius:20px; overflow:hidden;
      background:linear-gradient(145deg,#4DA8DA 0%,#80D8C3 100%);
      height:200px; position:relative;
      box-shadow:0 6px 20px rgba(77,168,218,0.28);
    }
    .anim-canvas {
      width:100%; height:100%; display:flex; align-items:center; justify-content:center;
      position:relative; overflow:hidden;
    }
    .anim-icon-main {
      font-size:5rem; color:rgba(255,255,255,0.9);
      animation:iconFloat 3s ease-in-out infinite;
      filter:drop-shadow(0 4px 12px rgba(26,46,58,0.2));
      position:relative; z-index:2;
    }
    @keyframes iconFloat {
      0%,100%{ transform:translateY(0) rotate(-3deg); }
      50%    { transform:translateY(-12px) rotate(3deg); }
    }
    .anim-particle {
      position:absolute; border-radius:50%; background:rgba(255,255,255,0.18);
      animation:particleFloat 4s ease-in-out infinite;
    }
    @keyframes particleFloat {
      0%,100%{ transform:translateY(0) scale(1); opacity:0.5; }
      50%    { transform:translateY(-15px) scale(1.1); opacity:0.8; }
    }
    .anim-star { position:absolute; color:rgba(255,214,107,0.7); animation:starAnim 2s ease-in-out infinite; }
    @keyframes starAnim {
      0%,100%{ transform:scale(0.8) rotate(0deg); opacity:0.5; }
      50%    { transform:scale(1.3) rotate(20deg); opacity:1; }
    }
    .ilustrasi-label {
      position:absolute; bottom:12px; left:0; right:0; text-align:center;
      font-family:var(--fh); font-size:0.85rem; font-weight:700;
      color:rgba(255,255,255,0.85); letter-spacing:0.2px;
    }
    .btn-tanya-ella {
      position:absolute; top:12px; right:12px; display:inline-flex; align-items:center; gap:6px;
      background:rgba(255,255,255,0.22); border:1.5px solid rgba(255,255,255,0.38);
      border-radius:999px; padding:5px 12px; font-family:var(--fh); font-size:0.75rem; font-weight:700; color:white;
      cursor:pointer; backdrop-filter:blur(4px); transition:background 0.18s; z-index: 10;
    }
    .btn-tanya-ella:hover { background:rgba(255,255,255,0.32); }

    /* KONTEN MATERI */
    .content-wrap { padding:20px 16px 0; }
    .intro-card {
      background:white; border-radius:18px; padding:18px; box-shadow:0 2px 10px rgba(26,46,58,0.07);
      border-left:4px solid var(--primary); margin-bottom:16px;
    }
    .intro-card p { font-size:0.9rem; line-height:1.65; color:var(--navy); font-weight:500; }

    .section-card {
      background:white; border-radius:18px; margin-bottom:12px; overflow:hidden;
      box-shadow:0 2px 10px rgba(26,46,58,0.07); border:1.5px solid rgba(77,168,218,0.08); transition:box-shadow 0.18s;
    }
    .section-card:hover { box-shadow:0 4px 16px rgba(77,168,218,0.15); }
    .sec-header { display:flex; align-items:center; gap:12px; padding:14px 16px; cursor:pointer; transition:background 0.15s; }
    .sec-header:hover { background:#F8FCFF; }
    .sec-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
    .sec-blue  { background:#EBF6FC; color:#4DA8DA; }
    .sec-green { background:#E1F5EE; color:#0F7A5A; }
    .sec-yellow{ background:#FFF8E6; color:#C07A00; }
    .sec-mint  { background:#E0FFF8; color:#0D7A6E; }
    .sec-pink  { background:#FFE8E8; color:#C04040; }
    .sec-title { font-family:var(--fh); font-size:0.92rem; font-weight:700; color:var(--navy); flex:1; }
    .sec-chevron { font-size:1.1rem; color:var(--muted); transition:transform 0.25s; flex-shrink:0; }
    .section-card.open .sec-chevron { transform:rotate(180deg); }
    .sec-body { padding:0 16px; max-height:0; overflow:hidden; transition:max-height 0.35s cubic-bezier(0.4,0,0.2,1), padding 0.25s; }
    .section-card.open .sec-body { max-height:500px; padding:0 16px 16px; }
    .sec-body p { font-size:0.88rem; line-height:1.65; color:#3A5060; font-weight:500; border-top:1px solid rgba(77,168,218,0.1); padding-top:12px; }

    /* Fun fact */
    .fun-fact {
      background:linear-gradient(135deg,#FFF8E6,#FFF0C8); border:2px solid var(--yellow); border-radius:18px;
      padding:16px 18px; margin-bottom:16px; display:flex; gap:12px; align-items:flex-start;
    }
    .fun-fact-icon { width:40px; height:40px; border-radius:12px; background:var(--yellow); display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
    .fun-fact-text { font-size:0.85rem; font-weight:600; line-height:1.6; color:#5A3E00; }
    .fun-fact-label { font-family:var(--fh); font-size:0.72rem; font-weight:700; color:#8A6000; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }

    /* Tombol Kuis */
    .kuis-cta {
      background:linear-gradient(135deg,#4DA8DA,#3A90C2); border-radius:18px; padding:20px; display:flex; align-items:center; gap:14px;
      margin-bottom:16px; cursor:pointer; text-decoration:none; box-shadow:0 6px 20px rgba(77,168,218,0.35); transition:transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.18s;
    }
    .kuis-cta:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(77,168,218,0.45); }
    .kuis-cta-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,0.2); border:1.5px solid rgba(255,255,255,0.3); display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:white; flex-shrink:0; }
    .kuis-cta-info { flex:1; }
    .kuis-cta-title { font-family:var(--fh); font-size:1rem; font-weight:700; color:white; }
    .kuis-cta-sub { font-size:0.75rem; color:rgba(255,255,255,0.78); margin-top:2px; }
    .kuis-cta-xp { font-family:var(--fh); font-size:1.1rem; font-weight:700; color:var(--yellow); flex-shrink:0; }

    /* Tombol Pre-Test / Post-Test */
    .tes-cta-row { display:flex; gap:10px; margin-bottom:16px; }
    .tes-cta { flex:1; background:white; border:2px solid rgba(77,168,218,0.2); border-radius:16px; padding:14px 12px; display:flex; flex-direction:column; align-items:center; gap:6px; text-decoration:none; text-align:center; box-shadow:0 2px 10px rgba(26,46,58,0.06); transition:transform 0.18s cubic-bezier(0.34,1.56,0.64,1), border-color 0.18s; }
    .tes-cta:hover { transform:translateY(-2px); border-color:var(--primary); }
    .tes-cta-icon { font-size:1.4rem; color:var(--primary); }
    .tes-cta-title { font-family:var(--fh); font-size:0.85rem; font-weight:700; color:var(--navy); }
    .tes-cta-sub { font-size:0.68rem; color:var(--muted); }
    .tes-cta.posttest .tes-cta-icon { color:#0F7A5A; }
    .tes-cta.posttest { border-color:rgba(15,122,90,0.2); }
    .tes-cta.posttest:hover { border-color:#0F7A5A; }

    /* ELLA CHAT PANEL */
    .ella-panel { position:fixed; bottom:20px; left:16px; right:16px; max-width:calc(480px - 32px); margin:0 auto; background:white; border-radius:20px; box-shadow:0 12px 40px rgba(26,46,58,0.18); border:1.5px solid rgba(77,168,218,0.15); z-index:300; overflow:hidden; transform:translateY(120%) scale(0.95); transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1); }
    .ella-panel.open { transform:translateY(0) scale(1); }
    .ella-panel-header { background:linear-gradient(135deg,#4DA8DA,#3A90C2); padding:12px 16px; display:flex; align-items:center; gap:10px; }
    .ella-ph-avatar { width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,0.2); border:2px solid rgba(255,255,255,0.4); display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
    .ella-ph-name { font-family:var(--fh); font-size:0.95rem; font-weight:700; color:white; flex:1; }
    .ella-ph-close { width:26px; height:26px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; cursor:pointer; color:white; font-size:0.85rem; }
    .ella-chat { height:180px; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:8px; }
    .chat-ella { background:#EBF6FC; border-radius:14px 14px 14px 3px; padding:8px 12px; font-size:0.83rem; font-weight:600; color:var(--navy); max-width:85%; align-self:flex-start; line-height:1.45; animation:msgIn 0.2s ease; }
    .chat-user { background:var(--primary); border-radius:14px 14px 3px 14px; padding:8px 12px; font-size:0.83rem; font-weight:600; color:white; max-width:85%; align-self:flex-end; line-height:1.45; animation:msgIn 0.2s ease; }
    @keyframes msgIn { from{ opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
    .ella-input-row { display:flex; gap:8px; padding:8px 12px; border-top:1px solid rgba(77,168,218,0.12); }
    .ella-input { flex:1; padding:9px 14px; border:2px solid rgba(77,168,218,0.18); border-radius:999px; font-family:var(--fb); font-size:0.83rem; font-weight:600; color:var(--navy); background:#F7FBFF; outline:none; }
    .ella-input:focus { border-color:var(--primary); background:white; }
    .ella-send { width:36px; height:36px; border-radius:50%; background:var(--primary); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:white; font-size:0.9rem; flex-shrink:0; transition:transform 0.18s; }
    .ella-send:hover { transform:scale(1.1); }
    .overlay { position:fixed; inset:0; background:rgba(26,46,58,0.35); z-index:299; display:none; }
    .overlay.show { display:block; }
    .ella-float { position:fixed; bottom:20px; right:16px; z-index:200; display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
    .ella-tip { background:white; border-radius:14px 14px 0 14px; padding:7px 11px; font-size:0.73rem; font-weight:700; color:var(--navy); box-shadow:0 4px 12px rgba(26,46,58,0.1); border:1px solid rgba(77,168,218,0.15); display:none; white-space:nowrap; }
    .ella-float-btn { width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg,#4DA8DA,#3A90C2); border:3px solid white; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 18px rgba(77,168,218,0.42); cursor:pointer; transition:transform 0.2s cubic-bezier(0.34,1.56,0.64,1); }
    .ella-float-btn:hover { transform:scale(1.1); }
    .ella-float-btn i { font-size:1.4rem; color:white; }

    .fade-in { animation:fadeUp 0.4s ease both; }
    @keyframes fadeUp { from{ opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
    .d1{animation-delay:0.05s} .d2{animation-delay:0.1s} .d3{animation-delay:0.15s} .d4{animation-delay:0.2s}
  </style>
</head>
<body>
<div class="page">
<div class="scroll-area">

  <div class="top-bar fade-in">
    <div class="tbg tbg1"></div>
    <div class="tbg tbg2"></div>
    <div class="tb-row">
      <a href="/SAINTARA/pages/materi.php?kelas=<?= $topik['kelas'] ?>" class="tb-back" aria-label="Kembali">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div class="tb-info">
        <div class="tb-kelas">
          <i class="bi bi-book-fill"></i>
          Kelas <?= $topik['kelas'] ?> — IPA
        </div>
        <div class="tb-topik-title"><?= htmlspecialchars($topik['judul']) ?></div>
      </div>
      <?php if ($sudahSelesai): ?>
      <div class="tb-done-badge">
        <i class="bi bi-check-circle-fill"></i> Selesai
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="ilustrasi-wrap fade-in d1" id="ilustrasiWrap">
    <div class="anim-canvas" id="animCanvas">
      <div class="anim-particle" style="width:80px;height:80px;top:10%;left:5%;animation-delay:0s"></div>
      <div class="anim-particle" style="width:50px;height:50px;top:60%;right:8%;animation-delay:1s"></div>
      <div class="anim-particle" style="width:35px;height:35px;bottom:15%;left:25%;animation-delay:0.5s"></div>
      <i class="bi bi-star-fill anim-star" style="font-size:1rem;top:15%;right:20%;animation-delay:0s"></i>
      <i class="bi bi-star-fill anim-star" style="font-size:0.7rem;top:40%;left:15%;animation-delay:0.8s"></i>
      <i class="bi bi-star-fill anim-star" style="font-size:1.2rem;bottom:20%;right:25%;animation-delay:0.4s"></i>

      <i class="bi <?= htmlspecialchars($topik['icon'] ?: 'bi-book-fill') ?> anim-icon-main" id="animIconMain"></i>
      <div class="ilustrasi-label"><?= htmlspecialchars($topik['judul']) ?></div>
      
      <button class="btn-tanya-ella" onclick="bukaEllaPanel()" type="button" style="display:none" data-hidden-reason="Fitur chat AI belum aktif">
        <i class="bi bi-chat-dots-fill"></i> Tanya Ella
      </button>
    </div>
  </div>

  <div class="content-wrap">

    <div class="intro-card fade-in d2">
      <p><?= htmlspecialchars($konten['intro']) ?></p>
    </div>

    <?php foreach ($konten['sections'] as $idx => $sec): ?>
    <div class="section-card fade-in" style="animation-delay:<?= 0.05 * ($idx + 3) ?>s" onclick="toggleSection(this)">
      <div class="sec-header">
        <div class="sec-icon <?= $sec['warna'] ?>">
          <i class="bi <?= $sec['icon'] ?>"></i>
        </div>
        <div class="sec-title"><?= htmlspecialchars($sec['judul']) ?></div>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="sec-body">
        <p><?= htmlspecialchars($sec['isi']) ?></p>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="fun-fact fade-in d3">
      <div class="fun-fact-icon"><i class="bi bi-lightbulb-fill" style="color:#5A3E00"></i></div>
      <div>
        <div class="fun-fact-label">Tahukah Kamu?</div>
        <div class="fun-fact-text"><?= htmlspecialchars($konten['fun_fact']) ?></div>
      </div>
    </div>

    <div class="tes-cta-row fade-in d4">
      <a href="/SAINTARA/pages/pretest.php?kelas=<?= $topik['kelas'] ?>&topik=<?= $topik_id ?>" class="tes-cta pretest">
        <i class="bi bi-clipboard-check tes-cta-icon"></i>
        <div class="tes-cta-title">Pre-Test</div>
        <div class="tes-cta-sub">Sebelum belajar</div>
      </a>
      <a href="/SAINTARA/pages/posttest.php?kelas=<?= $topik['kelas'] ?>&topik=<?= $topik_id ?>" class="tes-cta posttest">
        <i class="bi bi-clipboard2-check tes-cta-icon"></i>
        <div class="tes-cta-title">Post-Test</div>
        <div class="tes-cta-sub">Setelah belajar</div>
      </a>
    </div>

    <a href="/SAINTARA/pages/kuis.php?id=<?= $topik_id ?>" class="kuis-cta fade-in d4">
      <div class="kuis-cta-icon"><i class="bi bi-pencil-square"></i></div>
      <div class="kuis-cta-info">
        <div class="kuis-cta-title">Mulai Kuis!</div>
        <div class="kuis-cta-sub"><?= $jumlahSoal > 0 ? $jumlahSoal . ' soal' : 'Kuis tersedia' ?> &middot; Uji pemahamanmu</div>
      </div>
      <div class="kuis-cta-xp">+<?= $xpReward ?> XP</div>
    </a>

  </div>
</div>

<div class="overlay" id="overlay" onclick="tutupEllaPanel()"></div>
<div class="ella-panel" id="ellaPanel" role="dialog" aria-label="Chat dengan Ella">
  <div class="ella-panel-header">
    <div class="ella-ph-avatar"><i class="bi bi-chat-heart-fill" style="color:white"></i></div>
    <div class="ella-ph-name">Ella — Asisten Belajar</div>
    <div class="ella-ph-close" onclick="tutupEllaPanel()"><i class="bi bi-x-lg"></i></div>
  </div>
  <div class="ella-chat" id="ellaChat">
    <div class="chat-ella">
      Halo <?= htmlspecialchars(explode(' ',$siswa['nama'])[0]) ?>! Aku Ella. Ada yang ingin kamu tanyakan tentang <strong><?= htmlspecialchars($topik['judul']) ?></strong>?
    </div>
  </div>
  <div class="ella-input-row">
    <input type="text" class="ella-input" id="ellaInput" placeholder="Tanya tentang materi ini..." maxlength="200" onkeypress="if(event.key==='Enter') kirimChat()">
    <button class="ella-send" onclick="kirimChat()" aria-label="Kirim"><i class="bi bi-send-fill"></i></button>
  </div>
</div>

<div class="ella-float" id="ellaFloat" style="display:none" data-hidden-reason="Fitur chat AI (Claude) belum aktif — hapus style ini untuk menampilkan lagi">
  <div class="ella-tip" id="ellaTip">Punya pertanyaan?</div>
  <div class="ella-float-btn" onclick="bukaEllaPanel()" aria-label="Tanya Ella"><i class="bi bi-chat-heart-fill"></i></div>
</div>

</div>
<script>
  const topikJudul = <?= json_encode($topik['judul']) ?>;
  function toggleSection(card) {
    const isOpen = card.classList.contains('open');
    document.querySelectorAll('.section-card.open').forEach(c => c.classList.remove('open'));
    if (!isOpen) card.classList.add('open');
  }

  window.addEventListener('load', () => {
    const first = document.querySelector('.section-card');
    if (first) first.classList.add('open');
  });

  const ellaPanel  = document.getElementById('ellaPanel');
  const ellaOverlay= document.getElementById('overlay');
  const ellaChat   = document.getElementById('ellaChat');
  const ellaInput  = document.getElementById('ellaInput');
  const ellaTip    = document.getElementById('ellaTip');

  setTimeout(() => {
    ellaTip.style.display = 'block';
    setTimeout(() => { ellaTip.style.display = 'none'; }, 3000);
  }, 2000);

  function bukaEllaPanel() {
    ellaPanel.classList.add('open');
    ellaOverlay.classList.add('show');
    setTimeout(() => ellaInput.focus(), 350);
    ellaTip.style.display = 'none';
  }

  function tutupEllaPanel() {
    ellaPanel.classList.remove('open');
    ellaOverlay.classList.remove('show');
  }

  const ellaRespons = {
    'apa':     'Pertanyaan bagus! Coba baca bagian materi di atas ya, ada penjelasannya di sana.',
    'kenapa':  'Karena alam itu penuh keajaiban! Ilmu ini membantu kita memahami dunia sekitar kita.',
    'bagaimana':'Caranya bisa kamu pelajari pelan-pelan di setiap bagian materi. Jangan terburu-buru!',
    'kuis':    'Kalau sudah siap, klik tombol "Mulai Kuis!" di bagian bawah. Semangat!',
    'sulit':   'Tenang, tidak ada yang sulit kalau kita mau mencoba! Baca ulang materinya pelan-pelan ya.',
    'default': ['Pertanyaan menarik! Coba cari di bagian materi di atas ya.',
                'Wah, penasaran juga aku! Yuk baca materinya bareng-bareng.',
                'Bagus pertanyaannya! Ini adalah bagian penting dari ' + topikJudul + '.',
                'Aku rasa jawabannya ada di bagian materi. Coba buka satu per satu!']
  };
  let chatCount = 0;

  function kirimChat() {
    const val = ellaInput.value.trim();
    if (!val) return;
    const userBubble = document.createElement('div');
    userBubble.className = 'chat-user';
    userBubble.textContent = val;
    ellaChat.appendChild(userBubble);
    ellaInput.value = '';
    ellaChat.scrollTop = ellaChat.scrollHeight;

    setTimeout(() => {
      const lower = val.toLowerCase();
      let respons = '';
      if (lower.includes('apa'))      respons = ellaRespons['apa'];
      else if (lower.includes('kenapa')) respons = ellaRespons['kenapa'];
      else if (lower.includes('bagaimana')) respons = ellaRespons['bagaimana'];
      else if (lower.includes('kuis') || lower.includes('soal')) respons = ellaRespons['kuis'];
      else if (lower.includes('sulit') || lower.includes('susah')) respons = ellaRespons['sulit'];
      else {
        const defs = ellaRespons['default'];
        respons = defs[chatCount % defs.length];
        chatCount++;
      }
      const ellaBubble = document.createElement('div');
      ellaBubble.className = 'chat-ella';
      ellaBubble.textContent = respons;
      ellaChat.appendChild(ellaBubble);
      ellaChat.scrollTop = ellaChat.scrollHeight;
    }, 700);
  }

  const mainIcon = document.getElementById('animIconMain');
  if (mainIcon) {
    mainIcon.addEventListener('click', () => {
      mainIcon.style.animation = 'none';
      mainIcon.style.transform = 'scale(1.4) rotate(20deg)';
      mainIcon.style.transition = 'transform 0.3s cubic-bezier(0.34,1.56,0.64,1)';
      setTimeout(() => {
        mainIcon.style.transform = '';
        mainIcon.style.transition = '';
        mainIcon.style.animation = 'iconFloat 3s ease-in-out infinite';
      }, 400);
    });
  }
</script>
</body>
</html>