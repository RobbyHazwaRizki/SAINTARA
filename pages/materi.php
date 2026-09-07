<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/materi.php — Daftar Materi IPA (100% Database Driven)
// ============================================================
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['siswa_id'])) {
    header('Location: /SAINTARA/login.php'); exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$_SESSION['siswa_id']]);
$siswa = $stmt->fetch();
if (!$siswa) { session_destroy(); header('Location: /SAINTARA/login.php'); exit; }

// Filter kelas — default kelas siswa sendiri
$kelasAktif = isset($_GET['kelas']) ? (int)$_GET['kelas'] : (int)$siswa['kelas'];
if ($kelasAktif < 1 || $kelasAktif > 6) $kelasAktif = (int)$siswa['kelas'];

// AMBIL TOPIK LANGSUNG DARI DATABASE
$stmtTopik = $pdo->prepare("
    SELECT t.*,
           COALESCE(p.selesai, 0)  AS sudah_selesai,
           COALESCE(p.skor, 0)     AS skor_terakhir
    FROM topik t
    LEFT JOIN progress p ON p.topik_id = t.id AND p.siswa_id = ?
    WHERE t.kelas = ?
    ORDER BY t.urutan ASC, t.id ASC
");
$stmtTopik->execute([$siswa['id'], $kelasAktif]);
$topikList = $stmtTopik->fetchAll(PDO::FETCH_ASSOC);

// Hitung progress keseluruhan kelas aktif
$totalSelesai = 0;
foreach ($topikList as $t) { if ($t['sudah_selesai']) $totalSelesai++; }
$totalTopik   = count($topikList);
$pctKelas     = $totalTopik > 0 ? round(($totalSelesai / $totalTopik) * 100) : 0;

// Menyiapkan susunan warna cantik yang berulang
$colorClasses = ['tib-1', 'tib-2', 'tib-3', 'tib-4'];
$topikTampil = [];
$idx = 0;

// Format data sebelum dikirim ke HTML
foreach ($topikList as $t) {
    $t['warna'] = $colorClasses[$idx % 4];
    $t['icon']  = !empty($t['icon']) ? $t['icon'] : 'bi-book-fill';
    $topikTampil[] = $t;
    $idx++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title>Materi — Saintara</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary:#4DA8DA; --primary-dk:#3590C2; --mint:#80D8C3;
      --yellow:#FFD66B;  --bg:#F0F8FF; --white:#fff;
      --navy:#1A2E3A;    --muted:#6B8899;
      --fh:'Fredoka',sans-serif; --fb:'Nunito',sans-serif;
    }
    html,body { font-family:var(--fb); background:var(--bg); color:var(--navy); -webkit-font-smoothing:antialiased; }
    a { text-decoration:none; color:inherit; }
    .page { max-width:480px; margin:0 auto; min-height:100vh; background:var(--bg); position:relative; }
    .scroll-area { overflow-y:auto; padding-bottom:calc(68px + 20px); }

    /* HEADER */
    .top-bar {
      background:linear-gradient(155deg,#3A90C2 0%,#4DA8DA 60%,#6EC5E0 100%);
      padding:20px 20px 56px;
      position:relative; overflow:hidden;
    }
    .tb-geo { position:absolute; opacity:0.08; pointer-events:none; border-radius:50%; }
    .tbg1 { width:160px;height:160px;border:3px solid white;top:-60px;right:-40px; }
    .tbg2 { width:80px;height:80px;background:white;bottom:8px;left:-24px; }

    .tb-row {
      display:flex; align-items:center; justify-content:space-between;
      position:relative; z-index:2;
    }
    .tb-back {
      width:40px; height:40px; border-radius:50%;
      background:rgba(255,255,255,0.18); border:1.5px solid rgba(255,255,255,0.3);
      display:flex; align-items:center; justify-content:center;
      cursor:pointer; transition:background 0.18s; flex-shrink:0;
      color:white; font-size:1.1rem;
    }
    .tb-back:hover { background:rgba(255,255,255,0.28); }
    .tb-title {
      font-family:var(--fh); font-size:1.2rem; font-weight:700;
      color:white; flex:1; text-align:center;
    }
    .tb-xp {
      display:flex; align-items:center; gap:4px;
      background:rgba(255,255,255,0.18); border:1.5px solid rgba(255,255,255,0.28);
      border-radius:999px; padding:5px 12px;
      font-family:var(--fh); font-size:0.88rem; font-weight:700; color:var(--yellow);
    }
    .tb-xp i { font-size:0.95rem; }

    .wave-btm { position:absolute; bottom:-1px; left:0; right:0; }

    /* KELAS TABS */
    .kelas-tabs-wrap {
      padding:0 16px; margin-top:-28px; position:relative; z-index:10;
    }
    .kelas-tabs {
      display:flex; gap:6px; overflow-x:auto; scrollbar-width:none;
      padding:14px 4px;
      flex-wrap: nowrap;
      -webkit-overflow-scrolling: touch; 
      scroll-snap-type: x mandatory;
    }
    .kelas-tabs::-webkit-scrollbar { display:none; }
    .kelas-tab {
      flex-shrink:0; padding:8px 18px; scroll-snap-align: start;
      background:white; border:2px solid rgba(77,168,218,0.15);
      border-radius:999px;
      font-family:var(--fh); font-size:0.82rem; font-weight:600;
      color:var(--muted); cursor:pointer;
      transition:all 0.18s; white-space:nowrap;
      box-shadow:0 2px 8px rgba(26,46,58,0.06);
      text-decoration:none; display:inline-flex; align-items:center; gap:5px;
    }
    .kelas-tab i { font-size:0.9rem; }
    .kelas-tab:hover { border-color:var(--primary); color:var(--primary); }
    .kelas-tab.active {
      background:linear-gradient(135deg,#4DA8DA,#3A90C2);
      border-color:transparent; color:white;
      box-shadow:0 4px 14px rgba(77,168,218,0.38);
    }
    .kelas-tab.my-kelas::after {
      content:''; width:6px; height:6px;
      background:var(--yellow); border-radius:50%; display:inline-block;
    }

    /* PROGRESS KELAS */
    .prog-card {
      margin:0 16px 16px; background:white;
      border-radius:18px; padding:16px 18px;
      box-shadow:0 2px 10px rgba(26,46,58,0.08);
      border:1.5px solid rgba(77,168,218,0.1);
    }
    .prog-card-top {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:10px;
    }
    .prog-card-title {
      font-family:var(--fh); font-size:0.92rem; font-weight:700; color:var(--navy);
    }
    .prog-pct {
      font-family:var(--fh); font-size:1.1rem; font-weight:700; color:var(--primary);
    }
    .prog-bar-bg {
      height:8px; background:#F0F8FF; border-radius:999px; overflow:hidden;
    }
    .prog-bar-fill {
      height:100%;
      background:linear-gradient(90deg,#4DA8DA,#80D8C3);
      border-radius:999px;
      transition:width 0.9s cubic-bezier(0.4,0,0.2,1);
    }
    .prog-sub {
      font-size:0.72rem; color:var(--muted); font-weight:600; margin-top:5px;
    }

    /* SECTION */
    .section { padding:0 16px 0; }
    .section-lbl {
      font-family:var(--fh); font-size:0.78rem; font-weight:700;
      color:var(--muted); text-transform:uppercase; letter-spacing:0.6px;
      margin-bottom:10px; display:flex; align-items:center; gap:6px;
    }
    .section-lbl i { font-size:0.9rem; color:var(--primary); }

    /* TOPIK CARD GRID */
    .topik-grid { display:flex; flex-direction:column; gap:11px; }

    .topik-card {
      background:white; border-radius:18px;
      padding:16px 16px 16px 16px;
      display:flex; align-items:center; gap:14px;
      box-shadow:0 2px 10px rgba(26,46,58,0.07);
      border:1.5px solid rgba(77,168,218,0.08);
      cursor:pointer; text-decoration:none;
      transition:transform 0.2s cubic-bezier(0.34,1.56,0.64,1),
                  box-shadow 0.18s, border-color 0.18s;
      position:relative; overflow:hidden;
    }
    .topik-card::before {
      content:''; position:absolute; left:0; top:0; bottom:0;
      width:4px; border-radius:4px 0 0 4px;
      background:transparent; transition:background 0.18s;
    }
    .topik-card:hover { transform:translateY(-3px) translateX(3px); border-color:#4DA8DA; box-shadow:0 6px 20px rgba(77,168,218,0.18); }
    .topik-card:hover::before { background:#4DA8DA; }
    .topik-card.done::before { background:#80D8C3; }
    .topik-card:active { transform:scale(0.97); }

    .topik-icon {
      width:52px; height:52px; border-radius:14px;
      display:flex; align-items:center; justify-content:center;
      flex-shrink:0; font-size:1.5rem;
      transition:transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
    }
    .topik-card:hover .topik-icon { transform:scale(1.12) rotate(-5deg); }
    .tib-1 { background:#EBF6FC; color:#4DA8DA; }
    .tib-2 { background:#E1F5EE; color:#0F7A5A; }
    .tib-3 { background:#FFF8E6; color:#C07A00; }
    .tib-4 { background:#FFE8E8; color:#C04040; }

    .topik-body { flex:1; min-width:0; }
    .topik-title {
      font-family:var(--fh); font-size:0.97rem; font-weight:700;
      color:var(--navy); margin-bottom:3px;
    }
    .topik-desc {
      font-size:0.75rem; color:var(--muted); font-weight:600;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .topik-meta {
      display:flex; align-items:center; gap:8px; margin-top:7px;
    }
    .topik-prog-bg {
      flex:1; height:5px; background:#F0F8FF; border-radius:999px; overflow:hidden;
    }
    .topik-prog-fill {
      height:100%;
      background:linear-gradient(90deg,#4DA8DA,#80D8C3);
      border-radius:999px;
    }
    .topik-prog-txt {
      font-size:0.68rem; font-weight:800; color:var(--muted); flex-shrink:0;
    }

    .topik-right { flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:5px; }
    .badge-done {
      width:30px; height:30px; border-radius:50%;
      background:#E1F5EE; display:flex; align-items:center; justify-content:center;
    }
    .badge-done i { font-size:1rem; color:#0F7A5A; }
    .badge-go {
      width:30px; height:30px; border-radius:50%;
      background:#EBF6FC; display:flex; align-items:center; justify-content:center;
    }
    .badge-go i { font-size:1rem; color:var(--primary); }
    .topik-xp {
      font-size:0.65rem; font-weight:800; color:var(--muted);
    }

    /* Empty state */
    .empty-state {
      text-align:center; padding:40px 20px;
      background:white; border-radius:18px;
      border:2px dashed rgba(77,168,218,0.2);
    }
    .empty-state i { font-size:2.5rem; color:#9BBCCF; display:block; margin-bottom:10px; }
    .empty-state p { font-size:0.85rem; color:var(--muted); line-height:1.5; }

    /* BOTTOM NAV */
    .bottom-nav {
      position:fixed; bottom:0; left:50%; transform:translateX(-50%);
      width:100%; max-width:480px; height:68px;
      background:white; border-top:1.5px solid rgba(77,168,218,0.12);
      border-radius:20px 20px 0 0;
      display:flex; align-items:center; justify-content:space-around;
      padding:0 8px; z-index:100;
      box-shadow:0 -4px 20px rgba(77,168,218,0.1);
    }
    .nav-item {
      display:flex; flex-direction:column; align-items:center; gap:3px;
      padding:8px 18px; border-radius:14px; cursor:pointer;
      color:#9BBCCF; font-size:0.68rem; font-weight:700;
      font-family:var(--fb); transition:color 0.15s;
      text-decoration:none; -webkit-tap-highlight-color:transparent; position:relative;
    }
    .nav-item i { font-size:1.35rem; transition:transform 0.18s; }
    .nav-item.active { color:var(--primary); }
    .nav-item.active i { transform:scale(1.15); }
    .nav-item.active::after {
      content:''; position:absolute; top:0; left:50%;
      transform:translateX(-50%); width:32px; height:3px;
      background:var(--primary); border-radius:0 0 4px 4px;
    }

    /* ELLA FLOAT */
    .ella-float {
      position:fixed; bottom:calc(68px + 14px); right:16px; z-index:200;
    }
    .ella-float-btn {
      width:52px; height:52px; border-radius:50%;
      background:linear-gradient(135deg,#4DA8DA,#3A90C2);
      border:3px solid white; display:flex; align-items:center; justify-content:center;
      box-shadow:0 6px 18px rgba(77,168,218,0.42); cursor:pointer;
      transition:transform 0.2s cubic-bezier(0.34,1.56,0.64,1);
    }
    .ella-float-btn:hover { transform:scale(1.1); }
    .ella-float-btn:active { transform:scale(0.93); }
    .ella-float-btn i { font-size:1.4rem; color:white; }

    /* Animasi masuk */
    .fade-in { animation:fadeUp 0.4s ease both; }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(14px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .d1{animation-delay:0.05s} .d2{animation-delay:0.1s}
    .d3{animation-delay:0.15s} .d4{animation-delay:0.2s}
  </style>
</head>
<body>
<div class="page">
<div class="scroll-area">

  <div class="top-bar fade-in">
    <div class="tb-geo tbg1"></div>
    <div class="tb-geo tbg2"></div>
    <div class="tb-row">
      <a href="/SAINTARA/pages/beranda.php" class="tb-back" aria-label="Kembali">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div class="tb-title">Materi IPA</div>
      <div class="tb-xp">
        <i class="bi bi-star-fill"></i>
        <?= number_format($siswa['xp']) ?> XP
      </div>
    </div>
    <svg class="wave-btm" viewBox="0 0 1440 48" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,24 C360,48 1080,0 1440,24 L1440,48 L0,48 Z" fill="#F0F8FF"/>
    </svg>
  </div>

  <div class="kelas-tabs-wrap fade-in d1">
    <div class="kelas-tabs" id="kelasTabs" role="tablist" aria-label="Pilih kelas">
      <?php
      $kelasInfo = [
        1=>['label'=>'Kelas 1','icon'=>'bi-flower1'],
        2=>['label'=>'Kelas 2','icon'=>'bi-tree'],
        3=>['label'=>'Kelas 3','icon'=>'bi-search'],
        4=>['label'=>'Kelas 4','icon'=>'bi-eyeglasses'],
        5=>['label'=>'Kelas 5','icon'=>'bi-droplet-half'],
        6=>['label'=>'Kelas 6','icon'=>'bi-rocket-takeoff'],
      ];
      foreach ($kelasInfo as $num => $ki): ?>
      <a href="?kelas=<?= $num ?>"
         class="kelas-tab <?= $num === $kelasAktif ? 'active' : '' ?> <?= $num === (int)$siswa['kelas'] ? 'my-kelas' : '' ?>"
         role="tab" aria-selected="<?= $num === $kelasAktif ? 'true' : 'false' ?>"
         aria-label="<?= $ki['label'] ?>">
        <i class="bi <?= $ki['icon'] ?>"></i>
        <?= $ki['label'] ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="prog-card fade-in d2">
    <div class="prog-card-top">
      <div class="prog-card-title">
        <i class="bi bi-journal-bookmark-fill" style="color:#4DA8DA;margin-right:5px"></i>
        Progress Kelas <?= $kelasAktif ?>
      </div>
      <div class="prog-pct" id="progPct"><?= $pctKelas ?>%</div>
    </div>
    <div class="prog-bar-bg">
      <div class="prog-bar-fill" id="progFill" style="width:0%" data-target="<?= $pctKelas ?>"></div>
    </div>
    <div class="prog-sub">
      <?= $totalSelesai ?> dari <?= $totalTopik ?> topik selesai
      <?= $pctKelas >= 100 ? ' — Keren, semua topik tuntas!' : '' ?>
    </div>
  </div>

  <div class="section fade-in d3">
    <div class="section-lbl">
      <i class="bi bi-grid-fill"></i>
      Pilih Topik
    </div>

    <?php if (empty($topikTampil)): ?>
    <div class="empty-state">
      <i class="bi bi-inbox"></i>
      <p>Materi Kelas <?= $kelasAktif ?> belum tersedia.<br>Guru sedang menyiapkannya!</p>
    </div>
    <?php else: ?>
    <div class="topik-grid" id="topikGrid">
      <?php foreach ($topikTampil as $i => $topik):
        $pct     = $topik['sudah_selesai'] ? 100 : ($topik['skor_terakhir'] > 0 ? 60 : 0);
        $selesai = (bool)$topik['sudah_selesai'];
        $xpReward= getXpReward($kelasAktif);
      ?>
      <a href="/SAINTARA/pages/materi-detail.php?id=<?= $topik['id'] ?>"
         class="topik-card <?= $selesai ? 'done' : '' ?> fade-in"
         style="animation-delay:<?= 0.05 * $i ?>s"
         aria-label="<?= htmlspecialchars($topik['judul']) ?>">
        <div class="topik-icon <?= $topik['warna'] ?>">
          <i class="bi <?= htmlspecialchars($topik['icon']) ?>"></i>
        </div>
        <div class="topik-body">
          <div class="topik-title"><?= htmlspecialchars($topik['judul']) ?></div>
          <div class="topik-desc"><?= htmlspecialchars($topik['deskripsi'] ?? 'Materi belum dideskripsikan') ?></div>
          <div class="topik-meta">
            <div class="topik-prog-bg">
              <div class="topik-prog-fill" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="topik-prog-txt"><?= $pct ?>%</span>
          </div>
        </div>
        <div class="topik-right">
          <?php if ($selesai): ?>
          <div class="badge-done"><i class="bi bi-check-circle-fill"></i></div>
          <?php else: ?>
          <div class="badge-go"><i class="bi bi-play-circle-fill"></i></div>
          <?php endif; ?>
          <span class="topik-xp">+<?= $xpReward ?> XP</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div style="height:20px"></div>
</div><nav class="bottom-nav" role="navigation">
  <a class="nav-item" href="/SAINTARA/pages/beranda.php"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
  <a class="nav-item active" href="/SAINTARA/pages/materi.php"><i class="bi bi-book-half"></i><span>Materi</span></a>
  <a class="nav-item" href="/SAINTARA/pages/ranking.php"><i class="bi bi-bar-chart-fill"></i><span>Ranking</span></a>
  <a class="nav-item" href="/SAINTARA/pages/profil.php"><i class="bi bi-person-circle"></i><span>Profil</span></a>
</nav>

<div class="ella-float" style="display:none" data-hidden-reason="Fitur chat AI (Claude) belum aktif — hapus style ini untuk menampilkan lagi">
  <div class="ella-float-btn" aria-label="Tanya Ella">
    <i class="bi bi-chat-heart-fill"></i>
  </div>
</div>

</div><script>
  // Animasi progress bar
  window.addEventListener('load', () => {
    const fill = document.getElementById('progFill');
    if (fill) setTimeout(() => { fill.style.width = fill.dataset.target + '%'; }, 400);
  });

  // Topik card hover — icon bounce
  document.querySelectorAll('.topik-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      const icon = card.querySelector('.topik-icon');
      if (icon) { icon.style.transform = 'scale(1.15) rotate(-6deg)'; }
    });
    card.addEventListener('mouseleave', () => {
      const icon = card.querySelector('.topik-icon');
      if (icon) { icon.style.transform = ''; }
    });
  });

  // Drag to scroll untuk tab kelas di layar PC
  const slider = document.getElementById('kelasTabs');
  let isDown = false;
  let startX;
  let scrollLeft;

  slider.addEventListener('mousedown', (e) => {
    isDown = true;
    slider.style.cursor = 'grabbing';
    startX = e.pageX - slider.offsetLeft;
    scrollLeft = slider.scrollLeft;
  });
  slider.addEventListener('mouseleave', () => {
    isDown = false;
    slider.style.cursor = 'pointer';
  });
  slider.addEventListener('mouseup', () => {
    isDown = false;
    slider.style.cursor = 'pointer';
  });
  slider.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - slider.offsetLeft;
    const walk = (x - startX) * 2;
    slider.scrollLeft = scrollLeft - walk;
  });
</script>
<?php include '../includes/audio-global.php'; ?>
</body>
</html>