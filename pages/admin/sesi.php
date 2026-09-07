<?php
/**
 * SAINTARA — Ella's Lab
 * pages/admin/sesi.php
 * Panel Guru — Kelola Sesi Kompetisi
 */

require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

requireGuru();

$pdo  = getDB();
$guru = getGuruSession();

// ─────────────────────────────────────────────
// AKSI POST
// ─────────────────────────────────────────────
$action_msg  = '';
$action_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = bersihkan($_POST['action'] ?? '');

    switch ($action) {

        // ── Buat Sesi Baru ────────────────────────────
        case 'buat_sesi':
            $nama  = bersihkan($_POST['nama'] ?? '');
            $kelas = (int)($_POST['kelas'] ?? 0);

            if (strlen($nama) < 3) {
                $action_msg = 'Nama sesi minimal 3 karakter.';
                $action_type = 'error';
                break;
            }

            // Nonaktifkan semua sesi aktif yang ada
            $pdo->exec("UPDATE sesi SET aktif=0, selesai=NOW() WHERE aktif=1 AND selesai IS NULL");

            $stmt = $pdo->prepare("
                INSERT INTO sesi (nama, kelas, mulai, aktif, dibuat_oleh)
                VALUES (?, ?, NOW(), 1, ?)
            ");
            $stmt->execute([$nama, ($kelas >= 1 && $kelas <= 6) ? $kelas : null, $guru['id']]);

            $action_msg  = "Sesi \"$nama\" berhasil dibuat dan sekarang aktif!";
            $action_type = 'success';
            break;

        // ── Tutup Sesi Aktif ──────────────────────────
        case 'tutup_sesi':
            $sesi_id = (int)($_POST['sesi_id'] ?? 0);
            if ($sesi_id <= 0) { $action_msg='ID sesi tidak valid.'; $action_type='error'; break; }

            $pdo->prepare("UPDATE sesi SET aktif=0, selesai=NOW() WHERE id=?")->execute([$sesi_id]);
            $action_msg  = 'Sesi berhasil ditutup.';
            $action_type = 'success';
            break;

        // ── Aktifkan Kembali Sesi ──────────────────────
        case 'aktifkan_sesi':
            $sesi_id = (int)($_POST['sesi_id'] ?? 0);
            if ($sesi_id <= 0) { $action_msg='ID sesi tidak valid.'; $action_type='error'; break; }

            // Nonaktifkan semua dulu
            $pdo->exec("UPDATE sesi SET aktif=0, selesai=NOW() WHERE aktif=1 AND selesai IS NULL");
            // Aktifkan yang dipilih
            $pdo->prepare("UPDATE sesi SET aktif=1, selesai=NULL WHERE id=?")->execute([$sesi_id]);
            $action_msg  = 'Sesi berhasil diaktifkan kembali.';
            $action_type = 'success';
            break;

        // ── Reset XP Sesi ─────────────────────────────
        case 'reset_xp_sesi':
            $sesi_id = (int)($_POST['sesi_id'] ?? 0);
            if ($sesi_id <= 0) { $action_msg='ID sesi tidak valid.'; $action_type='error'; break; }

            $pdo->prepare("DELETE FROM xp_sesi WHERE sesi_id=?")->execute([$sesi_id]);
            $action_msg  = 'XP sesi berhasil direset. Semua peserta mulai dari 0.';
            $action_type = 'success';
            break;

        // ── Hapus Sesi ────────────────────────────────
        case 'hapus_sesi':
            $sesi_id = (int)($_POST['sesi_id'] ?? 0);
            if ($sesi_id <= 0) { $action_msg='ID sesi tidak valid.'; $action_type='error'; break; }

            $pdo->prepare("DELETE FROM xp_sesi WHERE sesi_id=?")->execute([$sesi_id]);
            $pdo->prepare("DELETE FROM sesi WHERE id=?")->execute([$sesi_id]);
            $action_msg  = 'Sesi berhasil dihapus permanen.';
            $action_type = 'success';
            break;
    }
}

// ─────────────────────────────────────────────
// FETCH DATA
// ─────────────────────────────────────────────

