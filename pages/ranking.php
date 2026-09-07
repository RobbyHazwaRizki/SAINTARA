<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/ranking.php — Leaderboard Sesi + Sepanjang Masa
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

// Tab aktif
$tab   = $_GET['tab']   ?? 'sesi';    // sesi | alltime
$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0; // 0 = semua

// ── Ambil sesi aktif ────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM sesi WHERE aktif = 1 ORDER BY mulai DESC LIMIT 1");
$stmt->execute();
$sesiAktif = $stmt->fetch();

// ── Leaderboard SESI ────────────────────────────────────────
$rankingSesi = [];
if ($sesiAktif) {
    if ($kelas > 0) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.nama, s.kelas, s.level, s.avatar,
                   COALESCE(xs.xp, 0) AS xp_sesi
            FROM siswa s
            LEFT JOIN xp_sesi xs ON xs.siswa_id = s.id AND xs.sesi_id = ?
            WHERE s.kelas = ?
            ORDER BY xp_sesi DESC, s.nama ASC
            LIMIT 30
        ");
        $stmt->execute([$sesiAktif['id'], $kelas]);
    } else {
        $stmt = $pdo->prepare("
            SELECT s.id, s.nama, s.kelas, s.level, s.avatar,
                   COALESCE(xs.xp, 0) AS xp_sesi
            FROM siswa s
            LEFT JOIN xp_sesi xs ON xs.siswa_id = s.id AND xs.sesi_id = ?
            ORDER BY xp_sesi DESC, s.nama ASC
            LIMIT 30
        ");
        $stmt->execute([$sesiAktif['id']]);
    }
    $rankingSesi = $stmt->fetchAll();
}

