<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/profil.php — Profil Siswa + Koleksi Badge
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

// Statistik lengkap
$levelName  = getLevelName($siswa['level']);
$levelProg  = getLevelProgress($siswa['xp']);
$levelThresh= getLevelThreshold($siswa['level']);
$xpToNext   = max(0, $levelThresh['max'] - $siswa['xp']);

// Progress topik
$stmt = $pdo->prepare("SELECT COUNT(*) FROM progress WHERE siswa_id = ? AND selesai = 1");
$stmt->execute([$siswa['id']]);
$topikSelesai = (int)$stmt->fetchColumn();

// Riwayat kuis terakhir
$stmt = $pdo->prepare("
    SELECT rk.*, t.judul as topik_nama
    FROM riwayat_kuis rk
    LEFT JOIN topik t ON t.id = rk.topik_id
    WHERE rk.siswa_id = ?
    ORDER BY rk.played_at DESC
    LIMIT 5
");
$stmt->execute([$siswa['id']]);
$riwayat = $stmt->fetchAll();

// Badge Siswa
$badgeSiswa = getBadgeSiswa($siswa['id'], $pdo);

// AMBIL MASTER BADGE DARI DATABASE
$stmtMaster = $pdo->query("SELECT * FROM badge_master");
$masterData = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);

$allBadges = [];
if (is_array($masterData)) {
    foreach ($masterData as $b) {
        $allBadges[$b['badge_key']] = $b; 
    }
}

// Proteksi Paksa Array
$badgeSiswa = is_array($badgeSiswa) ? $badgeSiswa : [];
$badgeKeys  = array_column($badgeSiswa, 'key');

// Rank posisi
$stmt = $pdo->prepare("SELECT COUNT(*)+1 FROM siswa WHERE xp > ?");
$stmt->execute([$siswa['xp']]);
$rankPos = (int)$stmt->fetchColumn();

// XP sesi aktif
$stmt = $pdo->prepare("SELECT xs.xp, s.nama as sesi_nama FROM xp_sesi xs JOIN sesi s ON s.id = xs.sesi_id WHERE xs.siswa_id = ? AND s.aktif = 1 LIMIT 1");
$stmt->execute([$siswa['id']]);
$xpSesi = $stmt->fetch();