// Sesi aktif
$sesi_aktif = $pdo->query("
    SELECT s.*, g.nama AS nama_guru,
           (SELECT COUNT(*) FROM xp_sesi x WHERE x.sesi_id = s.id) AS peserta
    FROM sesi s
    LEFT JOIN guru g ON g.id = s.dibuat_oleh
    WHERE s.aktif = 1
    ORDER BY s.mulai DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Semua sesi (arsip)
$all_sesi = $pdo->query("
    SELECT s.*, g.nama AS nama_guru,
           (SELECT COUNT(*) FROM xp_sesi x WHERE x.sesi_id = s.id) AS peserta,
           (SELECT MAX(x.xp) FROM xp_sesi x WHERE x.sesi_id = s.id) AS xp_tertinggi
    FROM sesi s
    LEFT JOIN guru g ON g.id = s.dibuat_oleh
    ORDER BY s.mulai DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Leaderboard sesi aktif (top 10)
$leaderboard = [];
if ($sesi_aktif) {
    $lb = $pdo->prepare("
        SELECT si.nama, si.kelas, si.avatar, x.xp,
               RANK() OVER (ORDER BY x.xp DESC) AS ranking
        FROM xp_sesi x
        JOIN siswa si ON si.id = x.siswa_id
        WHERE x.sesi_id = ?
        ORDER BY x.xp DESC
        LIMIT 10
    ");
    $lb->execute([$sesi_aktif['id']]);
    $leaderboard = $lb->fetchAll(PDO::FETCH_ASSOC);
}

// Stats global sesi
$total_sesi    = count($all_sesi);
$total_peserta = (int)$pdo->query("SELECT COUNT(DISTINCT siswa_id) FROM xp_sesi")->fetchColumn();

// Helper avatar
$avatars = ['🐘','🦁','🐯','🦊','🐧','🦋','🐬','🦜','🐸','🌟'];
function getAva($idx) { global $avatars; return $avatars[(int)$idx] ?? '🐘'; }

function durasi_label($mulai, $selesai) {
    if (!$mulai) return '-';
    $start = strtotime($mulai);
    $end   = $selesai ? strtotime($selesai) : time();
    $diff  = $end - $start;
    if ($diff < 60)        return $diff . ' detik';
    if ($diff < 3600)      return round($diff/60) . ' menit';
    if ($diff < 86400)     return round($diff/3600, 1) . ' jam';
    return round($diff/86400) . ' hari';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sesi Kompetisi — Saintara Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
/* ══════════════════════════════════════════
   DESIGN SYSTEM
══════════════════════════════════════════ */
:root {
  --primary:   #4DA8DA;
  --secondary: #80D8C3;
  --accent:    #FFD66B;
  --bg:        #F0F8FF;
  --error:     #E05555;
  --text:      #1A2E3A;
  --muted:     #6B8899;
  --white:     #FFFFFF;
  --sidebar-w: 240px;
  --navy:      #1A2E3A;
  --navy-light:#243447;
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
  position: fixed; top: 0; left: 0;
  height: 100vh;
  display: flex; flex-direction: column;
  z-index: 100; overflow-y: auto;
}

.sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,.08); }
.brand-logo { font-family: 'Fredoka', sans-serif; font-size: 1.4rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 8px; }
.brand-logo span { color: var(--accent); }
.sidebar-brand small { color: var(--muted); font-size: .75rem; display: block; margin-top: 4px; }

.sidebar-nav { padding: 12px 0; flex: 1; }
.nav-label { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: var(--muted); padding: 16px 20px 6px; }

.nav-link {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 20px;
  color: rgba(255,255,255,.65);
  text-decoration: none; font-size: .9rem; font-weight: 600;
  transition: all .2s; border-left: 3px solid transparent;
}
.nav-link:hover { color: var(--white); background: rgba(255,255,255,.05); }
.nav-link.active { color: var(--white); background: rgba(77,168,218,.15); border-left-color: var(--primary); }
.nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }

.sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.08); }
.guru-info { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.guru-avatar { width: 36px; height: 36px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.guru-name { font-size: .85rem; font-weight: 700; color: var(--white); }
.guru-role { font-size: .7rem; color: var(--muted); }

.btn-logout {
  display: flex; align-items: center; gap: 8px;
  width: 100%; padding: 9px 14px;
  background: rgba(224,85,85,.15); color: #ff8080;
  border: 1px solid rgba(224,85,85,.25); border-radius: 8px;
  font-size: .83rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .2s;
}
.btn-logout:hover { background: rgba(224,85,85,.3); color: #ff6060; }

/* ── MAIN ────────────────────────────────── */
.main-content { margin-left: var(--sidebar-w); flex: 1; padding: 28px 32px; min-height: 100vh; }

.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 16px; }
.page-title { font-size: 1.8rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 10px; }
.page-title i { color: var(--accent); }
.page-subtitle { font-size: .9rem; color: var(--muted); margin-top: 2px; }

/* ── TOAST ───────────────────────────────── */
.toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
.toast { background: var(--white); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 12px; box-shadow: 0 8px 32px rgba(0,0,0,.12); border-left: 4px solid var(--secondary); min-width: 280px; animation: toastIn .3s ease; font-weight: 600; font-size: .88rem; }
.toast.error { border-left-color: var(--error); }
@keyframes toastIn { from { transform: translateX(40px); opacity: 0; } to { transform: none; opacity: 1; } }

/* ── GRID LAYOUT ─────────────────────────── */
.top-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; margin-bottom: 24px; }

/* ── ACTIVE SESSION CARD ─────────────────── */
.sesi-aktif-card {
  background: linear-gradient(135deg, var(--navy) 0%, #1e3a50 100%);
  border-radius: 20px;
  padding: 28px;
  color: var(--white);
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(26,46,58,.3);
}

.sesi-aktif-card::before {
  content: '';
  position: absolute;
  top: -40px; right: -40px;
  width: 200px; height: 200px;
  background: rgba(77,168,218,.1);
  border-radius: 50%;
}

.sesi-aktif-card::after {
  content: '';
  position: absolute;
  bottom: -60px; right: 60px;
  width: 150px; height: 150px;
  background: rgba(255,214,107,.06);
  border-radius: 50%;
}

.live-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(128,216,195,.2);
  border: 1px solid rgba(128,216,195,.4);
  color: var(--secondary);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: .75rem;
  font-weight: 800;
  margin-bottom: 16px;
  text-transform: uppercase;
  letter-spacing: .8px;
}

.live-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--secondary);
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%,100% { opacity: 1; transform: scale(1); }
  50%      { opacity: .5; transform: scale(.85); }
}