// ── Leaderboard ALL TIME ─────────────────────────────────────
if ($kelas > 0) {
    $stmt = $pdo->prepare("
        SELECT id, nama, kelas, xp, level, avatar
        FROM siswa WHERE kelas = ?
        ORDER BY xp DESC LIMIT 30
    ");
    $stmt->execute([$kelas]);
} else {
    $stmt = $pdo->prepare("
        SELECT id, nama, kelas, xp, level, avatar
        FROM siswa ORDER BY xp DESC LIMIT 30
    ");
    $stmt->execute();
}
$rankingAllTime = $stmt->fetchAll();

// ── Posisi siswa saat ini ────────────────────────────────────
$posSesi    = 0;
$posAllTime = 0;
foreach ($rankingSesi as $i => $r) {
    if ($r['id'] === $siswa['id']) { $posSesi = $i + 1; break; }
}
foreach ($rankingAllTime as $i => $r) {
    if ($r['id'] === $siswa['id']) { $posAllTime = $i + 1; break; }
}

// Ambil XP sesi siswa
$xpSesiSiswa = 0;
if ($sesiAktif) {
    $stmt = $pdo->prepare("SELECT xp FROM xp_sesi WHERE sesi_id = ? AND siswa_id = ?");
    $stmt->execute([$sesiAktif['id'], $siswa['id']]);
    $xpSesiSiswa = (int)($stmt->fetchColumn() ?: 0);
}

// Helper medal
function medalIcon(int $pos): string {
    return match($pos) { 1=>'🥇', 2=>'🥈', 3=>'🥉', default=>$pos };
}
function medalClass(int $pos): string {
    return match($pos) { 1=>'gold', 2=>'silver', 3=>'bronze', default=>'' };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title>Ranking — Saintara</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --primary:#4DA8DA;--mint:#80D8C3;--yellow:#FFD66B;
      --bg:#F0F8FF;--white:#fff;--navy:#1A2E3A;--muted:#6B8899;
      --fh:'Fredoka',sans-serif;--fb:'Nunito',sans-serif;
    }
    html,body{font-family:var(--fb);background:var(--bg);color:var(--navy);-webkit-font-smoothing:antialiased}
    a{text-decoration:none;color:inherit}
    .page{max-width:480px;margin:0 auto;min-height:100vh;background:var(--bg)}
    .scroll-area{overflow-y:auto;padding-bottom:calc(68px + 20px)}

    /* ── HEADER ─────────────────────────────────────────────── */
    .top-bar{
      background:linear-gradient(155deg,#3A90C2 0%,#4DA8DA 55%,#6EC5E0 100%);
      padding:20px 20px 56px;position:relative;overflow:hidden;
    }
    .tbg{position:absolute;opacity:0.07;pointer-events:none;border-radius:50%}
    .tbg1{width:160px;height:160px;border:3px solid white;top:-55px;right:-40px}
    .tbg2{width:80px;height:80px;background:white;bottom:8px;left:-20px}

    .tb-row{display:flex;align-items:center;justify-content:space-between;position:relative;z-index:2}
    .tb-title{font-family:var(--fh);font-size:1.3rem;font-weight:700;color:white;display:flex;align-items:center;gap:8px}
    .tb-title i{font-size:1.2rem}
    .tb-xp{
      display:flex;align-items:center;gap:4px;
      background:rgba(255,255,255,0.18);border:1.5px solid rgba(255,255,255,0.28);
      border-radius:999px;padding:5px 12px;
      font-family:var(--fh);font-size:0.88rem;font-weight:700;color:var(--yellow);
    }
    .wave-btm{position:absolute;bottom:-1px;left:0;right:0}

    /* ── MY RANK CARD ───────────────────────────────────────── */
    .my-rank-card{
      margin:-32px 16px 0;position:relative;z-index:10;
      background:white;border-radius:20px;padding:16px 18px;
      box-shadow:0 6px 24px rgba(26,46,58,0.12);
      border:2px solid rgba(77,168,218,0.15);
      display:flex;align-items:center;gap:14px;
      animation:cardUp 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    @keyframes cardUp{
      from{opacity:0;transform:translateY(20px) scale(0.95)}
      to{opacity:1;transform:translateY(0) scale(1)}
    }
    .my-rank-pos{
      width:52px;height:52px;border-radius:14px;
      background:linear-gradient(135deg,#4DA8DA,#3A90C2);
      display:flex;align-items:center;justify-content:center;
      font-family:var(--fh);font-size:1.5rem;font-weight:700;color:white;
      flex-shrink:0;box-shadow:0 4px 12px rgba(77,168,218,0.38);
    }
    .my-rank-info{flex:1}
    .my-rank-name{font-family:var(--fh);font-size:1rem;font-weight:700;color:var(--navy)}
    .my-rank-sub{font-size:0.72rem;color:var(--muted);font-weight:600;margin-top:2px}
    .my-rank-xp{text-align:right;flex-shrink:0}
    .my-rank-xp-num{font-family:var(--fh);font-size:1.3rem;font-weight:700;color:var(--primary);line-height:1}
    .my-rank-xp-lbl{font-size:0.65rem;color:var(--muted);font-weight:700}

    /* ── SESI INFO BANNER ───────────────────────────────────── */
    .sesi-banner{
      margin:14px 16px 0;
      background:linear-gradient(135deg,#E1F5EE,#C8F0E3);
      border:1.5px solid var(--mint);border-radius:16px;
      padding:12px 16px;display:flex;align-items:center;gap:12px;
    }
    .sesi-banner-icon{
      width:38px;height:38px;border-radius:10px;
      background:var(--mint);display:flex;align-items:center;
      justify-content:center;font-size:1.1rem;flex-shrink:0;
    }
    .sesi-banner-info{flex:1}
    .sesi-banner-name{font-family:var(--fh);font-size:0.88rem;font-weight:700;color:#0A5A40}
    .sesi-banner-sub{font-size:0.7rem;color:#2A7A60;font-weight:600;margin-top:1px}
    .sesi-banner-badge{
      background:#0F7A5A;color:white;font-size:0.65rem;font-weight:800;
      padding:3px 9px;border-radius:999px;text-transform:uppercase;letter-spacing:0.4px;
      flex-shrink:0;
    }

    /* ── TABS ───────────────────────────────────────────────── */
    .tab-wrap{
      margin:14px 16px 0;
      background:white;border-radius:14px;padding:5px;
      display:flex;gap:4px;
      box-shadow:0 2px 8px rgba(26,46,58,0.07);
    }
    .tab-btn{
      flex:1;padding:10px;border-radius:10px;border:none;
      font-family:var(--fh);font-size:0.88rem;font-weight:600;
      color:var(--muted);cursor:pointer;background:transparent;
      transition:all 0.18s;display:flex;align-items:center;justify-content:center;gap:6px;
    }
    .tab-btn i{font-size:1rem}
    .tab-btn.active{background:linear-gradient(135deg,#4DA8DA,#3A90C2);color:white;box-shadow:0 3px 10px rgba(77,168,218,0.35)}

    /* ── FILTER KELAS ───────────────────────────────────────── */
    .filter-scroll {
      display: flex;
      gap: 8px;
      padding: 12px 16px 12px;
      overflow-x: auto;
      
      /* Ini yang mencegah item turun ke bawah dan bisa di-scroll */
      flex-wrap: nowrap; 
      -webkit-overflow-scrolling: touch; 
      scroll-snap-type: x mandatory;
      scrollbar-width: none; 
    }
    .filter-scroll::-webkit-scrollbar {
      display: none; 
    }
    
    .filter-pill {
      /* Ini yang mencegah tombol mengecil otomatis */
      flex-shrink: 0; 
      scroll-snap-align: start;
      
      padding: 6px 14px;
      background: white;
      border: 1.5px solid rgba(77,168,218,0.18);
      border-radius: 999px;
      font-family: var(--fh);
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--muted);
      cursor: pointer;
      transition: all 0.15s;
      text-decoration: none;
      white-space: nowrap;
    }
    .filter-pill:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
    .filter-pill.active {
      background: linear-gradient(135deg, #4DA8DA, #3A90C2);
      border-color: transparent;
      color: white;
      box-shadow: 0 3px 10px rgba(77,168,218,0.32);
    }

    /* ── TOP 3 PODIUM ───────────────────────────────────────── */
    .podium-wrap{
      padding:16px 16px 4px;
      display:grid;grid-template-columns:1fr 1.15fr 1fr;
      align-items:flex-end;gap:8px;
    }
    .podium-item{
      display:flex;flex-direction:column;align-items:center;gap:5px;
      animation:podiumIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    .podium-item:nth-child(1){animation-delay:0.1s}
    .podium-item:nth-child(2){animation-delay:0s}
    .podium-item:nth-child(3){animation-delay:0.2s}
    @keyframes podiumIn{
      from{opacity:0;transform:translateY(20px)}
      to{opacity:1;transform:translateY(0)}
    }

    .podium-avatar{
      position:relative;display:flex;align-items:center;justify-content:center;
    }
    .podium-avatar-circle{
      border-radius:50%;display:flex;align-items:center;justify-content:center;
      background:linear-gradient(135deg,#4DA8DA,#80D8C3);
      color:white;font-size:1.1rem;border:3px solid white;
      box-shadow:0 4px 14px rgba(77,168,218,0.35);
      transition:transform 0.2s;
    }
    .podium-avatar-circle:hover{transform:scale(1.08)}
    .p1 .podium-avatar-circle{width:64px;height:64px;font-size:1.3rem;border-color:#FFD700;box-shadow:0 6px 18px rgba(255,215,0,0.4)}
    .p2 .podium-avatar-circle{width:52px;height:52px}
    .p3 .podium-avatar-circle{width:48px;height:48px}

    .podium-crown{
      position:absolute;top:-14px;font-size:1.3rem;
      animation:crownBounce 2s ease-in-out infinite;
    }
    @keyframes crownBounce{
      0%,100%{transform:translateY(0) rotate(-5deg)}
      50%{transform:translateY(-4px) rotate(5deg)}
    }

    .podium-name{
      font-family:var(--fh);font-size:0.78rem;font-weight:700;
      color:var(--navy);text-align:center;max-width:80px;
      overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
    }
    .podium-xp{font-size:0.68rem;font-weight:800;color:var(--primary);text-align:center}
    .podium-kelas{font-size:0.62rem;color:var(--muted);text-align:center}

    .podium-block{
      border-radius:12px 12px 0 0;width:100%;
      display:flex;align-items:center;justify-content:center;
      font-family:var(--fh);font-size:1.1rem;font-weight:700;color:white;
    }
    .p1 .podium-block{height:55px;background:linear-gradient(180deg,#FFD700,#F0A800)}
    .p2 .podium-block{height:42px;background:linear-gradient(180deg,#4DA8DA,#3A90C2)}
    .p3 .podium-block{height:32px;background:linear-gradient(180deg,#80D8C3,#5BBFAA)}

    /* ── RANK LIST ──────────────────────────────────────────── */
    .rank-list{padding:4px 16px 0;display:flex;flex-direction:column;gap:8px}

    .rank-row{
      background:white;border-radius:14px;padding:12px 14px;
      display:flex;align-items:center;gap:12px;
      box-shadow:0 2px 8px rgba(26,46,58,0.06);
      border:1.5px solid transparent;
      transition:transform 0.18s,box-shadow 0.18s;
      animation:rowIn 0.4s ease both;
    }
    .rank-row:hover{transform:translateX(3px);box-shadow:0 4px 14px rgba(77,168,218,0.15)}
    @keyframes rowIn{
      from{opacity:0;transform:translateX(-10px)}
      to{opacity:1;transform:translateX(0)}
    }
    .rank-row.me{border-color:var(--primary);background:linear-gradient(135deg,#EBF6FC,#E0F2FF)}
    .rank-row.gold{border-color:#FFD700}
    .rank-row.silver{border-color:#C0C0C0}
    .rank-row.bronze{border-color:#CD7F32}

    .rank-pos{
      width:30px;text-align:center;font-family:var(--fh);
      font-size:1rem;font-weight:700;flex-shrink:0;color:var(--muted);
    }
    .rank-pos.gold{color:#FFB800;font-size:1.2rem}
    .rank-pos.silver{color:#909090;font-size:1.2rem}
    .rank-pos.bronze{color:#CD7F32;font-size:1.1rem}

    .rank-ava{
      width:38px;height:38px;border-radius:50%;
      background:linear-gradient(135deg,#4DA8DA,#80D8C3);
      display:flex;align-items:center;justify-content:center;
      flex-shrink:0;font-size:1.1rem;color:white;
    }
    .rank-info{flex:1;min-width:0}
    .rank-name{
      font-family:var(--fh);font-size:0.9rem;font-weight:700;color:var(--navy);
      white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    }
    .rank-meta{font-size:0.68rem;color:var(--muted);font-weight:600;margin-top:1px}

    .rank-xp-val{
      font-family:var(--fh);font-size:1rem;font-weight:700;
      color:var(--primary);flex-shrink:0;text-align:right;
    }
    .rank-xp-lbl{font-size:0.62rem;color:var(--muted);font-weight:700;text-align:right}

    /* Empty state */
    .empty-rank{
      text-align:center;padding:40px 20px;
      background:white;border-radius:18px;
      border:2px dashed rgba(77,168,218,0.2);
    }
    .empty-rank i{font-size:2.5rem;color:#9BBCCF;display:block;margin-bottom:10px}
    .empty-rank p{font-size:0.85rem;color:var(--muted);line-height:1.5}

    /* ── BOTTOM NAV ─────────────────────────────────────────── */
    .bottom-nav{
      position:fixed;bottom:0;left:50%;transform:translateX(-50%);
      width:100%;max-width:480px;height:68px;
      background:white;border-top:1.5px solid rgba(77,168,218,0.12);
      border-radius:20px 20px 0 0;
      display:flex;align-items:center;justify-content:space-around;
      padding:0 8px;z-index:100;box-shadow:0 -4px 20px rgba(77,168,218,0.1);
    }
    .nav-item{
      display:flex;flex-direction:column;align-items:center;gap:3px;
      padding:8px 18px;border-radius:14px;cursor:pointer;
      color:#9BBCCF;font-size:0.68rem;font-weight:700;
      font-family:var(--fb);transition:color 0.15s;
      text-decoration:none;position:relative;
    }
    .nav-item i{font-size:1.35rem;transition:transform 0.18s}
    .nav-item.active{color:var(--primary)}
    .nav-item.active i{transform:scale(1.15)}
    .nav-item.active::after{
      content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);
      width:32px;height:3px;background:var(--primary);border-radius:0 0 4px 4px;
    }

    .fade-in{animation:fadeUp 0.4s ease both}
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .d1{animation-delay:0.05s}.d2{animation-delay:0.1s}.d3{animation-delay:0.15s}
  </style>
</head>
<body>
<div class="page">
<div class="scroll-area">

  <!-- HEADER -->
  <div class="top-bar fade-in">
    <div class="tbg tbg1"></div>
    <div class="tbg tbg2"></div>
    <div class="tb-row">
      <div class="tb-title">
        <i class="bi bi-trophy-fill"></i>
        Papan Ranking
      </div>
      <div class="tb-xp">
        <i class="bi bi-star-fill"></i>
        <?= number_format($siswa['xp']) ?> XP
      </div>
    </div>
    <svg class="wave-btm" viewBox="0 0 1440 48" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,24 C360,48 1080,0 1440,24 L1440,48 L0,48 Z" fill="#F0F8FF"/>
    </svg>
  </div>

  <!-- MY RANK CARD -->
  <div class="my-rank-card">
    <div class="my-rank-pos">
      <?= $tab === 'sesi' ? ($posSesi ?: '—') : ($posAllTime ?: '—') ?>
    </div>
    <div class="my-rank-info">
      <div class="my-rank-name"><?= htmlspecialchars(explode(' ',$siswa['nama'])[0]) ?> (Kamu)</div>
      <div class="my-rank-sub">
        Kelas <?= $siswa['kelas'] ?> &middot; Level <?= $siswa['level'] ?> &middot;
        <?= getLevelName($siswa['level']) ?>
      </div>
    </div>
    <div class="my-rank-xp">
      <div class="my-rank-xp-num">
        <?= $tab === 'sesi' ? number_format($xpSesiSiswa) : number_format($siswa['xp']) ?>
      </div>
      <div class="my-rank-xp-lbl"><?= $tab === 'sesi' ? 'XP Sesi' : 'Total XP' ?></div>
    </div>
  </div>

  <!-- SESI BANNER -->
  <?php if ($sesiAktif): ?>
  <div class="sesi-banner fade-in d1">
    <div class="sesi-banner-icon">
      <i class="bi bi-lightning-charge-fill" style="color:#0A5A40"></i>
    </div>
    <div class="sesi-banner-info">
      <div class="sesi-banner-name"><?= htmlspecialchars($sesiAktif['nama']) ?></div>
      <div class="sesi-banner-sub">
        Dimulai <?= date('d M Y', strtotime($sesiAktif['mulai'])) ?>
        <?= $sesiAktif['kelas'] ? ' · Kelas '.$sesiAktif['kelas'].' saja' : ' · Semua kelas' ?>
      </div>
    </div>
    <div class="sesi-banner-badge">Aktif</div>
  </div>
  <?php endif; ?>

  <!-- TABS -->
  <div class="tab-wrap fade-in d1">
    <button class="tab-btn <?= $tab==='sesi' ? 'active' : '' ?>"
            onclick="gantiTab('sesi')" type="button">
      <i class="bi bi-lightning-charge-fill"></i>
      Sesi Ini
    </button>
    <button class="tab-btn <?= $tab==='alltime' ? 'active' : '' ?>"
            onclick="gantiTab('alltime')" type="button">
      <i class="bi bi-infinity"></i>
      Sepanjang Masa
    </button>
  </div>

  <!-- FILTER KELAS -->
  <div class="filter-scroll fade-in d2">
    <?php
    $kelasFilters = [0=>'Semua Kelas',1=>'Kelas 1',2=>'Kelas 2',3=>'Kelas 3',4=>'Kelas 4',5=>'Kelas 5',6=>'Kelas 6'];
    foreach ($kelasFilters as $k => $lbl): ?>
    <a href="?tab=<?= $tab ?>&kelas=<?= $k ?>"
       class="filter-pill <?= $kelas===$k ? 'active' : '' ?>">
      <?= $lbl ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php
  $listAktif = $tab === 'sesi' ? $rankingSesi : $rankingAllTime;
  $xpKey     = $tab === 'sesi' ? 'xp_sesi' : 'xp';
  ?>

  <?php if (empty($listAktif)): ?>
  <div style="padding:0 16px;margin-top:12px">
    <div class="empty-rank">
      <i class="bi bi-people"></i>
      <p>Belum ada siswa di ranking ini.<br>Jadilah yang pertama mengerjakan kuis!</p>
    </div>
  </div>

  <?php else: ?>

  <!-- PODIUM TOP 3 -->
  <?php
  $top3 = array_slice($listAktif, 0, 3);
  // Susun: posisi 2 - 1 - 3
  $podiumOrder = [];
  if (isset($top3[1])) $podiumOrder[] = ['data'=>$top3[1],'pos'=>2,'cls'=>'p2'];
  if (isset($top3[0])) $podiumOrder[] = ['data'=>$top3[0],'pos'=>1,'cls'=>'p1'];
  if (isset($top3[2])) $podiumOrder[] = ['data'=>$top3[2],'pos'=>3,'cls'=>'p3'];
  ?>
  <div class="podium-wrap fade-in d2">
    <?php foreach ($podiumOrder as $p): ?>
    <div class="podium-item <?= $p['cls'] ?>">
      <div class="podium-avatar">
        <?php if ($p['pos']===1): ?>
        <div class="podium-crown">👑</div>
        <?php endif; ?>
        <div class="podium-avatar-circle">
          <i class="bi bi-person-fill"></i>
        </div>
      </div>
      <div class="podium-name"><?= htmlspecialchars(explode(' ',$p['data']['nama'])[0]) ?></div>
      <div class="podium-xp"><?= number_format($p['data'][$xpKey]) ?> XP</div>
      <div class="podium-kelas">Kelas <?= $p['data']['kelas'] ?></div>
      <div class="podium-block"><?= $p['pos'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- RANK LIST (mulai dari 4) -->
  <div class="rank-list">
    <?php foreach ($listAktif as $idx => $r):
      $pos   = $idx + 1;
      $isMe  = $r['id'] === $siswa['id'];
      $mCls  = medalClass($pos);
      if ($pos <= 3) continue; // sudah di podium
    ?>
    <div class="rank-row <?= $mCls ?> <?= $isMe?'me':'' ?>"
         style="animation-delay:<?= 0.04*($idx-2) ?>s">
      <div class="rank-pos <?= $mCls ?>"><?= medalIcon($pos) ?></div>
      <div class="rank-ava"><i class="bi bi-person-fill"></i></div>
      <div class="rank-info">
        <div class="rank-name">
          <?= htmlspecialchars($r['nama']) ?>
          <?php if ($isMe): ?>
          <span style="font-size:0.65rem;color:var(--primary);font-weight:800"> ★ Kamu</span>
          <?php endif; ?>
        </div>
        <div class="rank-meta">
          Kelas <?= $r['kelas'] ?> &middot; Level <?= $r['level'] ?>
        </div>
      </div>
      <div>
        <div class="rank-xp-val"><?= number_format($r[$xpKey]) ?></div>
        <div class="rank-xp-lbl"><?= $tab==='sesi'?'XP Sesi':'Total XP' ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="height:20px"></div>
</div><!-- /.scroll-area -->

<!-- BOTTOM NAV -->
<nav class="bottom-nav" role="navigation">
  <a class="nav-item" href="/SAINTARA/pages/beranda.php"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
  <a class="nav-item" href="/SAINTARA/pages/materi.php"><i class="bi bi-book-half"></i><span>Materi</span></a>
  <a class="nav-item active" href="/SAINTARA/pages/ranking.php"><i class="bi bi-bar-chart-fill"></i><span>Ranking</span></a>
  <a class="nav-item" href="/SAINTARA/pages/profil.php"><i class="bi bi-person-circle"></i><span>Profil</span></a>
</nav>

</div><!-- /.page -->
<script>
  function gantiTab(tab) {
    const kelas = new URLSearchParams(window.location.search).get('kelas') || '0';
    window.location.href = '?tab=' + tab + '&kelas=' + kelas;
  }

  // --- TAMBAHAN KODE: Fitur Slide / Drag dengan Mouse di PC ---
  const slider = document.querySelector('.filter-scroll');
  let isDown = false;
  let startX;
  let scrollLeft;

  slider.addEventListener('mousedown', (e) => {
    isDown = true;
    slider.style.cursor = 'grabbing'; // Ubah kursor jadi tangan menggenggam
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
    const walk = (x - startX) * 2; // Angka 2 adalah kecepatan geser (bisa diubah)
    slider.scrollLeft = scrollLeft - walk;
  });
</script>
<?php include '../includes/audio-global.php'; ?>
</body>
</html>