// Avatar list
$avatarList = ['🐘','🦁','🐯','🐧','🦊','🐸','🦋','🐬','🦄','🐙'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title>Profil — Saintara</title>
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

    /* ── HERO PROFIL ────────────────────────────────────────── */
    .hero-profil{
      background:linear-gradient(155deg,#3A90C2 0%,#4DA8DA 55%,#6EC5E0 100%);
      padding:24px 20px 72px;position:relative;overflow:hidden;text-align:center;
    }
    .hpg{position:absolute;opacity:0.07;pointer-events:none;border-radius:50%}
    .hpg1{width:170px;height:170px;border:3px solid white;top:-60px;right:-50px}
    .hpg2{width:90px;height:90px;background:white;bottom:10px;left:-25px}

    .hp-toolbar{display:flex;justify-content:space-between;position:relative;z-index:2;margin-bottom:20px;}
    .hp-tool-btn{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.18);border:1.5px solid rgba(255,255,255,0.3);display:flex;align-items:center;justify-content:center;color:white;font-size:1rem;cursor:pointer;transition:background 0.18s;text-decoration:none;}
    .hp-tool-btn:hover{background:rgba(255,255,255,0.3)}

    .avatar-big-wrap{position:relative;display:inline-block;z-index:2;}
    .avatar-ring{width:86px;height:86px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;font-size:2.5rem;border:4px solid var(--yellow);box-shadow:0 6px 20px rgba(26,46,58,0.2);cursor:pointer;transition:transform 0.2s;position:relative;overflow:hidden;}
    .avatar-ring:hover{transform:scale(1.06)}

    .avatar-edit-btn{position:absolute;bottom:0;right:0;width:26px;height:26px;border-radius:50%;background:var(--primary);border:2.5px solid white;display:flex;align-items:center;justify-content:center;color:white;font-size:0.7rem;cursor:pointer;}

    .profil-name{font-family:var(--fh);font-size:1.4rem;font-weight:700;color:white;margin-top:10px;position:relative;z-index:2;}
    .profil-kelas{font-size:0.8rem;color:rgba(255,255,255,0.78);font-weight:600;margin-top:3px;position:relative;z-index:2;}
    .profil-level-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.18);border:1.5px solid rgba(255,255,255,0.3);border-radius:999px;padding:5px 14px;margin-top:8px;font-family:var(--fh);font-size:0.82rem;font-weight:700;color:white;position:relative;z-index:2;}
    .profil-level-badge i{color:var(--yellow)}
    .wave-btm{position:absolute;bottom:-1px;left:0;right:0}

    /* ── STATS CARD ─────────────────────────────────────────── */
    .stats-float{margin:-36px 16px 0;position:relative;z-index:10;background:white;border-radius:20px;padding:0;overflow:hidden;box-shadow:0 6px 24px rgba(26,46,58,0.1);border:1.5px solid rgba(77,168,218,0.1);display:grid;grid-template-columns:repeat(4,1fr);animation:cardUp 0.5s cubic-bezier(0.34,1.56,0.64,1) both;}
    @keyframes cardUp{from{opacity:0;transform:translateY(20px) scale(0.95)}to{opacity:1;transform:translateY(0) scale(1)}}
    .stat-item{padding:14px 8px;display:flex;flex-direction:column;align-items:center;gap:3px;border-right:1px solid #F0F8FF;}
    .stat-item:last-child{border-right:none}
    .stat-item i{font-size:1.1rem;margin-bottom:2px}
    .stat-num{font-family:var(--fh);font-size:1.2rem;font-weight:700;color:var(--navy);line-height:1}
    .stat-lbl{font-size:0.62rem;font-weight:800;color:var(--muted);text-align:center}

    /* ── XP PROGRESS ────────────────────────────────────────── */
    .xp-prog-card{margin:14px 16px 0;background:white;border-radius:18px;padding:16px 18px;box-shadow:0 2px 10px rgba(26,46,58,0.07);border:1.5px solid rgba(77,168,218,0.08);}
    .xp-prog-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
    .xp-prog-title{font-family:var(--fh);font-size:0.92rem;font-weight:700;color:var(--navy)}
    .xp-prog-pct{font-family:var(--fh);font-size:1rem;font-weight:700;color:var(--primary)}
    .xp-bar-bg{height:10px;background:#F0F8FF;border-radius:999px;overflow:hidden}
    .xp-bar-fill{height:100%;background:linear-gradient(90deg,#4DA8DA,#80D8C3);border-radius:999px;transition:width 1s cubic-bezier(0.4,0,0.2,1);position:relative;overflow:hidden;}
    .xp-bar-fill::after{content:'';position:absolute;top:0;left:-100%;right:0;bottom:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.45),transparent);animation:barShimmer 2s infinite;}
    @keyframes barShimmer{from{left:-100%}to{left:100%}}
    .xp-bar-labels{display:flex;justify-content:space-between;font-size:0.7rem;color:var(--muted);font-weight:700;margin-top:5px;}

    .section{padding:16px 16px 0}
    .sec-title{font-family:var(--fh);font-size:1rem;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:7px;margin-bottom:12px;}
    .sec-title i{font-size:1.1rem;color:var(--primary)}

    /* ── BADGE GRID + RPG TOOLTIP ───────────────────────────── */
    .badge-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;}
    
    .badge-item{
      position:relative;
      display:flex;flex-direction:column;align-items:center;gap:5px;
      cursor:pointer;
    }
    
    .badge-circle{
      width:54px;height:54px;border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      font-size:1.5rem;transition:transform 0.2s cubic-bezier(0.34,1.56,0.64,1);
      position:relative;
    }
    .badge-circle.unlocked{background:linear-gradient(135deg,#4DA8DA,#80D8C3);box-shadow:0 4px 14px rgba(77,168,218,0.38);}
    .badge-circle.locked{background:#E8E8E8;filter:grayscale(1) opacity(0.4)}
    .badge-circle.locked i{font-size:1rem;color:#9BBCCF}
    .badge-tier{position:absolute;bottom:-3px;right:-3px;width:16px;height:16px;border-radius:50%;border:2px solid white;display:flex;align-items:center;justify-content:center;font-size:8px;}
    .tier-mudah{background:#80D8C3}
    .tier-sedang{background:var(--yellow)}
    .tier-langka{background:var(--primary)}
    
    .badge-lbl{font-size:0.6rem;font-weight:700;color:var(--muted);text-align:center;max-width:60px;line-height:1.2;}
    .badge-lbl.unlocked-lbl{color:var(--navy)}

    /* Efek Pop Up Kotak RPG */
/* Efek Pop Up Kotak RPG */
    .badge-tooltip {
      position: absolute;
      bottom: 110%;
      left: 50%;
      transform: translateX(-50%) translateY(10px);
      background: #1A2E3A;
      color: #ffffff;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 0.82rem;
      width: 200px; /* Ukuran dipatenkan agar tidak mekar menabrak layar */
      text-align: center;
      opacity: 0;
      visibility: hidden;
      transition: all 0.25s cubic-bezier(0.68, -0.55, 0.27, 1.55);
      z-index: 999;
      pointer-events: none; 
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
      border: 1px solid #4DA8DA;
    }
    .badge-tooltip::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 50%;
      margin-left: -7px;
      border-width: 7px;
      border-style: solid;
      border-color: #1A2E3A transparent transparent transparent;
    }

    /* 🔥 PATCH: Mencegah Terpotong di Pinggir Kanan (Item ke-4, 8, 12, dst) */
    .badge-item:nth-child(4n) .badge-tooltip {
      left: auto;
      right: -20px;
      transform: translateY(10px);
    }
    .badge-item:nth-child(4n) .badge-tooltip::after {
      left: auto;
      right: 40px;
    }

    /* 🔥 PATCH: Mencegah Terpotong di Pinggir Kiri (Item ke-1, 5, 9, dst) */
    .badge-item:nth-child(4n+1) .badge-tooltip {
      left: -20px;
      transform: translateY(10px);
    }
    .badge-item:nth-child(4n+1) .badge-tooltip::after {
      left: 40px;
    }
    
    /* Trigger interaksi Hover/Klik */
    .badge-item:hover { z-index: 10; }
    .badge-item:hover .badge-circle { transform: scale(1.15) rotate(-8deg); }
    .badge-item:hover .badge-tooltip,
    .badge-item:active .badge-tooltip {
      opacity: 1;
      visibility: visible;
      /* Pastikan transform X hanya aktif jika tidak dioverride oleh aturan Kiri/Kanan */
      transform: translateY(0) translateX(var(--tx, -50%)); 
    }
    .badge-item:nth-child(4n):hover .badge-tooltip,
    .badge-item:nth-child(4n+1):hover .badge-tooltip {
      --tx: 0%; /* Matikan pergeseran center untuk pinggiran */
    }
    .tooltip-badge-name { font-family: var(--fh); font-weight: 700; color: #FFD66B; margin-bottom: 4px; display: block; font-size: 0.95rem; }
    .tooltip-badge-desc { color: #E2E8F0; line-height: 1.3; display: block; white-space: normal; }
    .badge-status-tag { margin-top: 8px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 4px; }


    .badge-progress-bar{background:white;border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-bottom:12px;box-shadow:0 2px 8px rgba(26,46,58,0.07);border:1px solid rgba(77,168,218,0.1);}
    .bp-icon{font-size:1.2rem}
    .bp-info{flex:1}
    .bp-text{font-size:0.78rem;font-weight:700;color:var(--navy)}
    .bp-bar-bg{height:5px;background:#F0F8FF;border-radius:999px;overflow:hidden;margin-top:4px}
    .bp-bar-fill{height:100%;background:linear-gradient(90deg,#FFD66B,#F0A800);border-radius:999px}
    .bp-pct{font-family:var(--fh);font-size:0.88rem;font-weight:700;color:var(--yellow)}

    /* ── RIWAYAT KUIS ───────────────────────────────────────── */
    .riwayat-list{display:flex;flex-direction:column;gap:8px}
    .riwayat-row{background:white;border-radius:14px;padding:12px 14px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 8px rgba(26,46,58,0.06);border:1.5px solid rgba(77,168,218,0.08);}
    .riwayat-icon{width:40px;height:40px;border-radius:12px;background:#EBF6FC;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--primary);flex-shrink:0;}
    .riwayat-info{flex:1;min-width:0}
    .riwayat-topik{font-family:var(--fh);font-size:0.88rem;font-weight:700;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .riwayat-meta{font-size:0.68rem;color:var(--muted);font-weight:600;margin-top:1px}
    .riwayat-xp{font-family:var(--fh);font-size:0.95rem;font-weight:700;color:var(--primary);flex-shrink:0;}
    .riwayat-empty{text-align:center;padding:28px;background:white;border-radius:14px;border:2px dashed rgba(77,168,218,0.2);}
    .riwayat-empty i{font-size:1.8rem;color:#9BBCCF;display:block;margin-bottom:6px}
    .riwayat-empty p{font-size:0.8rem;color:var(--muted)}

    /* ── AVATAR PICKER MODAL ────────────────────────────────── */
    .overlay{position:fixed;inset:0;background:rgba(26,46,58,0.4);z-index:299;display:none}
    .overlay.show{display:block}
    .avatar-modal{position:fixed;bottom:0;left:50%;transform:translateX(-50%) translateY(100%);width:100%;max-width:480px;background:white;border-radius:24px 24px 0 0;padding:20px;z-index:300;transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1);}
    .avatar-modal.open{transform:translateX(-50%) translateY(0)}
    .modal-handle{width:40px;height:5px;background:rgba(77,168,218,0.2);border-radius:999px;margin:0 auto 16px;}
    .modal-title{font-family:var(--fh);font-size:1.1rem;font-weight:700;color:var(--navy);text-align:center;margin-bottom:16px}
    .avatar-pick-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
    .avatar-pick-btn{width:52px;height:52px;border-radius:14px;background:#F0F8FF;border:2px solid rgba(77,168,218,0.15);display:flex;align-items:center;justify-content:center;font-size:1.6rem;cursor:pointer;transition:all 0.18s cubic-bezier(0.34,1.56,0.64,1);}
    .avatar-pick-btn:hover{background:#EBF6FC;border-color:var(--primary);transform:scale(1.1)}
    .avatar-pick-btn.selected{background:linear-gradient(135deg,#4DA8DA,#80D8C3);border-color:transparent;box-shadow:0 4px 12px rgba(77,168,218,0.4)}

    /* ── BOTTOM NAV ─────────────────────────────────────────── */
    .bottom-nav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:480px;height:68px;background:white;border-top:1.5px solid rgba(77,168,218,0.12);border-radius:20px 20px 0 0;display:flex;align-items:center;justify-content:space-around;padding:0 8px;z-index:100;box-shadow:0 -4px 20px rgba(77,168,218,0.1);}
    .nav-item{display:flex;flex-direction:column;align-items:center;gap:3px;padding:8px 18px;border-radius:14px;cursor:pointer;color:#9BBCCF;font-size:0.68rem;font-weight:700;font-family:var(--fb);text-decoration:none;position:relative;}
    .nav-item i{font-size:1.35rem;transition:transform 0.18s}
    .nav-item.active{color:var(--primary)}
    .nav-item.active i{transform:scale(1.15)}
    .nav-item.active::after{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:32px;height:3px;background:var(--primary);border-radius:0 0 4px 4px;}

    .fade-in{animation:fadeUp 0.4s ease both}
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .d1{animation-delay:0.05s}.d2{animation-delay:0.1s}.d3{animation-delay:0.15s}.d4{animation-delay:0.2s}
  </style>
</head>
<body>
<div class="page">
<div class="scroll-area">

  <div class="hero-profil fade-in">
    <div class="hpg hpg1"></div>
    <div class="hpg hpg2"></div>

    <div class="hp-toolbar">
      <a href="/SAINTARA/pages/beranda.php" class="hp-tool-btn" aria-label="Kembali">
        <i class="bi bi-arrow-left"></i>
      </a>
      <a href="/SAINTARA/api/logout.php" class="hp-tool-btn" aria-label="Logout"
         onclick="return confirm('Yakin mau keluar?')">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>

    <div class="avatar-big-wrap">
      <div class="avatar-ring" onclick="bukaAvatarPicker()" id="avatarRing">
        <span id="avatarDisplay"><?= htmlspecialchars($siswa['avatar'] ?: '🐘') ?></span>
      </div>
      <div class="avatar-edit-btn" onclick="bukaAvatarPicker()">
        <i class="bi bi-pencil-fill"></i>
      </div>
    </div>

    <div class="profil-name"><?= htmlspecialchars($siswa['nama']) ?></div>
    <div class="profil-kelas">Kelas <?= $siswa['kelas'] ?> &middot; Peringkat #<?= $rankPos ?> di Saintara</div>
    <div class="profil-level-badge">
      <i class="bi bi-award-fill"></i>
      Level <?= $siswa['level'] ?> — <?= htmlspecialchars($levelName) ?>
    </div>

    <svg class="wave-btm" viewBox="0 0 1440 48" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,24 C360,48 1080,0 1440,24 L1440,48 L0,48 Z" fill="#F0F8FF"/>
    </svg>
  </div>

  <div class="stats-float">
    <div class="stat-item">
      <i class="bi bi-star-fill" style="color:#F0A800"></i>
      <div class="stat-num"><?= number_format($siswa['xp']) ?></div>
      <div class="stat-lbl">Total XP</div>
    </div>
    <div class="stat-item">
      <i class="bi bi-fire" style="color:#E08000"></i>
      <div class="stat-num"><?= $siswa['streak'] ?></div>
      <div class="stat-lbl">Streak</div>
    </div>
    <div class="stat-item">
      <i class="bi bi-check2-circle" style="color:#0F7A5A"></i>
      <div class="stat-num"><?= $siswa['total_benar'] ?></div>
      <div class="stat-lbl">Benar</div>
    </div>
    <div class="stat-item">
      <i class="bi bi-journals" style="color:#4DA8DA"></i>
      <div class="stat-num"><?= $topikSelesai ?></div>
      <div class="stat-lbl">Topik</div>
    </div>
  </div>

  <div class="xp-prog-card fade-in d1" id="xpProgCard">
    <div class="xp-prog-top">
      <div class="xp-prog-title">
        <i class="bi bi-graph-up-arrow" style="color:var(--primary);margin-right:5px"></i>
        Progress Level
      </div>
      <div class="xp-prog-pct"><?= $levelProg ?>%</div>
    </div>
    <div class="xp-bar-bg">
      <div class="xp-bar-fill" id="xpBarFill" style="width:0%" data-target="<?= $levelProg ?>"></div>
    </div>
    <div class="xp-bar-labels">
      <span><?= number_format($siswa['xp']) ?> XP</span>
      <span>
        <?php if ($siswa['level'] < 5): ?>
        <?= number_format($xpToNext) ?> XP lagi → <?= getLevelName($siswa['level'] + 1) ?>
        <?php else: ?>
        Level Maksimal! 🎉
        <?php endif; ?>
      </span>
    </div>
  </div>

  <?php if ($xpSesi): ?>
  <div style="margin:10px 16px 0;background:linear-gradient(135deg,#E1F5EE,#C8F0E3);border:1.5px solid var(--mint);border-radius:16px;padding:12px 16px;display:flex;align-items:center;gap:12px">
    <i class="bi bi-lightning-charge-fill" style="font-size:1.4rem;color:#0A5A40"></i>
    <div style="flex:1">
      <div style="font-family:var(--fh);font-size:0.85rem;font-weight:700;color:#0A5A40"><?= htmlspecialchars($xpSesi['sesi_nama']) ?></div>
      <div style="font-size:0.7rem;color:#2A7A60;font-weight:600">XP kamu di sesi ini</div>
    </div>
    <div style="font-family:var(--fh);font-size:1.3rem;font-weight:700;color:#0F7A5A">
      <?= number_format($xpSesi['xp']) ?> XP
    </div>
  </div>
  <?php endif; ?>

  <div class="section fade-in d2" id="badges">
    <div class="sec-title">
      <i class="bi bi-patch-star-fill"></i>
      Koleksi Badge
    </div>

    <div class="badge-progress-bar">
      <div class="bp-icon">🏅</div>
      <div class="bp-info">
        <div class="bp-text"><?= count($badgeSiswa) ?> dari <?= count($allBadges) ?> badge terkumpul</div>
        <div class="bp-bar-bg">
          <div class="bp-bar-fill" style="width:<?= count($allBadges) > 0 ? round((count($badgeSiswa)/count($allBadges))*100) : 0 ?>%"></div>
        </div>
      </div>
      <div class="bp-pct"><?= count($allBadges) > 0 ? round((count($badgeSiswa)/count($allBadges))*100) : 0 ?>%</div>
    </div>

    <div class="badge-grid">
      <?php 
      $allBadges = is_array($allBadges) ? $allBadges : [];
      $badgeKeys = is_array($badgeKeys) ? $badgeKeys : [];
      
      foreach ($allBadges as $key => $badge):
        $unlocked = in_array($key, $badgeKeys);
      ?>
      <div class="badge-item">
        
        <div class="badge-circle <?= $unlocked ? 'unlocked' : 'locked' ?>">
          <?php if ($unlocked): ?>
            <?= htmlspecialchars($badge['icon'] ?? '🏅') ?>
            <div class="badge-tier tier-<?= $badge['tier'] ?? 'mudah' ?>"></div>
          <?php else: ?>
            <i class="bi bi-lock-fill"></i>
          <?php endif; ?>
        </div>
        
        <div class="badge-lbl <?= $unlocked ? 'unlocked-lbl' : '' ?>">
          <?= htmlspecialchars($badge['nama'] ?? 'Lencana') ?>
        </div>

        <div class="badge-tooltip">
            <span class="tooltip-badge-name"><?= htmlspecialchars($badge['nama'] ?? 'Lencana') ?></span>
            <span class="tooltip-badge-desc"><?= htmlspecialchars($badge['deskripsi'] ?? 'Misi rahasia dari Ella...') ?></span>
            
            <?php if ($unlocked): ?>
                <span class="badge-status-tag" style="color: #80D8C3;"><i class="bi bi-check-circle-fill"></i> Telah Dibuka</span>
            <?php else: ?>
                <div style="margin-top: 10px; font-size: 0.75rem; color: #9BBCCF; background: rgba(255,255,255,0.1); padding: 6px; border-radius: 8px; text-align: left; line-height: 1.4;">
                    <strong style="color: #FFD66B;">Tugas:</strong><br>
                    <?php 
                        // AMAN DARI ERROR: Gunakan null coalescing operator (??)
                        $tipeSyarat = $badge['syarat_tipe'] ?? '';
                        $nilaiSyarat = $badge['syarat_nilai'] ?? '';

                        if ($tipeSyarat == 'total_benar') {
                            echo 'Jawab ' . htmlspecialchars((string)$nilaiSyarat) . ' soal dengan benar secara kumulatif.';
                        } elseif ($tipeSyarat == 'login_streak') {
                            echo 'Belajar ' . htmlspecialchars((string)$nilaiSyarat) . ' hari berturut-turut tanpa putus.';
                        } elseif ($tipeSyarat == 'topik_selesai') {
                            echo 'Selesaikan ' . htmlspecialchars((string)$nilaiSyarat) . ' topik materi.';
                        } else {
                            echo 'Penuhi syarat rahasia sistem.';
                        }
                    ?>
                </div>
                <span class="badge-status-tag" style="color: #E05555;"><i class="bi bi-lock-fill"></i> Terkunci</span>
            <?php endif; ?>
        </div>

      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="section fade-in d3">
    <div class="sec-title">
      <i class="bi bi-clock-history"></i>
      Riwayat Kuis
    </div>
    <?php if (empty($riwayat)): ?>
    <div class="riwayat-empty">
      <i class="bi bi-inbox"></i>
      <p>Belum ada riwayat kuis.<br>Yuk mulai kuis pertamamu!</p>
    </div>
    <?php else: ?>
    <div class="riwayat-list">
      <?php foreach ($riwayat as $r):
        $total = $r['benar'] + $r['salah'];
        $pct   = $total > 0 ? round(($r['benar']/$total)*100) : 0;
        $tgl   = date('d M', strtotime($r['played_at']));
      ?>
      <div class="riwayat-row">
        <div class="riwayat-icon"><i class="bi bi-pencil-square"></i></div>
        <div class="riwayat-info">
          <div class="riwayat-topik">
            <?= htmlspecialchars($r['topik_nama'] ?? 'Kuis Bebas') ?>
          </div>
          <div class="riwayat-meta">
            <?= $r['benar'] ?>✓ <?= $r['salah'] ?>✗ &middot; <?= $pct ?>% &middot; <?= $tgl ?>
          </div>
        </div>
        <div class="riwayat-xp">+<?= $r['xp_gained'] ?> XP</div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div style="height:16px"></div>
</div>

<nav class="bottom-nav" role="navigation">
  <a class="nav-item" href="/SAINTARA/pages/beranda.php"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
  <a class="nav-item" href="/SAINTARA/pages/materi.php"><i class="bi bi-book-half"></i><span>Materi</span></a>
  <a class="nav-item" href="/SAINTARA/pages/ranking.php"><i class="bi bi-bar-chart-fill"></i><span>Ranking</span></a>
  <a class="nav-item active" href="/SAINTARA/pages/profil.php"><i class="bi bi-person-circle"></i><span>Profil</span></a>
</nav>

<div class="overlay" id="overlay" onclick="tutupAvatarPicker()"></div>
<div class="avatar-modal" id="avatarModal">
  <div class="modal-handle"></div>
  <div class="modal-title">Pilih Avatar Kamu</div>
  <div class="avatar-pick-grid" id="avatarPickGrid">
    <?php foreach ($avatarList as $av): ?>
    <div class="avatar-pick-btn <?= $siswa['avatar']===$av?'selected':'' ?>"
         onclick="pilihAvatar('<?= $av ?>')"><?= $av ?></div>
    <?php endforeach; ?>
  </div>
</div>

</div>
<script>
  // XP bar animasi
  window.addEventListener('load', () => {
    const bar = document.getElementById('xpBarFill');
    if (bar) setTimeout(() => { bar.style.width = bar.dataset.target + '%'; }, 400);
  });

  // Avatar picker
  function bukaAvatarPicker() {
    document.getElementById('overlay').classList.add('show');
    document.getElementById('avatarModal').classList.add('open');
  }
  function tutupAvatarPicker() {
    document.getElementById('overlay').classList.remove('show');
    document.getElementById('avatarModal').classList.remove('open');
  }
  function pilihAvatar(av) {
    document.getElementById('avatarDisplay').textContent = av;
    document.querySelectorAll('.avatar-pick-btn').forEach(b => {
      b.classList.toggle('selected', b.textContent === av);
    });
    // Simpan ke server
    fetch('/SAINTARA/api/update-avatar.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'avatar=' + encodeURIComponent(av)
    }).then(() => setTimeout(tutupAvatarPicker, 300));
  }
</script>
<?php include '../includes/audio-global.php'; ?>
</body>
</html>