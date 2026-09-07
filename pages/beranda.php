<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/beranda.php — Halaman Beranda Siswa
// ============================================================
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once '../includes/header.php';
require_once '../includes/session.php';
$pageTitle  = 'Beranda';
$activePage = 'beranda';

requireSiswa(); 

$pdo = getDB();

// =========================================================
// PASTE KODE LOGIKA SESI DI SINI (BAGIAN ATAS)
// =========================================================
$stmtSesi = $pdo->prepare("SELECT id, nama, kelas FROM sesi WHERE aktif = 1 ORDER BY mulai DESC LIMIT 1");
$stmtSesi->execute();
$sesiAktif = $stmtSesi->fetch();

$tampilkanSesi = false;
$sudahMengerjakan = false;

if ($sesiAktif) {
    $kelasSesi = (int)($sesiAktif['kelas'] ?? 0);
    $kelasSiswa = (int)$_SESSION['siswa_kelas'];
    
    if ($kelasSesi === 0 || $kelasSesi === $kelasSiswa) {
        $tampilkanSesi = true;
        $stmtCek = $pdo->prepare("SELECT id FROM xp_sesi WHERE sesi_id = ? AND siswa_id = ?");
        $stmtCek->execute([$sesiAktif['id'], $_SESSION['siswa_id']]);
        if ($stmtCek->fetch()) {
            $sudahMengerjakan = true;
        }
    }
}

if (empty($_SESSION['siswa_id'])) {
    header('Location: /SAINTARA/login.php'); exit;
}

// Ambil data siswa fresh dari DB
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$_SESSION['siswa_id']]);
$siswa = $stmt->fetch();
if (!$siswa) { session_destroy(); header('Location: /SAINTARA/login.php'); exit; }

// Sync session
$_SESSION['siswa_xp']    = $siswa['xp'];
$_SESSION['siswa_level'] = $siswa['level'];

$levelName     = getLevelName($siswa['level']);
$levelProgress = getLevelProgress($siswa['xp']);
$levelThresh   = getLevelThreshold($siswa['level']);
$xpNeeded      = $levelThresh['max'] - $siswa['xp'];
$badgeList     = getBadgeSiswa($siswa['id'], $pdo);