.sesi-nama { font-size: 1.7rem; font-weight: 700; margin-bottom: 8px; line-height: 1.2; }
.sesi-meta { font-size: .85rem; color: rgba(255,255,255,.6); display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
.sesi-meta span { display: flex; align-items: center; gap: 5px; }

.sesi-stats { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.sesi-stat { background: rgba(255,255,255,.08); border-radius: 12px; padding: 12px 18px; flex: 1; min-width: 90px; }
.sesi-stat-val { font-family: 'Fredoka', sans-serif; font-size: 1.6rem; font-weight: 700; color: var(--accent); }
.sesi-stat-lbl { font-size: .72rem; color: rgba(255,255,255,.55); margin-top: 2px; font-weight: 600; }

.sesi-actions { display: flex; gap: 10px; flex-wrap: wrap; position: relative; z-index: 1; }

/* ── FORM BUAT SESI ──────────────────────── */
.buat-sesi-card {
  background: var(--white);
  border-radius: 20px;
  padding: 24px;
  box-shadow: var(--shadow);
  border-top: 3px solid var(--accent);
}

.buat-sesi-card h3 { font-size: 1.1rem; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
.buat-sesi-card h3 i { color: var(--accent); }

.form-group { margin-bottom: 14px; }
.form-label { display: block; font-size: .8rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px; }

.form-input {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2edf5;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif;
  font-size: .9rem;
  color: var(--text);
  background: var(--bg);
  outline: none;
  transition: border-color .2s;
}
.form-input:focus { border-color: var(--primary); background: var(--white); }

.form-select {
  width: 100%;
  padding: 10px 14px;
  border: 1.5px solid #e2edf5;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif;
  font-size: .9rem;
  color: var(--text);
  background: var(--bg);
  outline: none; cursor: pointer;
  transition: border-color .2s;
}
.form-select:focus { border-color: var(--primary); }

.form-hint { font-size: .74rem; color: var(--muted); margin-top: 4px; }

/* ── NO SESSION STATE ────────────────────── */
.no-sesi-card {
  background: var(--white);
  border-radius: 20px;
  padding: 40px 28px;
  box-shadow: var(--shadow);
  text-align: center;
  color: var(--muted);
  border: 2px dashed #d0e4ef;
}
.no-sesi-icon { font-size: 3rem; display: block; margin-bottom: 14px; opacity: .4; }
.no-sesi-title { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.no-sesi-sub { font-size: .85rem; line-height: 1.6; }

/* ── LEADERBOARD CARD ────────────────────── */
.lb-card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  margin-bottom: 24px;
}

.lb-header {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  display: flex; align-items: center; justify-content: space-between;
}
.lb-header h3 { font-size: 1rem; display: flex; align-items: center; gap: 8px; }
.lb-header h3 i { color: var(--accent); }

.lb-badge { font-size: .75rem; background: #fff8e1; color: #e65100; padding: 3px 10px; border-radius: 20px; font-weight: 800; }

/* Podium top 3 */
.podium { display: flex; align-items: flex-end; justify-content: center; gap: 12px; padding: 24px 20px 0; }

.podium-item { text-align: center; display: flex; flex-direction: column; align-items: center; }
.podium-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg,var(--primary),var(--secondary)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; position: relative; margin: 0 auto 8px; }
.podium-avatar .crown { position: absolute; top: -14px; font-size: 1rem; }

.podium-name { font-size: .78rem; font-weight: 800; color: var(--text); max-width: 70px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.podium-kelas { font-size: .68rem; color: var(--muted); }
.podium-xp { font-family:'Fredoka',sans-serif; font-size:1rem; font-weight:700; color:var(--accent); margin-top:2px; }

.podium-stand {
  border-radius: 10px 10px 0 0;
  width: 72px;
  margin-top: 8px;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Fredoka', sans-serif; font-weight: 700; font-size: 1.1rem; color: var(--white);
}

.stand-1 { height: 64px; background: linear-gradient(180deg,#FFD66B,#fbbf24); color: var(--navy); }
.stand-2 { height: 48px; background: linear-gradient(180deg,#c0c0c0,#9e9e9e); }
.stand-3 { height: 36px; background: linear-gradient(180deg,#cd7f32,#a1612a); }

/* List rank 4–10 */
.rank-list { padding: 12px 20px 16px; }
.rank-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  transition: background .15s;
  font-size: .85rem;
}
.rank-row:hover { background: var(--bg); }
.rank-num { width: 24px; text-align: center; font-weight: 800; color: var(--muted); font-size: .78rem; }
.rank-ava { font-size: 1.1rem; }
.rank-name { flex: 1; font-weight: 700; }
.rank-kelas { font-size: .72rem; color: var(--muted); background: var(--bg); padding: 2px 7px; border-radius: 20px; font-weight: 600; }
.rank-xp { font-weight: 800; color: var(--primary); font-size: .85rem; }

/* ── HISTORY TABLE ───────────────────────── */
.history-card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.history-header {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px;
}
.history-header h3 { font-size: 1rem; display: flex; align-items: center; gap: 8px; }
.history-header h3 i { color: var(--primary); }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #f7fafd; }
thead th { padding: 10px 16px; text-align: left; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); border-bottom: 1px solid #edf2f7; white-space: nowrap; }
tbody tr { border-bottom: 1px solid #f0f4f8; transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f7fafd; }
tbody td { padding: 12px 16px; font-size: .87rem; vertical-align: middle; }

.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px; font-size: .73rem; font-weight: 800;
}
.status-aktif { background: #e8f8f5; color: #00897b; }
.status-selesai { background: #f0f4f8; color: var(--muted); }

.kelas-chip { display: inline-block; padding: 2px 9px; background: var(--bg); border-radius: 20px; font-size: .75rem; font-weight: 800; color: var(--primary); border: 1px solid #d0e4ef; }

/* ── BUTTONS ─────────────────────────────── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 10px;
  font-family: 'Nunito', sans-serif; font-size: .88rem; font-weight: 700;
  cursor: pointer; border: none; transition: all .2s; text-decoration: none;
}
.btn-sm { padding: 6px 13px; font-size: .8rem; border-radius: 8px; }
.btn-primary { background: var(--primary); color: var(--white); }
.btn-primary:hover { background: #3a90c2; transform: translateY(-1px); }
.btn-accent { background: var(--accent); color: var(--navy); }
.btn-accent:hover { background: #fbbf24; transform: translateY(-1px); }
.btn-danger { background: var(--error); color: var(--white); }
.btn-danger:hover { background: #c44; }
.btn-ghost { background: var(--bg); color: var(--text); border: 1.5px solid #e2edf5; }
.btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
.btn-warning { background: rgba(255,214,107,.2); color: #92640a; border: 1.5px solid rgba(255,214,107,.5); }
.btn-warning:hover { background: rgba(255,214,107,.4); }
.btn-green { background: #e8f8f5; color: #00897b; border: 1.5px solid #b2dfdb; }
.btn-green:hover { background: #00897b; color: var(--white); }

/* ── MODAL ───────────────────────────────── */
.modal-backdrop { position: fixed; inset: 0; background: rgba(26,46,58,.5); backdrop-filter: blur(4px); z-index: 500; display: none; align-items: center; justify-content: center; padding: 20px; }
.modal-backdrop.open { display: flex; }
.modal { background: var(--white); border-radius: 20px; max-width: 480px; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,.18); animation: modalIn .25s ease; overflow: hidden; }
@keyframes modalIn { from { transform: scale(.94) translateY(16px); opacity: 0; } to { transform: none; opacity: 1; } }
.modal-head { padding: 20px 24px; border-bottom: 1px solid #edf2f7; display: flex; align-items: center; justify-content: space-between; }
.modal-head h3 { font-size: 1.1rem; display: flex; align-items: center; gap: 8px; }
.modal-body { padding: 24px; }
.modal-foot { padding: 16px 24px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
.btn-close-modal { width: 32px; height: 32px; border-radius: 8px; border: none; background: #f0f4f8; color: var(--muted); font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; }
.btn-close-modal:hover { background: var(--error); color: var(--white); }
.confirm-icon { font-size: 3rem; text-align: center; display: block; margin-bottom: 12px; }
.confirm-text { text-align: center; font-size: .92rem; color: var(--text); line-height: 1.6; }
.confirm-name { font-weight: 800; color: var(--primary); }

.empty-lb { text-align: center; padding: 32px; color: var(--muted); font-size: .88rem; }

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-thumb { background: #d0e4ef; border-radius: 10px; }

@media (max-width:1024px) { .top-grid { grid-template-columns: 1fr; } }
@media (max-width:900px) { .main-content { margin-left: 0; padding: 20px 16px; } }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><i class="bi bi-stars"></i> Saintara <span>Admin</span></div>
    <small>Panel Guru — Ella's Lab</small>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <a href="/SAINTARA/pages/admin/dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
    <a href="/SAINTARA/pages/admin/siswa.php" class="nav-link"><i class="bi bi-people-fill"></i> Data Siswa</a>
    <a href="/SAINTARA/pages/admin/soal.php" class="nav-link"><i class="bi bi-patch-question-fill"></i> Bank Soal</a>
    <a href="/SAINTARA/pages/admin/sesi.php" class="nav-link active"><i class="bi bi-trophy-fill"></i> Sesi Kompetisi</a>
  </nav>
  <div class="sidebar-footer">
    <div class="guru-info">
      <div class="guru-avatar"><i class="bi bi-person-fill"></i></div>
      <div>
        <div class="guru-name"><?= htmlspecialchars($guru['nama'] ?? 'Guru') ?></div>
        <div class="guru-role">Administrator</div>
      </div>
    </div>
    <a href="/SAINTARA/api/logout-guru.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</aside>

<!-- TOAST -->
<?php if ($action_msg): ?>
<div class="toast-container" id="toastEl">
  <div class="toast <?= $action_type ?>">
    <span><?= $action_type === 'success' ? '✅' : '❌' ?></span>
    <?= htmlspecialchars($action_msg) ?>
  </div>
</div>
<?php endif; ?>

<!-- MAIN -->
<main class="main-content">

  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-trophy-fill"></i> Sesi Kompetisi</h1>
      <p class="page-subtitle">Buat & kelola sesi leaderboard untuk kelas</p>
    </div>
  </div>

  <!-- TOP GRID -->
  <div class="top-grid">

    <!-- SESI AKTIF / EMPTY -->
    <div>
      <?php if ($sesi_aktif): ?>
      <div class="sesi-aktif-card">
        <div class="live-badge"><span class="live-dot"></span> SESI AKTIF SEKARANG</div>
        <div class="sesi-nama"><?= htmlspecialchars($sesi_aktif['nama']) ?></div>
        <div class="sesi-meta">
          <span><i class="bi bi-calendar3"></i> Mulai: <?= date('d M Y, H:i', strtotime($sesi_aktif['mulai'])) ?></span>
          <span><i class="bi bi-clock"></i> Durasi: <?= durasi_label($sesi_aktif['mulai'], null) ?></span>
          <?php if ($sesi_aktif['kelas']): ?>
          <span><i class="bi bi-mortarboard-fill"></i> Kelas <?= $sesi_aktif['kelas'] ?></span>
          <?php else: ?>
          <span><i class="bi bi-globe"></i> Semua Kelas</span>
          <?php endif; ?>
          <span><i class="bi bi-person-fill"></i> <?= htmlspecialchars($sesi_aktif['nama_guru'] ?? 'Guru') ?></span>
        </div>

        <div class="sesi-stats">
          <div class="sesi-stat">
            <div class="sesi-stat-val"><?= $sesi_aktif['peserta'] ?></div>
            <div class="sesi-stat-lbl">Peserta Aktif</div>
          </div>
          <div class="sesi-stat">
            <div class="sesi-stat-val"><?= $total_sesi ?></div>
            <div class="sesi-stat-lbl">Total Sesi Pernah Ada</div>
          </div>
          <div class="sesi-stat">
            <div class="sesi-stat-val"><?= $total_peserta ?></div>
            <div class="sesi-stat-lbl">Total Peserta Pernah Main</div>
          </div>
        </div>

        <div class="sesi-actions">
          <button class="btn btn-warning btn-sm"
            onclick="bukaReset(<?= $sesi_aktif['id'] ?>, '<?= htmlspecialchars($sesi_aktif['nama'], ENT_QUOTES) ?>')">
            <i class="bi bi-arrow-counterclockwise"></i> Reset XP Sesi
          </button>
          <button class="btn btn-danger btn-sm"
            onclick="bukaTutup(<?= $sesi_aktif['id'] ?>, '<?= htmlspecialchars($sesi_aktif['nama'], ENT_QUOTES) ?>')">
            <i class="bi bi-stop-circle-fill"></i> Tutup Sesi
          </button>
        </div>
      </div>
      <?php else: ?>
      <div class="no-sesi-card">
        <span class="no-sesi-icon">🏟️</span>
        <div class="no-sesi-title">Tidak Ada Sesi Aktif</div>
        <div class="no-sesi-sub">Buat sesi baru dari form di samping agar siswa bisa berkompetisi di leaderboard real-time.</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- FORM BUAT SESI -->
    <div class="buat-sesi-card">
      <h3><i class="bi bi-plus-circle-fill"></i> Buat Sesi Baru</h3>
      <?php if ($sesi_aktif): ?>
      <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#92640a;margin-bottom:14px;display:flex;gap:8px;align-items:flex-start">
        <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px"></i>
        Membuat sesi baru akan <b>otomatis menutup</b> sesi yang sedang aktif.
      </div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="buat_sesi">
        <div class="form-group">
          <label class="form-label">Nama Sesi <span style="color:var(--error)">*</span></label>
          <input type="text" name="nama" class="form-input" placeholder="cth: Kompetisi IPA Semester 1" required maxlength="100">
          <div class="form-hint">Nama akan muncul di leaderboard siswa.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Filter Kelas</label>
          <select name="kelas" class="form-select">
            <option value="0">Semua Kelas</option>
            <?php for ($k=1;$k<=6;$k++): ?>
            <option value="<?= $k ?>">Kelas <?= $k ?> saja</option>
            <?php endfor; ?>
          </select>
          <div class="form-hint">Kosongkan untuk kompetisi lintas kelas.</div>
        </div>
        <button type="submit" class="btn btn-accent" style="width:100%;justify-content:center;margin-top:4px">
          <i class="bi bi-rocket-takeoff-fill"></i> Mulai Sesi Kompetisi
        </button>
      </form>
    </div>

  </div>

  <!-- LEADERBOARD SESI AKTIF -->
  <?php if ($sesi_aktif): ?>
  <div class="lb-card">
    <div class="lb-header">
      <h3><i class="bi bi-bar-chart-fill"></i> Leaderboard Sesi Ini</h3>
      <span class="lb-badge">🔴 LIVE</span>
    </div>

    <?php if (empty($leaderboard)): ?>
    <div class="empty-lb">
      <div style="font-size:2rem;margin-bottom:8px">🕳️</div>
      Belum ada siswa yang mengumpulkan XP di sesi ini.<br>
      <span style="font-size:.78rem">XP akan muncul saat siswa menyelesaikan kuis.</span>
    </div>
    <?php else: ?>

    <!-- PODIUM top 3 -->
    <?php
      $top = array_slice($leaderboard, 0, 3);
      $p1 = $top[0] ?? null;
      $p2 = $top[1] ?? null;
      $p3 = $top[2] ?? null;
    ?>
    <div class="podium">
      <!-- Posisi 2 -->
      <?php if ($p2): ?>
      <div class="podium-item">
        <div class="podium-avatar"><?= getAva($p2['avatar']) ?></div>
        <div class="podium-name"><?= htmlspecialchars($p2['nama']) ?></div>
        <div class="podium-kelas">Kelas <?= $p2['kelas'] ?></div>
        <div class="podium-xp"><?= number_format($p2['xp']) ?> XP</div>
        <div class="podium-stand stand-2">2</div>
      </div>
      <?php endif; ?>

      <!-- Posisi 1 -->
      <?php if ($p1): ?>
      <div class="podium-item">
        <div class="podium-avatar">
          <span class="crown">👑</span>
          <?= getAva($p1['avatar']) ?>
        </div>
        <div class="podium-name"><?= htmlspecialchars($p1['nama']) ?></div>
        <div class="podium-kelas">Kelas <?= $p1['kelas'] ?></div>
        <div class="podium-xp"><?= number_format($p1['xp']) ?> XP</div>
        <div class="podium-stand stand-1">1</div>
      </div>
      <?php endif; ?>

      <!-- Posisi 3 -->
      <?php if ($p3): ?>
      <div class="podium-item">
        <div class="podium-avatar"><?= getAva($p3['avatar']) ?></div>
        <div class="podium-name"><?= htmlspecialchars($p3['nama']) ?></div>
        <div class="podium-kelas">Kelas <?= $p3['kelas'] ?></div>
        <div class="podium-xp"><?= number_format($p3['xp']) ?> XP</div>
        <div class="podium-stand stand-3">3</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Rank 4–10 -->
    <?php $rest = array_slice($leaderboard, 3); ?>
    <?php if ($rest): ?>
    <div class="rank-list">
      <?php foreach ($rest as $r): ?>
      <div class="rank-row">
        <div class="rank-num"><?= $r['ranking'] ?></div>
        <div class="rank-ava"><?= getAva($r['avatar']) ?></div>
        <div class="rank-name"><?= htmlspecialchars($r['nama']) ?></div>
        <div class="rank-kelas">Kelas <?= $r['kelas'] ?></div>
        <div class="rank-xp"><?= number_format($r['xp']) ?> XP</div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- RIWAYAT SESI -->
  <div class="history-card">
    <div class="history-header">
      <h3><i class="bi bi-clock-history"></i> Riwayat Sesi</h3>
      <span style="font-size:.8rem;color:var(--muted)"><?= $total_sesi ?> sesi tercatat</span>
    </div>

    <?php if (empty($all_sesi)): ?>
    <div style="text-align:center;padding:40px;color:var(--muted)">
      <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3">📋</div>
      Belum ada sesi yang pernah dibuat.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama Sesi</th>
          <th>Kelas</th>
          <th>Mulai</th>
          <th>Selesai</th>
          <th>Durasi</th>
          <th>Peserta</th>
          <th>XP Tertinggi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($all_sesi as $i => $s): ?>
        <tr>
          <td style="color:var(--muted);font-weight:700"><?= $i+1 ?></td>
          <td style="font-weight:700"><?= htmlspecialchars($s['nama']) ?></td>
          <td>
            <?php if ($s['kelas']): ?>
            <span class="kelas-chip">Kelas <?= $s['kelas'] ?></span>
            <?php else: ?>
            <span style="font-size:.78rem;color:var(--muted)">Semua</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.82rem"><?= date('d M Y<br>H:i', strtotime($s['mulai'])) ?></td>
          <td style="font-size:.82rem">
            <?= $s['selesai'] ? date('d M Y<br>H:i', strtotime($s['selesai'])) : '<span style="color:var(--secondary);font-weight:700">Masih berjalan</span>' ?>
          </td>
          <td style="font-size:.82rem;color:var(--muted)"><?= durasi_label($s['mulai'], $s['selesai']) ?></td>
          <td style="font-weight:800;text-align:center"><?= $s['peserta'] ?></td>
          <td style="font-weight:800;color:var(--accent)"><?= $s['xp_tertinggi'] ? number_format($s['xp_tertinggi']).' XP' : '-' ?></td>
          <td>
            <span class="status-badge <?= $s['aktif'] ? 'status-aktif' : 'status-selesai' ?>">
              <?= $s['aktif'] ? '🟢 Aktif' : '⚫ Selesai' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if (!$s['aktif']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="aktifkan_sesi">
                <input type="hidden" name="sesi_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-sm btn-green" title="Aktifkan kembali"
                  onclick="return confirm('Aktifkan sesi ini? Sesi aktif saat ini (jika ada) akan ditutup.')">
                  <i class="bi bi-play-fill"></i>
                </button>
              </form>
              <?php endif; ?>
              <button class="btn btn-sm btn-ghost" style="color:var(--error);border-color:#ffd0d0"
                title="Hapus sesi"
                onclick="bukaHapus(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>', <?= $s['aktif'] ? 'true' : 'false' ?>)">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

</main>

<!-- MODAL: TUTUP SESI -->
<div class="modal-backdrop" id="modalTutup">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="bi bi-stop-circle-fill" style="color:var(--error)"></i> Tutup Sesi</h3>
      <button class="btn-close-modal" onclick="tutupModal('modalTutup')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <span class="confirm-icon">🏁</span>
      <p class="confirm-text">Tutup sesi <span class="confirm-name" id="tutupNama"></span>?<br>
      <span style="font-size:.83rem;color:var(--muted)">Siswa tidak bisa lagi mengumpulkan XP untuk sesi ini. Data leaderboard tetap tersimpan.</span></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="tutupModal('modalTutup')">Batal</button>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="tutup_sesi">
        <input type="hidden" name="sesi_id" id="tutupSesiId">
        <button type="submit" class="btn btn-danger"><i class="bi bi-stop-fill"></i> Tutup Sesi</button>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: RESET XP -->
<div class="modal-backdrop" id="modalReset">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="bi bi-arrow-counterclockwise" style="color:#e65100"></i> Reset XP Sesi</h3>
      <button class="btn-close-modal" onclick="tutupModal('modalReset')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <span class="confirm-icon">⚡</span>
      <p class="confirm-text">
        Reset semua XP sesi untuk <span class="confirm-name" id="resetNama"></span>?<br>
        <span style="font-size:.83rem;color:var(--error)">Semua peserta akan mulai dari 0 XP. XP lifetime siswa tidak terpengaruh.</span>
      </p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="tutupModal('modalReset')">Batal</button>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="reset_xp_sesi">
        <input type="hidden" name="sesi_id" id="resetSesiId">
        <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-counterclockwise"></i> Ya, Reset XP</button>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: HAPUS SESI -->
<div class="modal-backdrop" id="modalHapus">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="bi bi-trash-fill" style="color:var(--error)"></i> Hapus Sesi</h3>
      <button class="btn-close-modal" onclick="tutupModal('modalHapus')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <span class="confirm-icon">🗑️</span>
      <p class="confirm-text" id="hapusPesan"></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="tutupModal('modalHapus')">Batal</button>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="hapus_sesi">
        <input type="hidden" name="sesi_id" id="hapusSesiId">
        <button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill"></i> Hapus Permanen</button>
      </form>
    </div>
  </div>
</div>

<script>
setTimeout(() => {
  const t = document.getElementById('toastEl');
  if (t) { t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(()=>t.remove(),400); }
}, 3500);

function tutupModal(id) { document.getElementById(id).classList.remove('open'); }
function bukaModal(id)  { document.getElementById(id).classList.add('open'); }

document.querySelectorAll('.modal-backdrop').forEach(el =>
  el.addEventListener('click', e => { if(e.target===el) el.classList.remove('open'); })
);

function bukaTutup(id, nama) {
  document.getElementById('tutupNama').textContent = '"' + nama + '"';
  document.getElementById('tutupSesiId').value = id;
  bukaModal('modalTutup');
}

function bukaReset(id, nama) {
  document.getElementById('resetNama').textContent = '"' + nama + '"';
  document.getElementById('resetSesiId').value = id;
  bukaModal('modalReset');
}

function bukaHapus(id, nama, isAktif) {
  document.getElementById('hapusSesiId').value = id;
  document.getElementById('hapusPesan').innerHTML =
    'Hapus permanen sesi <b style="color:var(--primary)">"' + nama + '"</b>?<br>' +
    (isAktif ? '<span style="color:var(--error);font-size:.83rem">⚠️ Sesi ini masih aktif!</span><br>' : '') +
    '<span style="font-size:.83rem;color:var(--muted)">Semua data XP sesi akan ikut terhapus.</span>';
  bukaModal('modalHapus');
}
</script>

</body>
</html>