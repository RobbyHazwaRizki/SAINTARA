<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/admin/dashboard.php — Panel Utama Guru
// ============================================================
require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

requireGuru();
$pdo  = getDB();
$guru = getGuruSession();

// ─── AKSI CEPAT: BUAT / TUTUP SESI DARI DASHBOARD ──────────
$flash = '';
$flash_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'buat_sesi_cepat') {
        $nama = trim($_POST['nama_sesi'] ?? '');
        if (strlen($nama) >= 3) {
            $pdo->exec("UPDATE sesi SET aktif=0, selesai=NOW() WHERE aktif=1 AND selesai IS NULL");
            $pdo->prepare("INSERT INTO sesi (nama, mulai, aktif, dibuat_oleh) VALUES (?,NOW(),1,?)")
                ->execute([$nama, $guru['id']]);
            $flash = "Sesi \"$nama\" berhasil dibuat!";
            $flash_type = 'success';
        }
    } elseif ($act === 'tutup_sesi_cepat') {
        $sesi_id = (int)($_POST['sesi_id'] ?? 0);
        if ($sesi_id) {
            $pdo->prepare("UPDATE sesi SET aktif=0, selesai=NOW() WHERE id=?")->execute([$sesi_id]);
            $flash = 'Sesi berhasil ditutup.';
            $flash_type = 'success';
        }
    }
}

// ─── STATISTIK UTAMA ────────────────────────────────────────
$total_siswa  = (int)$pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$total_soal   = (int)$pdo->query("SELECT COUNT(*) FROM soal")->fetchColumn();
$total_topik  = (int)$pdo->query("SELECT COUNT(*) FROM topik")->fetchColumn();
$total_kuis   = (int)$pdo->query("SELECT COUNT(*) FROM riwayat_kuis")->fetchColumn();
$total_guru   = (int)$pdo->query("SELECT COUNT(*) FROM guru")->fetchColumn();

// Kuis hari ini
$kuis_hari_ini = (int)$pdo->query("SELECT COUNT(*) FROM riwayat_kuis WHERE DATE(played_at) = CURDATE()")->fetchColumn();

// XP total terdistribusi
$total_xp = (int)$pdo->query("SELECT SUM(xp) FROM siswa")->fetchColumn();

// Rata-rata skor
$avg_skor_row = $pdo->query("SELECT AVG(benar/(benar+salah+0.001)*100) FROM riwayat_kuis WHERE (benar+salah) > 0")->fetchColumn();
$avg_skor = round((float)$avg_skor_row);