// Progress topik per kelas siswa
$stmt = $pdo->prepare("
    SELECT t.kelas, COUNT(t.id) as total,
           SUM(CASE WHEN p.selesai=1 THEN 1 ELSE 0 END) as selesai
    FROM topik t
    LEFT JOIN progress p ON p.topik_id = t.id AND p.siswa_id = ?
    WHERE t.kelas = ?
    GROUP BY t.kelas
");

// Cek apakah ada Sesi Ujian (Try Out) yang sedang aktif
$stmtSesi = $pdo->prepare("SELECT id, nama, kelas FROM sesi WHERE aktif = 1 ORDER BY mulai DESC LIMIT 1");
$stmtSesi->execute();
$sesiAktif = $stmtSesi->fetch();

$tampilkanSesi = false;
if ($sesiAktif) {
    // Tampilkan jika sesi untuk semua kelas (NULL/0) ATAU sesuai kelas siswa
    $kelasSesi = (int)($sesiAktif['kelas'] ?? 0);
    $kelasSiswa = (int)$_SESSION['siswa_kelas'];
    
    if ($kelasSesi === 0 || $kelasSesi === $kelasSiswa) {
        $tampilkanSesi = true;
    }
}
$stmt->execute([$siswa['id'], $siswa['kelas']]);
$progressKelas = $stmt->fetch();
$totalTopik    = $progressKelas['total']  ?? 4;
$topikSelesai  = $progressKelas['selesai'] ?? 0;

// Top 3 ranking
$ranking = getRanking($pdo, null, 3);

// Greeting berdasarkan waktu
$jam = (int) date('H');
if ($jam < 11)      $greeting = 'Selamat Pagi';
elseif ($jam < 15)  $greeting = 'Selamat Siang';
elseif ($jam < 18)  $greeting = 'Selamat Sore';
else                $greeting = 'Selamat Malam';

$namaDepan = explode(' ', $siswa['nama'])[0];

// Topik materi kelas siswa
$stmtTopik = $pdo->prepare("
    SELECT t.*, COALESCE(p.selesai, 0) as sudah_selesai, COALESCE(p.skor, 0) as skor
    FROM topik t
    LEFT JOIN progress p ON p.topik_id = t.id AND p.siswa_id = ?
    WHERE t.kelas = ?
    ORDER BY t.urutan
    LIMIT 4
");
$stmtTopik->execute([$siswa['id'], $siswa['kelas']]);
$topikList = $stmtTopik->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title>Beranda — Saintara</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/SAINTARA/assets/css/style.css">
  <style>
    /* ── BERANDA SPECIFIC ───────────────────────────────────── */
    body { background: #F0F8FF; }

    .page { max-width: 480px; margin: 0 auto; min-height: 100vh; position: relative; background: #F0F8FF; }

    /* Scroll area above bottom nav */
    .scroll-area {
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      padding-bottom: calc(68px + 24px);
    }

    /* ── HERO HEADER ───────────────────────────────────────── */
    .hero {
      background: linear-gradient(155deg, #3A90C2 0%, #4DA8DA 55%, #6EC5E0 100%);
      padding: 20px 20px 60px;
      position: relative;
      overflow: hidden;
    }

    .hero-geo {
      position: absolute;
      opacity: 0.08;
      pointer-events: none;
    }
    .hg1 { width:180px;height:180px;border:3px solid white;border-radius:50%;top:-70px;right:-50px; }
    .hg2 { width:90px;height:90px;background:white;border-radius:50%;bottom:10px;left:-30px; }
    .hg3 { width:45px;height:45px;background:#FFD66B;border-radius:12px;transform:rotate(20deg);top:16px;right:100px;opacity:0.25; }

    .hero-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      position: relative;
      z-index: 2;
    }

    .greeting-wrap { flex: 1; }

    .greeting-text {
      font-size: 0.78rem;
      color: rgba(255,255,255,0.78);
      font-weight: 600;
    }

    .greeting-name {
      font-family: 'Fredoka', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff;
      line-height: 1.1;
      margin-top: 2px;
    }

    .avatar-btn {
      width: 46px; height: 46px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      border: 2.5px solid rgba(255,255,255,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: transform 0.18s, background 0.18s;
      flex-shrink: 0;
      text-decoration: none;
    }
    .avatar-btn:hover { background: rgba(255,255,255,0.3); transform: scale(1.05); }
    .avatar-btn i { font-size: 1.4rem; color: white; }

    /* XP Card besar */
    .xp-card {
      background: rgba(255,255,255,0.15);
      border: 1.5px solid rgba(255,255,255,0.28);
      border-radius: 20px;
      padding: 16px 18px;
      backdrop-filter: blur(8px);
      position: relative;
      z-index: 2;
    }

    .xp-card-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .level-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(255,255,255,0.22);
      border: 1.5px solid rgba(255,255,255,0.35);
      border-radius: 999px;
      padding: 4px 12px;
      font-family: 'Fredoka', sans-serif;
      font-size: 0.82rem;
      font-weight: 600;
      color: white;
    }
    .level-pill i { font-size: 0.95rem; }

    .xp-numbers {
      text-align: right;
    }
    .xp-big {
      font-family: 'Fredoka', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: #FFD66B;
      line-height: 1;
    }
    .xp-sub {
      font-size: 0.72rem;
      color: rgba(255,255,255,0.7);
      font-weight: 600;
    }

    /* XP Bar */
    .xp-bar-bg {
      height: 10px;
      background: rgba(255,255,255,0.2);
      border-radius: 999px;
      overflow: hidden;
      margin-bottom: 5px;
    }
    .xp-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #FFD66B, #FFB020);
      border-radius: 999px;
      transition: width 1s cubic-bezier(0.4,0,0.2,1);
      position: relative;
      overflow: hidden;
    }
    .xp-bar-fill::after {
      content:'';
      position:absolute;
      top:0;left:-100%;right:0;bottom:0;
      background:linear-gradient(90deg,transparent,rgba(255,255,255,0.45),transparent);
      animation: barShimmer 2s infinite;
    }
    @keyframes barShimmer {
      from{left:-100%} to{left:100%}
    }
    .xp-bar-label {
      display: flex;
      justify-content: space-between;
      font-size: 0.7rem;
      color: rgba(255,255,255,0.65);
      font-weight: 600;
    }

    /* Wave di bawah hero */
    .hero-wave {
      position: absolute;
      bottom: -1px; left: 0; right: 0;
    }

    /* ── STAT STRIP ─────────────────────────────────────────── */
    .stat-strip {
      display: flex;
      gap: 10px;
      padding: 0 16px;
      margin-top: -24px;
      position: relative;
      z-index: 10;
    }

    .stat-chip {
      flex: 1;
      background: white;
      border-radius: 16px;
      padding: 12px 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      box-shadow: 0 2px 12px rgba(26,46,58,0.09);
      border: 1px solid rgba(77,168,218,0.1);
      transition: transform 0.18s;
    }
    .stat-chip:hover { transform: translateY(-2px); }

    .stat-chip-icon {
      width: 36px; height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      margin-bottom: 2px;
    }
    .ic-fire  { background: #FFF3D0; color: #E08000; }
    .ic-check { background: #E1F5EE; color: #0F7A5A; }
    .ic-star  { background: #EBF4FF; color: #1873CC; }

    .stat-chip-num {
      font-family: 'Fredoka', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: #1A2E3A;
      line-height: 1;
    }
    .stat-chip-lbl {
      font-size: 0.68rem;
      color: #6B8899;
      font-weight: 700;
      text-align: center;
    }

    /* ── SECTION ────────────────────────────────────────────── */
    .section { padding: 20px 16px 0; }

    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .section-title {
      font-family: 'Fredoka', sans-serif;
      font-size: 1.05rem;
      font-weight: 700;
      color: #1A2E3A;
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .section-title i { font-size: 1.1rem; color: #4DA8DA; }

    .section-link {
      font-size: 0.78rem;
      font-weight: 700;
      color: #4DA8DA;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .section-link:hover { color: #3A90C2; }

    /* ── DAILY CHALLENGE ───────────────────────────────────── */
    .daily-card {
      background: linear-gradient(135deg, #E8F8F4 0%, #D0F5EC 100%);
      border: 2px solid #80D8C3;
      border-radius: 18px;
      padding: 16px;
      display: flex;
      align-items: center;
      gap: 14px;
      cursor: pointer;
      transition: transform 0.18s, box-shadow 0.18s;
      text-decoration: none;
    }
    .daily-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(128,216,195,0.35); }
    .daily-card:active { transform: scale(0.97); }

    .daily-icon-wrap {
      width: 56px; height: 56px;
      background: white;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(128,216,195,0.3);
    }
    .daily-icon-wrap i { font-size: 1.6rem; color: #0F7A5A; }

    .daily-info { flex: 1; }
    .daily-title {
      font-family: 'Fredoka', sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      color: #1A2E3A;
    }
    .daily-desc {
      font-size: 0.75rem;
      color: #3D7A6E;
      margin-top: 2px;
    }
    .daily-xp {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: white;
      border-radius: 999px;
      padding: 3px 10px;
      font-size: 0.75rem;
      font-weight: 800;
      color: #0F7A5A;
      margin-top: 6px;
      border: 1px solid rgba(128,216,195,0.4);
    }
    .daily-xp i { font-size: 0.8rem; }

    .daily-arrow {
      width: 32px; height: 32px;
      background: #80D8C3;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .daily-arrow i { font-size: 0.9rem; color: white; }

    /* ── TOPIK MATERI CARD ──────────────────────────────────── */
    .topik-list { display: flex; flex-direction: column; gap: 10px; }

    .topik-card {
      background: white;
      border-radius: 16px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 2px 10px rgba(26,46,58,0.07);
      border: 1.5px solid rgba(77,168,218,0.1);
      cursor: pointer;
      text-decoration: none;
      transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s;
    }
    .topik-card:hover { transform: translateX(4px); border-color: #4DA8DA; box-shadow: 0 4px 16px rgba(77,168,218,0.18); }
    .topik-card:active { transform: scale(0.97); }

    .topik-icon-box {
      width: 48px; height: 48px;
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .topik-icon-box i { font-size: 1.4rem; }
    .tib-1 { background: #EBF6FC; color: #4DA8DA; }
    .tib-2 { background: #E1F5EE; color: #0F7A5A; }
    .tib-3 { background: #FFF8E6; color: #C07A00; }
    .tib-4 { background: #FFE8E8; color: #C04040; }

    .topik-info { flex: 1; min-width: 0; }
    .topik-title {
      font-family: 'Fredoka', sans-serif;
      font-size: 0.92rem;
      font-weight: 700;
      color: #1A2E3A;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .topik-prog-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 5px;
    }
    .topik-prog-bg {
      flex: 1;
      height: 5px;
      background: #F0F8FF;
      border-radius: 999px;
      overflow: hidden;
    }
    .topik-prog-fill {
      height: 100%;
      background: linear-gradient(90deg, #4DA8DA, #80D8C3);
      border-radius: 999px;
    }
    .topik-prog-pct {
      font-size: 0.68rem;
      font-weight: 800;
      color: #6B8899;
      flex-shrink: 0;
    }

    .topik-badge-done {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 28px; height: 28px;
      background: #E1F5EE;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .topik-badge-done i { font-size: 0.9rem; color: #0F7A5A; }

    .topik-badge-go {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 28px; height: 28px;
      background: #EBF6FC;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .topik-badge-go i { font-size: 0.9rem; color: #4DA8DA; }

    /* Topik empty state */
    .topik-empty {
      background: white;
      border-radius: 16px;
      padding: 28px;
      text-align: center;
      border: 2px dashed rgba(77,168,218,0.2);
    }
    .topik-empty i { font-size: 2rem; color: #9BBCCF; display: block; margin-bottom: 8px; }
    .topik-empty p { font-size: 0.85rem; color: #6B8899; }

    /* ── MINI LEADERBOARD ───────────────────────────────────── */
    .leaderboard { display: flex; flex-direction: column; gap: 8px; }

    .rank-row {
      background: white;
      border-radius: 14px;
      padding: 11px 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 1px 6px rgba(26,46,58,0.07);
      border: 1.5px solid transparent;
    }
    .rank-row.me { border-color: #4DA8DA; background: #EBF6FC; }
    .rank-row.gold   { border-color: #FFD700; }
    .rank-row.silver { border-color: #C0C0C0; }
    .rank-row.bronze { border-color: #CD7F32; }

    .rank-pos {
      font-family: 'Fredoka', sans-serif;
      font-size: 1.1rem;
      font-weight: 700;
      width: 26px;
      text-align: center;
      flex-shrink: 0;
    }
    .rank-pos.p1 { color: #FFB800; }
    .rank-pos.p2 { color: #909090; }
    .rank-pos.p3 { color: #CD7F32; }

    .rank-ava {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4DA8DA, #80D8C3);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .rank-ava i { font-size: 1.1rem; color: white; }

    .rank-name {
      flex: 1;
      font-weight: 700;
      font-size: 0.88rem;
      color: #1A2E3A;
    }
    .rank-kelas {
      font-size: 0.68rem;
      color: #6B8899;
      margin-top: 1px;
    }

    .rank-xp-val {
      font-family: 'Fredoka', sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      color: #4DA8DA;
      flex-shrink: 0;
    }

    /* ── BADGE STRIP ───────────────────────────────────────── */
    .badge-scroll {
      display: flex;
      gap: 10px;
      overflow-x: auto;
      padding-bottom: 6px;
      scrollbar-width: none;
    }
    .badge-scroll::-webkit-scrollbar { display: none; }

    .badge-chip {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 5px;
      flex-shrink: 0;
    }

    .badge-circle {
      width: 52px; height: 52px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4DA8DA, #80D8C3);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      box-shadow: 0 4px 12px rgba(77,168,218,0.32);
      transition: transform 0.18s;
    }
    .badge-circle:hover { transform: scale(1.1) rotate(-5deg); }
    .badge-circle.locked {
      background: #E8E8E8;
      filter: grayscale(1) opacity(0.45);
      box-shadow: none;
    }

    .badge-lbl {
      font-size: 0.62rem;
      font-weight: 700;
      color: #6B8899;
      text-align: center;
      max-width: 56px;
      line-height: 1.2;
    }

    /* ── BOTTOM NAV ─────────────────────────────────────────── */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100%;
      max-width: 480px;
      height: 68px;
      background: white;
      border-top: 1.5px solid rgba(77,168,218,0.12);
      border-radius: 20px 20px 0 0;
      display: flex;
      align-items: center;
      justify-content: space-around;
      padding: 0 8px;
      z-index: 100;
      box-shadow: 0 -4px 20px rgba(77,168,218,0.1);
    }

    .nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      padding: 8px 18px;
      border-radius: 14px;
      cursor: pointer;
      color: #9BBCCF;
      font-size: 0.68rem;
      font-weight: 700;
      font-family: 'Nunito', sans-serif;
      transition: color 0.15s, background 0.15s;
      text-decoration: none;
      -webkit-tap-highlight-color: transparent;
      position: relative;
    }
    .nav-item i { font-size: 1.35rem; transition: transform 0.18s; }
    .nav-item.active { color: #4DA8DA; }
    .nav-item.active i { transform: scale(1.15); }
    .nav-item.active::after {
      content: '';
      position: absolute;
      top: 0; left: 50%;
      transform: translateX(-50%);
      width: 32px; height: 3px;
      background: #4DA8DA;
      border-radius: 0 0 4px 4px;
    }
    .nav-item:active { transform: scale(0.9); }

    /* ── ELLA FLOAT ─────────────────────────────────────────── */
    .ella-float {
      position: fixed;
      bottom: calc(68px + 14px);
      right: 16px;
      z-index: 200;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 8px;
    }

    .ella-bubble-tip {
      background: white;
      border-radius: 14px 14px 0 14px;
      padding: 8px 12px;
      font-size: 0.75rem;
      font-weight: 700;
      color: #1A2E3A;
      box-shadow: 0 4px 14px rgba(26,46,58,0.12);
      max-width: 150px;
      text-align: right;
      border: 1px solid rgba(77,168,218,0.15);
      display: none;
    }
    .ella-float-btn {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4DA8DA, #3A90C2);
      border: 3px solid white;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 20px rgba(77,168,218,0.42);
      cursor: pointer;
      transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.18s;
    }
    .ella-float-btn:hover { transform: scale(1.1); box-shadow: 0 8px 28px rgba(77,168,218,0.55); }
    .ella-float-btn:active { transform: scale(0.93); }
    .ella-float-btn i { font-size: 1.5rem; color: white; }

    /* Page enter animation */
    .fade-in {
      animation: fadeInUp 0.45s ease both;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .delay-1 { animation-delay: 0.05s; }
    .delay-2 { animation-delay: 0.1s; }
    .delay-3 { animation-delay: 0.15s; }
    .delay-4 { animation-delay: 0.2s; }
  </style>
</head>
<body>
<div class="page">
<div class="scroll-area">

  <!-- ── HERO ───────────────────────────────────────────────── -->
  <div class="hero fade-in">
    <div class="hero-geo hg1"></div>
    <div class="hero-geo hg2"></div>
    <div class="hero-geo hg3"></div>

    <div class="hero-top">
      <div class="greeting-wrap">
        <div class="greeting-text"><?= htmlspecialchars($greeting) ?>,</div>
        <div class="greeting-name"><?= htmlspecialchars($namaDepan) ?>! <span style="font-size:1.1rem">👋</span></div>
      </div>
      <a href="/SAINTARA/pages/profil.php" class="avatar-btn" aria-label="Lihat profil">
        <i class="bi bi-person-circle"></i>
      </a>
    </div>

    <!-- XP Card -->
    <div class="xp-card">
      <div class="xp-card-top">
        <div class="level-pill">
          <i class="bi bi-award-fill"></i>
          Level <?= $siswa['level'] ?> — <?= htmlspecialchars($levelName) ?>
        </div>
        <div class="xp-numbers">
          <div class="xp-big"><?= number_format($siswa['xp']) ?></div>
          <div class="xp-sub">Total XP</div>
        </div>
      </div>
      <div class="xp-bar-bg">
        <div class="xp-bar-fill" id="xpBar" style="width: 0%" data-target="<?= $levelProgress ?>"></div>
      </div>
      <div class="xp-bar-label">
        <span><?= number_format($siswa['xp']) ?> XP</span>
        <span><?= $siswa['level'] < 5 ? number_format($levelThresh['max']) . ' XP untuk naik level' : 'Level Maksimal!' ?></span>
      </div>
    </div>

    <svg class="hero-wave" viewBox="0 0 1440 48" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,24 C360,48 1080,0 1440,24 L1440,48 L0,48 Z" fill="#F0F8FF"/>
    </svg>
  </div>

  <!-- ── STAT STRIP ─────────────────────────────────────────── -->
  <div class="stat-strip fade-in delay-1">
    <div class="stat-chip">
      <div class="stat-chip-icon ic-fire">
        <i class="bi bi-fire"></i>
      </div>
      <div class="stat-chip-num"><?= $siswa['streak'] ?></div>
      <div class="stat-chip-lbl">Streak</div>
    </div>
    <div class="stat-chip">
      <div class="stat-chip-icon ic-check">
        <i class="bi bi-check2-circle"></i>
      </div>
      <div class="stat-chip-num"><?= $siswa['total_benar'] ?></div>
      <div class="stat-chip-lbl">Soal Benar</div>
    </div>
    <div class="stat-chip">
      <div class="stat-chip-icon ic-star">
        <i class="bi bi-patch-star-fill"></i>
      </div>
      <div class="stat-chip-num"><?= count($badgeList) ?></div>
      <div class="stat-chip-lbl">Badge</div>
    </div>
  </div>

<!-- Konten halaman beranda lainnya (seperti profil ringkas, sapaan Ella) -->
<!-- ========================================================= -->
<?php if ($tampilkanSesi): ?>
<div style="margin: 0 20px 20px; background: linear-gradient(135deg, #FFD66B, #F0A800); border-radius: 20px; padding: 18px 20px; box-shadow: 0 8px 24px rgba(240, 168, 0, 0.3); position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 12px; <?= !$sudahMengerjakan ? 'animation: pulseBanner 2s infinite;' : '' ?>">
    
    <i class="bi bi-stars" style="position: absolute; top: -10px; right: -10px; font-size: 5rem; color: rgba(255,255,255,0.2); pointer-events: none;"></i>

    <div style="position: relative; z-index: 2;">
        <div style="font-family: 'Fredoka', sans-serif; font-size: 0.85rem; font-weight: 700; color: #1A2E3A; background: white; padding: 4px 10px; border-radius: 999px; display: inline-block; margin-bottom: 8px;">
            🚨 UJIAN TERSEDIA
        </div>
        <h3 style="font-family: 'Fredoka', sans-serif; font-size: 1.3rem; font-weight: 700; color: #1A2E3A; margin: 0 0 4px;">
            <?= htmlspecialchars($sesiAktif['nama']) ?>
        </h3>
        <p style="font-size: 0.82rem; color: rgba(26,46,58,0.85); font-weight: 600; line-height: 1.4; margin: 0;">
            Ujian campuran dari seluruh materi. Peringatan: <strong>Hanya bisa dikerjakan 1 kali!</strong>
        </p>
    </div>
    
    <?php if ($sudahMengerjakan): ?>
        <a href="/SAINTARA/pages/ranking.php?tab=sesi" style="position: relative; z-index: 2; background: #0F7A5A; color: #fff; font-family: 'Fredoka', sans-serif; font-weight: 700; font-size: 1rem; text-align: center; padding: 12px; border-radius: 14px; text-decoration: none; display: flex; justify-content: center; align-items: center; gap: 8px;">
            <i class="bi bi-check-circle-fill"></i> Sudah Selesai! Lihat Peringkat
        </a>
    <?php else: ?>
        <a href="/SAINTARA/pages/kuis.php?sesi_id=<?= $sesiAktif['id'] ?>" style="position: relative;  z-index: 2; background: #1A2E3A; color: #FFD66B; font-family: 'Fredoka', sans-serif; font-weight: 700; font-size: 1rem; text-align: center; padding: 12px; border-radius: 14px; text-decoration: none; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(26,46,58,0.2); transition: transform 0.2s;" onclick="return confirm('Kamu hanya bisa mengerjakan ujian ini SATU KALI. Sudah siap?')">
            Mulai Try Out Sekarang <i class="bi bi-arrow-right-circle-fill"></i>
        </a>
    <?php endif; ?>
</div>

<style>
    @keyframes pulseBanner { 0% { box-shadow: 0 8px 24px rgba(240, 168, 0, 0.3); } 50% { box-shadow: 0 8px 32px rgba(240, 168, 0, 0.6); } 100% { box-shadow: 0 8px 24px rgba(240, 168, 0, 0.3); } }
</style>
<?php endif; ?>
<!-- ========================================================= -->

  <!-- ── DAILY CHALLENGE ──────────────────────────────────── -->
  <div class="section fade-in delay-2">
    <div class="section-head">
      <div class="section-title">
        <i class="bi bi-lightning-charge-fill"></i>
        Tantangan Hari Ini
      </div>
    </div>
    <a href="/SAINTARA/pages/kuis.php?kelas=<?= $siswa['kelas'] ?>&mode=daily" class="daily-card">
      <div class="daily-icon-wrap">
        <i class="bi bi-journal-bookmark-fill"></i>
      </div>
      <div class="daily-info">
        <div class="daily-title">Kuis Harian Kelas <?= $siswa['kelas'] ?></div>
        <div class="daily-desc">Selesaikan kuis dan dapatkan bonus XP!</div>
        <div class="daily-xp">
          <i class="bi bi-star-fill"></i>
          +<?= getXpReward($siswa['kelas']) * 3 ?> XP Bonus
        </div>
      </div>
      <div class="daily-arrow"><i class="bi bi-arrow-right"></i></div>
    </a>
  </div>

  <!-- ── MATERI KELAS ──────────────────────────────────────── -->
  <div class="section fade-in delay-3">
    <div class="section-head">
      <div class="section-title">
        <i class="bi bi-book-fill"></i>
        Materi Kelas <?= $siswa['kelas'] ?>
      </div>
      <a href="/SAINTARA/pages/materi.php" class="section-link">
        Lihat Semua <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <?php if (empty($topikList)): ?>
    <div class="topik-empty">
      <i class="bi bi-inbox"></i>
      <p>Materi kelas <?= $siswa['kelas'] ?> belum tersedia.<br>Guru sedang menyiapkannya!</p>
    </div>
    <?php else: ?>
    <div class="topik-list">
      <?php
      $topikIcons = [
        ['tib-1','bi-eye-fill'],
        ['tib-2','bi-tree-fill'],
        ['tib-3','bi-lightning-fill'],
        ['tib-4','bi-heart-pulse-fill'],
      ];
      foreach ($topikList as $i => $topik):
        $ic    = $topikIcons[$i % 4];
        $pct   = $topik['sudah_selesai'] ? 100 : ($topik['skor'] > 0 ? 50 : 0);
        $selesai = (bool)$topik['sudah_selesai'];
      ?>
      <a href="/SAINTARA/pages/materi.php?topik=<?= $topik['id'] ?>" class="topik-card">
        <div class="topik-icon-box <?= $ic[0] ?>">
          <i class="bi <?= $ic[1] ?>"></i>
        </div>
        <div class="topik-info">
          <div class="topik-title"><?= htmlspecialchars($topik['judul']) ?></div>
          <div class="topik-prog-wrap">
            <div class="topik-prog-bg">
              <div class="topik-prog-fill" style="width:<?= $pct ?>%"></div>
            </div>
            <span class="topik-prog-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php if ($selesai): ?>
        <div class="topik-badge-done"><i class="bi bi-check-circle-fill"></i></div>
        <?php else: ?>
        <div class="topik-badge-go"><i class="bi bi-play-circle-fill"></i></div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── MINI LEADERBOARD ─────────────────────────────────── -->
  <div class="section fade-in delay-4">
    <div class="section-head">
      <div class="section-title">
        <i class="bi bi-trophy-fill"></i>
        Top Ilmuwan
      </div>
      <a href="/SAINTARA/pages/ranking.php" class="section-link">
        Semua <i class="bi bi-arrow-right"></i>
      </a>
    </div>
    <div class="leaderboard">
      <?php if (empty($ranking)): ?>
      <div class="topik-empty">
        <i class="bi bi-people"></i>
        <p>Belum ada siswa di leaderboard. Jadilah yang pertama!</p>
      </div>
      <?php else: ?>
      <?php
      $posClass  = ['p1','p2','p3'];
      $rowClass  = ['gold','silver','bronze'];
      foreach ($ranking as $idx => $r):
        $isMe = $r['id'] === $siswa['id'];
      ?>
      <div class="rank-row <?= $rowClass[$idx] ?> <?= $isMe ? 'me' : '' ?>">
        <div class="rank-pos <?= $posClass[$idx] ?>"><?= $idx+1 ?></div>
        <div class="rank-ava"><i class="bi bi-person-fill"></i></div>
        <div style="flex:1">
          <div class="rank-name">
            <?= htmlspecialchars($r['nama']) ?>
            <?php if ($isMe): ?><span style="font-size:0.7rem;color:#4DA8DA;font-weight:800"> (Kamu)</span><?php endif; ?>
          </div>
          <div class="rank-kelas">Kelas <?= $r['kelas'] ?> &middot; Level <?= $r['level'] ?></div>
        </div>
        <div class="rank-xp-val"><?= number_format($r['xp']) ?> XP</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── BADGE STRIP ──────────────────────────────────────── -->
  <?php if (!empty($badgeList)): ?>
  <div class="section fade-in delay-4">
    <div class="section-head">
      <div class="section-title">
        <i class="bi bi-patch-check-fill"></i>
        Badge Kamu
      </div>
      <a href="/SAINTARA/pages/profil.php#badges" class="section-link">
        Lihat Semua <i class="bi bi-arrow-right"></i>
      </a>
    </div>
    <div class="badge-scroll">
      <?php foreach (array_slice($badgeList, 0, 8) as $b): ?>
      <div class="badge-chip">
        <div class="badge-circle"><?= $b['icon'] ?></div>
        <div class="badge-lbl"><?= htmlspecialchars($b['nama']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div style="height: 16px"></div>

</div><!-- /.scroll-area -->

<!-- ── BOTTOM NAV ─────────────────────────────────────────── -->
<nav class="bottom-nav" role="navigation" aria-label="Navigasi utama">
  <a class="nav-item active" href="/SAINTARA/pages/beranda.php" aria-label="Beranda">
    <i class="bi bi-house-door-fill"></i>
    <span>Beranda</span>
  </a>
  <a class="nav-item" href="/SAINTARA/pages/materi.php" aria-label="Materi">
    <i class="bi bi-book-half"></i>
    <span>Materi</span>
  </a>
  <a class="nav-item" href="/SAINTARA/pages/ranking.php" aria-label="Ranking">
    <i class="bi bi-bar-chart-fill"></i>
    <span>Ranking</span>
  </a>
  <a class="nav-item" href="/SAINTARA/pages/profil.php" aria-label="Profil">
    <i class="bi bi-person-circle"></i>
    <span>Profil</span>
  </a>
</nav>

<!-- ── ELLA FLOAT BUTTON ─────────────────────────────────── -->
<div class="ella-float" id="ellaFloat" style="display:none" data-hidden-reason="Fitur chat AI (Claude) belum aktif — hapus style ini untuk menampilkan lagi">
  <div class="ella-bubble-tip" id="ellaTip">Mau tanya sesuatu?</div>
  <div class="ella-float-btn" id="ellaBtn" aria-label="Tanya Ella">
    <i class="bi bi-chat-heart-fill"></i>
  </div>
</div>

</div><!-- /.page -->
<script>


  // ==========================================
  // 2. KODE BAWAAN HALAMAN BERANDA (JANGAN DIHAPUS)
  // ==========================================
  
  // XP bar animasi
  window.addEventListener('load', () => {
    const bar = document.getElementById('xpBar');
    if(bar) {
        const target = bar.dataset.target;
        setTimeout(() => { bar.style.width = target + '%'; }, 300);
    }
  });

  // Ella float button
  const ellaBtn = document.getElementById('ellaBtn');
  const ellaTip = document.getElementById('ellaTip');
  let tipShown = false;

  // Tampilkan tip setelah 2 detik
  if(ellaTip) {
      setTimeout(() => {
        ellaTip.style.display = 'block';
        ellaTip.style.animation = 'fadeInUp 0.3s ease';
        tipShown = true;
        setTimeout(() => {
          ellaTip.style.display = 'none';
        }, 3500);
      }, 2000);
  }

  if(ellaBtn) {
      ellaBtn.addEventListener('click', () => {
        // Nanti akan buka panel chat Ella
        // Untuk sekarang redirect ke halaman materi
        window.location.href = '/SAINTARA/pages/materi.php';
      });
  }

  // Data PHP ke JS
  const siswaNama  = <?= json_encode($siswa['nama']) ?>;
  const siswaKelas = <?= (int)$siswa['kelas'] ?>;
  const siswaXP    = <?= (int)$siswa['xp'] ?>;
</script>
<?php include '../includes/audio-global.php'; ?>
</body>
</html>