// ─── SESI AKTIF ─────────────────────────────────────────────
$sesi_aktif = $pdo->query("
    SELECT s.*, g.nama AS nama_guru,
           (SELECT COUNT(*) FROM xp_sesi x WHERE x.sesi_id = s.id) AS peserta
    FROM sesi s LEFT JOIN guru g ON g.id = s.dibuat_oleh
    WHERE s.aktif=1 ORDER BY s.mulai DESC LIMIT 1
")->fetch();

// ─── AKTIVITAS KUIS TERBARU (8 entri) ───────────────────────
$aktivitas = $pdo->query("
    SELECT r.*, si.nama AS nama_siswa, si.kelas, si.avatar,
           COALESCE(t.judul,'—') AS judul_topik
    FROM riwayat_kuis r
    JOIN siswa si ON si.id = r.siswa_id
    LEFT JOIN topik t ON t.id = r.topik_id
    ORDER BY r.played_at DESC
    LIMIT 8
")->fetchAll();

// ─── TOP 5 SISWA ────────────────────────────────────────────
$top_siswa = $pdo->query("
    SELECT nama, kelas, xp, level, avatar,
           RANK() OVER (ORDER BY xp DESC) AS ranking
    FROM siswa ORDER BY xp DESC LIMIT 5
")->fetchAll();

// ─── SISWA PER KELAS (untuk bar chart) ──────────────────────
$per_kelas = [];
for ($k = 1; $k <= 6; $k++) {
    $per_kelas[$k] = (int)$pdo->query("SELECT COUNT(*) FROM siswa WHERE kelas=$k")->fetchColumn();
}
$max_kelas = max($per_kelas) ?: 1;

// ─── TOPIK TERPOPULER ───────────────────────────────────────
$top_topik = $pdo->query("
    SELECT t.judul, t.kelas, t.icon, COUNT(r.id) AS play_count
    FROM riwayat_kuis r
    JOIN topik t ON t.id = r.topik_id
    GROUP BY r.topik_id
    ORDER BY play_count DESC
    LIMIT 5
")->fetchAll();

// ─── GRAFIK AKTIVITAS 7 HARI ─────────────────────────────────
$daily_data = [];
for ($d = 6; $d >= 0; $d--) {
    $label = date('d/m', strtotime("-$d days"));
    $count = (int)$pdo->query("SELECT COUNT(*) FROM riwayat_kuis WHERE DATE(played_at) = DATE(NOW() - INTERVAL $d DAY)")->fetchColumn();
    $daily_data[] = ['label' => $label, 'count' => $count];
}
$max_daily = max(array_column($daily_data, 'count')) ?: 1;

// Helper avatar
$avatars = ['🐘','🦁','🐯','🦊','🐧','🦋','🐬','🦜','🐸','🌟'];
function ava($i) { global $avatars; return $avatars[(int)$i] ?? '🐘'; }

$level_names = [1=>'Penjelajah Baru',2=>'Petualang Muda',3=>'Ilmuwan Cilik',4=>'Peneliti Muda',5=>'Profesor Kecil'];
$level_colors = [1=>'#4DA8DA',2=>'#80D8C3',3=>'#a78bfa',4=>'#f97316',5=>'#FFD66B'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Saintara Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root {
  --primary:   #4DA8DA;
  --secondary: #80D8C3;
  --accent:    #FFD66B;
  --bg:        #F0F8FF;
  --error:     #E05555;
  --text:      #1A2E3A;
  --muted:     #6B8899;
  --white:     #FFFFFF;
  --navy:      #1A2E3A;
  --sidebar-w: 240px;
  --radius:    14px;
  --shadow:    0 4px 20px rgba(77,168,218,.12);
  --shadow-lg: 0 8px 32px rgba(77,168,218,.18);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Nunito', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }
h1,h2,h3,h4 { font-family: 'Fredoka', sans-serif; }

/* ── SIDEBAR ─────────────────────────────── */
.sidebar {
  width: var(--sidebar-w);
  background: var(--navy);
  color: var(--white);
  position: fixed; top:0; left:0;
  height: 100vh;
  display: flex; flex-direction: column;
  z-index: 100; overflow-y: auto;
}
.sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,.08); }
.brand-logo { font-family: 'Fredoka',sans-serif; font-size: 1.4rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 8px; }
.brand-logo span { color: var(--accent); }
.sidebar-brand small { color: var(--muted); font-size: .75rem; display: block; margin-top: 4px; }
.sidebar-nav { padding: 12px 0; flex: 1; }
.nav-label { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: var(--muted); padding: 16px 20px 6px; }
.nav-link { display: flex; align-items: center; gap: 10px; padding: 11px 20px; color: rgba(255,255,255,.65); text-decoration: none; font-size: .9rem; font-weight: 600; transition: all .2s; border-left: 3px solid transparent; }
.nav-link:hover { color: var(--white); background: rgba(255,255,255,.05); }
.nav-link.active { color: var(--white); background: rgba(77,168,218,.15); border-left-color: var(--primary); }
.nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }
.sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.08); }
.guru-info { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.guru-avatar-box { width: 36px; height: 36px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.guru-name { font-size: .85rem; font-weight: 700; color: var(--white); }
.guru-role { font-size: .7rem; color: var(--muted); }
.btn-logout { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 14px; background: rgba(224,85,85,.15); color: #ff8080; border: 1px solid rgba(224,85,85,.25); border-radius: 8px; font-size: .83rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .2s; }
.btn-logout:hover { background: rgba(224,85,85,.3); }

/* ── MAIN ────────────────────────────────── */
.main-content { margin-left: var(--sidebar-w); flex: 1; padding: 28px 32px; }

/* ── TOPBAR ──────────────────────────────── */
.topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.page-title { font-size: 1.8rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 10px; }
.page-title i { color: var(--primary); }
.page-subtitle { font-size: .88rem; color: var(--muted); margin-top: 2px; }
.topbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.date-chip {
  background: var(--white);
  border: 1.5px solid #e2edf5;
  border-radius: 10px;
  padding: 8px 14px;
  font-size: .82rem;
  font-weight: 700;
  color: var(--muted);
  display: flex; align-items: center; gap: 6px;
  box-shadow: var(--shadow);
}

/* ── FLASH TOAST ─────────────────────────── */
.toast-wrap { position: fixed; top: 24px; right: 24px; z-index: 9999; }
.toast { background: var(--white); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 32px rgba(0,0,0,.12); border-left: 4px solid var(--secondary); font-weight: 700; font-size: .88rem; animation: toastIn .3s ease; }
.toast.error { border-left-color: var(--error); }
@keyframes toastIn { from { transform: translateX(40px); opacity: 0; } }

/* ── KPI STAT CARDS ──────────────────────── */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }

.kpi-card {
  background: var(--white);
  border-radius: var(--radius);
  padding: 20px;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
  cursor: default;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
.kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--kc, var(--primary)); }
.kpi-card .bg-icon { position: absolute; right: -8px; bottom: -8px; font-size: 4.5rem; opacity: .05; }
.kpi-icon { width: 44px; height: 44px; border-radius: 12px; background: color-mix(in srgb, var(--kc, var(--primary)) 12%, transparent); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--kc, var(--primary)); margin-bottom: 14px; }
.kpi-val { font-family: 'Fredoka',sans-serif; font-size: 2rem; font-weight: 700; line-height: 1; color: var(--text); }
.kpi-label { font-size: .78rem; color: var(--muted); margin-top: 4px; font-weight: 600; }
.kpi-sub { font-size: .72rem; color: var(--muted); margin-top: 8px; display: flex; align-items: center; gap: 4px; }
.kpi-sub .up { color: var(--secondary); font-weight: 800; }
.kpi-sub .neutral { color: var(--muted); }

/* ── CONTENT GRID ────────────────────────── */
.content-grid { display: grid; grid-template-columns: 1fr 340px; gap: 20px; margin-bottom: 20px; }
.content-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }

/* ── CARD BASE ───────────────────────────── */
.card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
.card-head { padding: 16px 20px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; }
.card-head h3 { font-size: 1rem; display: flex; align-items: center; gap: 8px; }
.card-head h3 i { color: var(--primary); }
.card-body { padding: 20px; }

/* ── SESI AKTIF BANNER ───────────────────── */
.sesi-banner {
  background: linear-gradient(135deg, var(--navy), #1e3a50);
  border-radius: var(--radius);
  padding: 18px 20px;
  color: var(--white);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px;
  margin-bottom: 20px;
  box-shadow: var(--shadow-lg);
}

.sesi-banner-left { display: flex; align-items: center; gap: 12px; }
.live-dot-wrap { position: relative; }
.live-dot { width: 10px; height: 10px; background: var(--secondary); border-radius: 50%; animation: pulse 1.5s infinite; }
.live-ring { position: absolute; inset: -4px; border: 2px solid var(--secondary); border-radius: 50%; opacity: .3; animation: ring 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.85)} }
@keyframes ring  { 0%{transform:scale(1);opacity:.3} 100%{transform:scale(2);opacity:0} }

.sesi-info-text .sesi-tag { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; color: var(--secondary); margin-bottom: 3px; }
.sesi-info-text .sesi-nama-text { font-family: 'Fredoka',sans-serif; font-size: 1.1rem; font-weight: 700; }
.sesi-info-text .sesi-stats-text { font-size: .78rem; color: rgba(255,255,255,.55); margin-top: 2px; }

.sesi-banner-right { display: flex; gap: 8px; flex-wrap: wrap; }

/* No sesi banner */
.no-sesi-banner {
  background: var(--white);
  border: 2px dashed #d0e4ef;
  border-radius: var(--radius);
  padding: 16px 20px;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
  flex-wrap: wrap; gap: 12px;
}
.no-sesi-text { font-size: .88rem; color: var(--muted); display: flex; align-items: center; gap: 8px; font-weight: 600; }
.no-sesi-text i { font-size: 1.1rem; color: var(--accent); }

/* ── AKTIVITAS TABLE ─────────────────────── */
table { width: 100%; border-collapse: collapse; }
thead th { padding: 10px 14px; text-align: left; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); background: #f7fafd; border-bottom: 1px solid #edf2f7; white-space: nowrap; }
tbody tr { border-bottom: 1px solid #f0f4f8; transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f7fafd; }
tbody td { padding: 11px 14px; font-size: .85rem; vertical-align: middle; }

.ava-cell { display: flex; align-items: center; gap: 8px; }
.ava-circle { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg,var(--primary),var(--secondary)); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.student-n { font-weight: 700; font-size: .83rem; }
.student-m { font-size: .7rem; color: var(--muted); }

.score-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: .73rem; font-weight: 800; }
.score-good  { background: #e8f8f5; color: #00897b; }
.score-mid   { background: #fff8e1; color: #f57f17; }
.score-low   { background: #fdecea; color: var(--error); }

.time-ago { font-size: .72rem; color: var(--muted); }

/* ── TOP SISWA ───────────────────────────── */
.rank-list { display: flex; flex-direction: column; gap: 8px; padding: 16px; }
.rank-row { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: var(--bg); border-radius: 10px; transition: transform .15s; }
.rank-row:hover { transform: translateX(3px); }
.rank-num { width: 22px; text-align: center; font-weight: 800; font-size: .78rem; color: var(--muted); }
.rank-num.gold   { color: #f59e0b; font-size: .9rem; }
.rank-num.silver { color: #9e9e9e; font-size: .9rem; }
.rank-num.bronze { color: #cd7f32; font-size: .9rem; }
.rank-ava { font-size: 1.2rem; }
.rank-info { flex: 1; }
.rank-name { font-size: .85rem; font-weight: 700; }
.rank-level { font-size: .7rem; color: var(--muted); }
.rank-xp { font-weight: 800; color: var(--accent); font-size: .85rem; }

/* ── BAR CHART KELAS ─────────────────────── */
.bar-chart { padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: flex; align-items: center; gap: 10px; }
.bar-label { width: 52px; font-size: .8rem; font-weight: 700; color: var(--muted); text-align: right; flex-shrink: 0; }
.bar-track { flex: 1; height: 22px; background: #edf2f7; border-radius: 6px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 6px; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; font-size: .72rem; font-weight: 800; color: var(--white); transition: width .8s cubic-bezier(.4,0,.2,1); min-width: 28px; }
.bar-count { width: 28px; font-size: .8rem; font-weight: 800; color: var(--text); }

/* ── GRAFIK AKTIVITAS ────────────────────── */
.chart-area {
  padding: 16px 20px;
  display: flex;
  align-items: flex-end;
  gap: 8px;
  height: 140px;
}

.chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px; height: 100%; justify-content: flex-end; }
.chart-bar-wrap { width: 100%; display: flex; justify-content: center; align-items: flex-end; flex: 1; }
.chart-bar { width: 70%; border-radius: 6px 6px 0 0; background: linear-gradient(180deg, var(--primary), #3a90c2); transition: height .6s ease; min-height: 3px; position: relative; }
.chart-bar:hover::after { content: attr(data-val); position: absolute; top: -22px; left: 50%; transform: translateX(-50%); background: var(--navy); color: var(--white); font-size: .68rem; font-weight: 800; padding: 2px 6px; border-radius: 5px; white-space: nowrap; }
.chart-label { font-size: .65rem; color: var(--muted); font-weight: 700; white-space: nowrap; }

/* ── TOPIK POPULER ───────────────────────── */
.topik-list { display: flex; flex-direction: column; gap: 6px; padding: 14px 16px; }
.topik-row { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; transition: background .15s; }
.topik-row:hover { background: var(--bg); }
.topik-rank { width: 20px; text-align: center; font-size: .8rem; font-weight: 800; color: var(--muted); }
.topik-icon-wrap { width: 32px; height: 32px; border-radius: 9px; background: color-mix(in srgb, var(--primary) 12%, transparent); display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.topik-info { flex: 1; }
.topik-title { font-size: .83rem; font-weight: 700; }
.topik-kelas { font-size: .7rem; color: var(--muted); }
.topik-count { font-weight: 800; color: var(--primary); font-size: .83rem; }

/* ── QUICK ACTION ────────────────────────── */
.quick-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; padding: 16px; }
.qa-btn {
  background: var(--bg);
  border: 1.5px solid #e2edf5;
  border-radius: 12px;
  padding: 14px;
  text-decoration: none;
  color: var(--text);
  display: flex; align-items: center; gap: 10px;
  font-weight: 700; font-size: .85rem;
  transition: all .2s;
}
.qa-btn:hover { border-color: var(--primary); background: #e8f5fd; color: var(--primary); transform: translateY(-2px); }
.qa-btn i { font-size: 1.3rem; color: var(--primary); }

/* ── FORM SESI CEPAT ─────────────────────── */
.quick-sesi-form { padding: 14px 16px; border-top: 1px solid #edf2f7; display: flex; gap: 8px; }
.quick-sesi-input { flex: 1; padding: 9px 13px; border: 1.5px solid #e2edf5; border-radius: 9px; font-family: 'Nunito',sans-serif; font-size: .85rem; color: var(--text); background: var(--bg); outline: none; }
.quick-sesi-input:focus { border-color: var(--primary); }

/* ── BUTTONS ─────────────────────────────── */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 9px; font-family: 'Nunito',sans-serif; font-size: .85rem; font-weight: 700; cursor: pointer; border: none; transition: all .2s; text-decoration: none; }
.btn-sm { padding: 6px 12px; font-size: .78rem; border-radius: 7px; }
.btn-primary { background: var(--primary); color: var(--white); }
.btn-primary:hover { background: #3a90c2; }
.btn-accent { background: var(--accent); color: var(--navy); }
.btn-accent:hover { background: #fbbf24; }
.btn-danger { background: var(--error); color: var(--white); }
.btn-danger:hover { background: #c44; }
.btn-ghost { background: var(--bg); color: var(--text); border: 1.5px solid #e2edf5; }
.btn-ghost:hover { border-color: var(--primary); color: var(--primary); }

/* ── EMPTY ───────────────────────────────── */
.empty-state { text-align: center; padding: 32px; color: var(--muted); }
.empty-state .ei { font-size: 2rem; opacity: .3; display: block; margin-bottom: 8px; }

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-thumb { background: #d0e4ef; border-radius: 10px; }

@media (max-width: 1200px) {
  .kpi-grid { grid-template-columns: repeat(2,1fr); }
  .content-grid { grid-template-columns: 1fr; }
  .content-grid-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 900px) {
  .main-content { margin-left: 0; padding: 20px 16px; }
  .content-grid-3 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- ── SIDEBAR ──────────────────────────────────────────────── -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><i class="bi bi-stars"></i> Saintara <span>Admin</span></div>
    <small>Panel Guru — Ella's Lab</small>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <a href="/SAINTARA/pages/admin/dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
    <a href="/SAINTARA/pages/admin/siswa.php"     class="nav-link"><i class="bi bi-people-fill"></i> Data Siswa</a>
    <a href="/SAINTARA/pages/admin/soal.php"      class="nav-link"><i class="bi bi-patch-question-fill"></i> Bank Soal</a>
    <a href="/SAINTARA/pages/admin/sesi.php"      class="nav-link"><i class="bi bi-trophy-fill"></i> Sesi Kompetisi</a>
  </nav>
  <div class="sidebar-footer">
    <div class="guru-info">
      <div class="guru-avatar-box"><i class="bi bi-person-fill"></i></div>
      <div>
        <div class="guru-name"><?= htmlspecialchars($guru['nama']) ?></div>
        <div class="guru-role">Administrator</div>
      </div>
    </div>
    <a href="/SAINTARA/api/logout-guru.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</aside>

<!-- ── TOAST ─────────────────────────────────────────────────── -->
<?php if ($flash): ?>
<div class="toast-wrap" id="toastEl">
  <div class="toast <?= $flash_type ?>">
    <?= $flash_type === 'success' ? '✅' : '❌' ?>
    <?= htmlspecialchars($flash) ?>
  </div>
</div>
<?php endif; ?>

<!-- ── MAIN ──────────────────────────────────────────────────── -->
<main class="main-content">

  <!-- TOPBAR -->
  <div class="topbar">
    <div>
      <h1 class="page-title"><i class="bi bi-grid-1x2-fill"></i> Dashboard</h1>
      <p class="page-subtitle">Selamat datang, <?= htmlspecialchars($guru['nama']) ?>! 👋</p>
    </div>
    <div class="topbar-right">
      <div class="date-chip"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></div>
      <a href="/SAINTARA/pages/admin/sesi.php" class="btn btn-accent btn-sm">
        <i class="bi bi-trophy-fill"></i> Kelola Sesi
      </a>
    </div>
  </div>

  <!-- SESI AKTIF BANNER -->
  <?php if ($sesi_aktif): ?>
  <div class="sesi-banner">
    <div class="sesi-banner-left">
      <div class="live-dot-wrap">
        <div class="live-dot"></div>
        <div class="live-ring"></div>
      </div>
      <div class="sesi-info-text">
        <div class="sesi-tag">🏆 Sesi Kompetisi Aktif</div>
        <div class="sesi-nama-text"><?= htmlspecialchars($sesi_aktif['nama']) ?></div>
        <div class="sesi-stats-text">
          <?= $sesi_aktif['peserta'] ?> peserta ·
          Dimulai <?= date('d M, H:i', strtotime($sesi_aktif['mulai'])) ?>
          <?php if ($sesi_aktif['kelas']): ?>
          · Kelas <?= $sesi_aktif['kelas'] ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="sesi-banner-right">
      <a href="/SAINTARA/pages/admin/sesi.php" class="btn btn-sm btn-ghost" style="color:var(--white);border-color:rgba(255,255,255,.2)">
        <i class="bi bi-eye-fill"></i> Lihat Leaderboard
      </a>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="tutup_sesi_cepat">
        <input type="hidden" name="sesi_id" value="<?= $sesi_aktif['id'] ?>">
        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tutup sesi ini?')">
          <i class="bi bi-stop-fill"></i> Tutup Sesi
        </button>
      </form>
    </div>
  </div>
  <?php else: ?>
  <div class="no-sesi-banner">
    <div class="no-sesi-text">
      <i class="bi bi-trophy"></i>
      Belum ada sesi kompetisi aktif. Buat sesi baru agar siswa bisa berkompetisi!
    </div>
    <form method="POST" style="display:flex;gap:8px">
      <input type="hidden" name="action" value="buat_sesi_cepat">
      <input type="text" name="nama_sesi" class="quick-sesi-input" placeholder="Nama sesi kompetisi..." required style="min-width:200px">
      <button type="submit" class="btn btn-accent btn-sm">
        <i class="bi bi-rocket-takeoff-fill"></i> Mulai
      </button>
    </form>
  </div>
  <?php endif; ?>

  <!-- KPI CARDS -->
  <div class="kpi-grid">
    <div class="kpi-card" style="--kc:#4DA8DA">
      <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
      <div class="kpi-val"><?= number_format($total_siswa) ?></div>
      <div class="kpi-label">Total Siswa Terdaftar</div>
      <div class="kpi-sub"><i class="bi bi-circle-fill" style="font-size:.4rem;color:var(--secondary)"></i> <span class="up"><?= $kuis_hari_ini ?> kuis</span> hari ini</div>
      <div class="bg-icon"><i class="bi bi-people"></i></div>
    </div>
    <div class="kpi-card" style="--kc:#80D8C3">
      <div class="kpi-icon"><i class="bi bi-patch-question-fill"></i></div>
      <div class="kpi-val"><?= number_format($total_soal) ?></div>
      <div class="kpi-label">Total Soal di Bank</div>
      <div class="kpi-sub"><span class="neutral"><?= $total_topik ?> topik tersedia</span></div>
      <div class="bg-icon"><i class="bi bi-question-circle"></i></div>
    </div>
    <div class="kpi-card" style="--kc:#FFD66B">
      <div class="kpi-icon"><i class="bi bi-controller"></i></div>
      <div class="kpi-val"><?= number_format($total_kuis) ?></div>
      <div class="kpi-label">Total Kuis Dimainkan</div>
      <div class="kpi-sub"><span class="up"><?= $avg_skor ?>%</span> rata-rata skor</div>
      <div class="bg-icon"><i class="bi bi-joystick"></i></div>
    </div>
    <div class="kpi-card" style="--kc:#a78bfa">
      <div class="kpi-icon"><i class="bi bi-lightning-charge-fill"></i></div>
      <div class="kpi-val"><?= number_format($total_xp) ?></div>
      <div class="kpi-label">Total XP Terdistribusi</div>
      <div class="kpi-sub"><span class="neutral"><?= $total_guru ?> akun guru</span></div>
      <div class="bg-icon"><i class="bi bi-lightning"></i></div>
    </div>
  </div>

  <!-- CONTENT ROW 1: Aktivitas + Top Siswa -->
  <div class="content-grid">

    <!-- Aktivitas Kuis Terbaru -->
    <div class="card">
      <div class="card-head">
        <h3><i class="bi bi-activity"></i> Aktivitas Kuis Terbaru</h3>
        <a href="/SAINTARA/pages/admin/siswa.php" class="btn btn-ghost btn-sm">Lihat Semua</a>
      </div>
      <?php if (empty($aktivitas)): ?>
      <div class="empty-state"><span class="ei">📋</span> Belum ada aktivitas kuis.</div>
      <?php else: ?>
      <div style="overflow-x:auto">
      <table>
        <thead><tr>
          <th>Siswa</th>
          <th>Topik</th>
          <th>Skor</th>
          <th>XP</th>
          <th>Waktu</th>
        </tr></thead>
        <tbody>
          <?php foreach ($aktivitas as $a):
            $total_a = $a['benar'] + $a['salah'];
            $pct = $total_a > 0 ? round($a['benar']/$total_a*100) : 0;
            $cls = $pct >= 70 ? 'good' : ($pct >= 40 ? 'mid' : 'low');
            $tAgo = '';
            $diff = time() - strtotime($a['played_at']);
            if ($diff < 60) $tAgo = $diff.'d lalu';
            elseif ($diff < 3600) $tAgo = round($diff/60).'m lalu';
            elseif ($diff < 86400) $tAgo = round($diff/3600).'j lalu';
            else $tAgo = date('d/m', strtotime($a['played_at']));
          ?>
          <tr>
            <td>
              <div class="ava-cell">
                <div class="ava-circle"><?= ava($a['avatar']) ?></div>
                <div>
                  <div class="student-n"><?= htmlspecialchars($a['nama_siswa']) ?></div>
                  <div class="student-m">Kelas <?= $a['kelas'] ?></div>
                </div>
              </div>
            </td>
            <td style="font-weight:600;font-size:.83rem"><?= htmlspecialchars($a['judul_topik']) ?></td>
            <td><span class="score-badge score-<?= $cls ?>"><?= $a['benar'] ?>/<?= $total_a ?> · <?= $pct ?>%</span></td>
            <td style="font-weight:800;color:var(--accent)">+<?= $a['xp_gained'] ?></td>
            <td><span class="time-ago"><?= $tAgo ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Top 5 Siswa -->
    <div class="card">
      <div class="card-head">
        <h3><i class="bi bi-trophy-fill"></i> Top 5 Siswa</h3>
        <a href="/SAINTARA/pages/admin/siswa.php?sort=xp" class="btn btn-ghost btn-sm">Lengkap</a>
      </div>
      <?php if (empty($top_siswa)): ?>
      <div class="empty-state"><span class="ei">🏆</span> Belum ada data siswa.</div>
      <?php else: ?>
      <div class="rank-list">
        <?php foreach ($top_siswa as $i => $s):
          $medal = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
          $medal_icon = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ''));
        ?>
        <div class="rank-row">
          <div class="rank-num <?= $medal ?>"><?= $medal_icon ?: $s['ranking'] ?></div>
          <div class="rank-ava"><?= ava($s['avatar']) ?></div>
          <div class="rank-info">
            <div class="rank-name"><?= htmlspecialchars($s['nama']) ?></div>
            <div class="rank-level">Kelas <?= $s['kelas'] ?> · <?= $level_names[$s['level']] ?? '' ?></div>
          </div>
          <div class="rank-xp"><?= number_format($s['xp']) ?> XP</div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Quick action buat sesi -->
      <?php if (!$sesi_aktif): ?>
      <div class="quick-sesi-form">
        <form method="POST" style="display:flex;gap:8px;width:100%">
          <input type="hidden" name="action" value="buat_sesi_cepat">
          <input type="text" name="nama_sesi" class="quick-sesi-input" placeholder="Nama sesi baru..." required>
          <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i></button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- CONTENT ROW 2: Chart aktivitas + Bar kelas + Topik populer -->
  <div class="content-grid-3">

    <!-- Grafik Aktivitas 7 Hari -->
    <div class="card">
      <div class="card-head">
        <h3><i class="bi bi-bar-chart-fill"></i> Aktivitas 7 Hari</h3>
      </div>
      <div class="chart-area">
        <?php foreach ($daily_data as $d):
          $h = $max_daily > 0 ? round(($d['count']/$max_daily)*90) : 3;
          $h = max($h, 3);
        ?>
        <div class="chart-col">
          <div class="chart-bar-wrap">
            <div class="chart-bar" style="height:<?= $h ?>%" data-val="<?= $d['count'] ?>"></div>
          </div>
          <div class="chart-label"><?= $d['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Siswa per Kelas -->
    <div class="card">
      <div class="card-head">
        <h3><i class="bi bi-people-fill"></i> Siswa per Kelas</h3>
        <span style="font-size:.78rem;color:var(--muted);font-weight:700"><?= $total_siswa ?> total</span>
      </div>
      <div class="bar-chart">
        <?php
        $bar_colors = ['#4DA8DA','#80D8C3','#FFD66B','#a78bfa','#f97316','#E05555'];
        for ($k = 1; $k <= 6; $k++):
          $pct = $max_kelas > 0 ? round($per_kelas[$k]/$max_kelas*100) : 0;
          $pct = max($pct, 2);
        ?>
        <div class="bar-row">
          <div class="bar-label">Kelas <?= $k ?></div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $bar_colors[$k-1] ?>">
              <?php if ($per_kelas[$k] > 0): ?><?= $per_kelas[$k] ?><?php endif; ?>
            </div>
          </div>
          <div class="bar-count"><?= $per_kelas[$k] ?></div>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Topik Terpopuler + Quick Actions -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <div class="card" style="flex:1">
        <div class="card-head">
          <h3><i class="bi bi-fire"></i> Topik Terpopuler</h3>
        </div>
        <?php if (empty($top_topik)): ?>
        <div class="empty-state"><span class="ei">📌</span> Belum ada data.</div>
        <?php else: ?>
        <div class="topik-list">
          <?php foreach ($top_topik as $i => $t): ?>
          <div class="topik-row">
            <div class="topik-rank"><?= $i+1 ?></div>
            <div class="topik-icon-wrap">
                <i class="bi <?= htmlspecialchars($t['icon'] ?: 'bi-book-fill') ?>"></i>
            </div>
            <div class="topik-info">
              <div class="topik-title"><?= htmlspecialchars($t['judul']) ?></div>
              <div class="topik-kelas">Kelas <?= $t['kelas'] ?></div>
            </div>
            <div class="topik-count"><?= $t['play_count'] ?>×</div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-head"><h3><i class="bi bi-lightning-fill"></i> Aksi Cepat</h3></div>
        <div class="quick-grid">
          <a href="/SAINTARA/pages/admin/soal.php" class="qa-btn"><i class="bi bi-plus-circle-fill"></i> Tambah Soal</a>
          <a href="/SAINTARA/pages/admin/siswa.php" class="qa-btn"><i class="bi bi-people-fill"></i> Data Siswa</a>
          <a href="/SAINTARA/pages/admin/sesi.php" class="qa-btn"><i class="bi bi-trophy-fill"></i> Sesi Baru</a>
          <a href="/SAINTARA/pages/ranking.php" target="_blank" class="qa-btn"><i class="bi bi-bar-chart-fill"></i> Lihat Ranking</a>
        </div>
      </div>

    </div>
  </div>

</main>

<script>
// Auto-hide toast
setTimeout(() => {
  const t = document.getElementById('toastEl');
  if (t) { t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(()=>t.remove(),400); }
}, 3500);

// Animasi bar chart on load
document.querySelectorAll('.bar-fill').forEach(el => {
  const w = el.style.width;
  el.style.width = '0';
  setTimeout(() => el.style.width = w, 100);
});
</script>

</body>
</html>