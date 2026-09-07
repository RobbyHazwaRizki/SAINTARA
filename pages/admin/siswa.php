<?php
/**
 * SAINTARA — Ella's Lab
 * pages/admin/siswa.php
 * Panel Guru — Manajemen Data Siswa
 */

require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

requireGuru();

$pdo = getDB();
$guru = getGuruSession();

// ─────────────────────────────────────────────
// AKSI: Reset Progress Siswa
// ─────────────────────────────────────────────
$action_msg  = '';
$action_type = ''; // success | error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = bersihkan($_POST['action'] ?? '');
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);

    if ($siswa_id <= 0) {
        $action_msg  = 'ID siswa tidak valid.';
        $action_type = 'error';
    } else {
        switch ($action) {

            // ── Reset XP & Level ──────────────────────────
            case 'reset_xp':
                $stmt = $pdo->prepare("UPDATE siswa SET xp=0, level=1, streak=0, total_benar=0, total_soal=0 WHERE id=?");
                $stmt->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM xp_sesi WHERE siswa_id=?")->execute([$siswa_id]);
                $action_msg  = 'XP & level siswa berhasil direset.';
                $action_type = 'success';
                break;

            // ── Reset Progress Topik ──────────────────────
            case 'reset_progress':
                $pdo->prepare("DELETE FROM progress WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM riwayat_kuis WHERE siswa_id=?")->execute([$siswa_id]);
                $action_msg  = 'Progress & riwayat kuis siswa berhasil dihapus.';
                $action_type = 'success';
                break;

            // ── Reset Badge ───────────────────────────────
            case 'reset_badge':
                $pdo->prepare("DELETE FROM badge_siswa WHERE siswa_id=?")->execute([$siswa_id]);
                $action_msg  = 'Badge siswa berhasil dihapus.';
                $action_type = 'success';
                break;

            // ── Reset Semua (Full Reset) ──────────────────
            case 'reset_all':
                $pdo->prepare("UPDATE siswa SET xp=0, level=1, streak=0, total_benar=0, total_soal=0 WHERE id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM progress WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM riwayat_kuis WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM badge_siswa WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM xp_sesi WHERE siswa_id=?")->execute([$siswa_id]);
                $action_msg  = 'Semua data siswa berhasil direset total.';
                $action_type = 'success';
                break;

            // ── Hapus Siswa ───────────────────────────────
            case 'hapus_siswa':
                $pdo->prepare("DELETE FROM progress WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM riwayat_kuis WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM badge_siswa WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM xp_sesi WHERE siswa_id=?")->execute([$siswa_id]);
                $pdo->prepare("DELETE FROM siswa WHERE id=?")->execute([$siswa_id]);
                $action_msg  = 'Data siswa berhasil dihapus permanen.';
                $action_type = 'success';
                break;

            default:
                $action_msg  = 'Aksi tidak dikenal.';
                $action_type = 'error';
        }
    }
}

// ─────────────────────────────────────────────
// FILTER & QUERY SISWA
// ─────────────────────────────────────────────
$filter_kelas  = (int)($_GET['kelas'] ?? 0);
$search_nama   = bersihkan($_GET['q'] ?? '');
$sort_by       = bersihkan($_GET['sort'] ?? 'nama');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 15;
$offset        = ($page - 1) * $per_page;

$allowed_sort = ['nama', 'kelas', 'xp', 'streak', 'total_benar'];
if (!in_array($sort_by, $allowed_sort)) $sort_by = 'nama';

// Build WHERE
$where_parts = [];
$params      = [];

if ($filter_kelas >= 1 && $filter_kelas <= 6) {
    $where_parts[] = "s.kelas = ?";
    $params[]      = $filter_kelas;
}
if ($search_nama !== '') {
    $where_parts[] = "s.nama LIKE ?";
    $params[]      = '%' . $search_nama . '%';
}

$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// Total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa s $where_sql");
$count_stmt->execute($params);
$total_siswa = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_siswa / $per_page));
$page = min($page, $total_pages);

// Fetch siswa dengan aggregasi
$params_paged = array_merge($params, [$per_page, $offset]);
$stmt = $pdo->prepare("
    SELECT
        s.*,
        (SELECT COUNT(*) FROM progress p WHERE p.siswa_id = s.id AND p.selesai = 1) AS topik_selesai,
        (SELECT COUNT(*) FROM badge_siswa b WHERE b.siswa_id = s.id) AS jumlah_badge,
        (SELECT COUNT(*) FROM riwayat_kuis r WHERE r.siswa_id = s.id) AS jumlah_kuis
    FROM siswa s
    $where_sql
    ORDER BY s.$sort_by ASC
    LIMIT ? OFFSET ?
");
$stmt->execute($params_paged);
$list_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stat ringkasan
$stat_stmt = $pdo->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN kelas=1 THEN 1 ELSE 0 END) AS k1,
    SUM(CASE WHEN kelas=2 THEN 1 ELSE 0 END) AS k2,
    SUM(CASE WHEN kelas=3 THEN 1 ELSE 0 END) AS k3,
    SUM(CASE WHEN kelas=4 THEN 1 ELSE 0 END) AS k4,
    SUM(CASE WHEN kelas=5 THEN 1 ELSE 0 END) AS k5,
    SUM(CASE WHEN kelas=6 THEN 1 ELSE 0 END) AS k6,
    AVG(xp) AS avg_xp,
    MAX(xp) AS max_xp
FROM siswa");
$stat = $stat_stmt->fetch(PDO::FETCH_ASSOC);

// Data detail siswa untuk modal (via AJAX atau query awal)
$detail_id = (int)($_GET['detail'] ?? 0);
$detail_siswa = null;
$detail_progress = [];
$detail_badge = [];
$detail_riwayat = [];

if ($detail_id > 0) {
    $detail_siswa = $pdo->prepare("SELECT * FROM siswa WHERE id=?");
    $detail_siswa->execute([$detail_id]);
    $detail_siswa = $detail_siswa->fetch(PDO::FETCH_ASSOC);

    if ($detail_siswa) {
        $dp = $pdo->prepare("
            SELECT p.*, t.judul, t.kelas, t.icon
            FROM progress p
            JOIN topik t ON t.id = p.topik_id
            WHERE p.siswa_id = ?
            ORDER BY t.kelas, t.urutan
        ");
        $dp->execute([$detail_id]);
        $detail_progress = $dp->fetchAll(PDO::FETCH_ASSOC);

        $db = $pdo->prepare("SELECT badge_key, unlocked_at FROM badge_siswa WHERE siswa_id=? ORDER BY unlocked_at DESC");
        $db->execute([$detail_id]);
        $detail_badge = $db->fetchAll(PDO::FETCH_ASSOC);

        $dr = $pdo->prepare("
            SELECT r.*, t.judul
            FROM riwayat_kuis r
            JOIN topik t ON t.id = r.topik_id
            WHERE r.siswa_id = ?
            ORDER BY r.played_at DESC
            LIMIT 10
        ");
        $dr->execute([$detail_id]);
        $detail_riwayat = $dr->fetchAll(PDO::FETCH_ASSOC);
    }
}

$all_badges = getAllBadges();

// ─────────────────────────────────────────────
// Avatar helper
// ─────────────────────────────────────────────
$avatars = ['🐘','🦁','🐯','🦊','🐧','🦋','🐬','🦜','🐸','🌟'];

function getAvatarEmoji($avatar_idx) {
    global $avatars;
    $idx = (int)$avatar_idx;
    return $avatars[$idx] ?? '🐘';
}

$level_names = [1=>'Penjelajah Baru',2=>'Petualang Muda',3=>'Ilmuwan Cilik',4=>'Peneliti Muda',5=>'Profesor Kecil'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Siswa — Saintara Admin</title>
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

body {
  font-family: 'Nunito', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
}

h1,h2,h3,h4 { font-family: 'Fredoka', sans-serif; }

/* ── SIDEBAR ─────────────────────────────── */
.sidebar {
  width: var(--sidebar-w);
  background: var(--navy);
  color: var(--white);
  position: fixed;
  top: 0; left: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  z-index: 100;
  overflow-y: auto;
}

.sidebar-brand {
  padding: 24px 20px 20px;
  border-bottom: 1px solid rgba(255,255,255,.08);
}

.sidebar-brand .brand-logo {
  font-family: 'Fredoka', sans-serif;
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--primary);
  display: flex; align-items: center; gap: 8px;
}

.sidebar-brand .brand-logo span { color: var(--accent); }
.sidebar-brand small { color: var(--muted); font-size: .75rem; display: block; margin-top: 4px; }

.sidebar-nav { padding: 12px 0; flex: 1; }

.nav-label {
  font-size: .65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  color: var(--muted);
  padding: 16px 20px 6px;
}

.nav-link {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 20px;
  color: rgba(255,255,255,.65);
  text-decoration: none;
  font-size: .9rem;
  font-weight: 600;
  transition: all .2s;
  border-left: 3px solid transparent;
}

.nav-link:hover { color: var(--white); background: rgba(255,255,255,.05); }
.nav-link.active { color: var(--white); background: rgba(77,168,218,.15); border-left-color: var(--primary); }
.nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }

.sidebar-footer {
  padding: 16px 20px;
  border-top: 1px solid rgba(255,255,255,.08);
}

.guru-info { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.guru-avatar { width: 36px; height: 36px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.guru-name { font-size: .85rem; font-weight: 700; color: var(--white); }
.guru-role { font-size: .7rem; color: var(--muted); }

.btn-logout {
  display: flex; align-items: center; gap: 8px;
  width: 100%; padding: 9px 14px;
  background: rgba(224,85,85,.15);
  color: #ff8080;
  border: 1px solid rgba(224,85,85,.25);
  border-radius: 8px;
  font-size: .83rem;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition: all .2s;
}

.btn-logout:hover { background: rgba(224,85,85,.3); color: #ff6060; }

/* ── MAIN CONTENT ────────────────────────── */
.main-content {
  margin-left: var(--sidebar-w);
  flex: 1;
  padding: 28px 32px;
  min-height: 100vh;
}

/* ── PAGE HEADER ─────────────────────────── */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 28px;
  flex-wrap: wrap; gap: 16px;
}

.page-title { font-size: 1.8rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 10px; }
.page-title i { color: var(--primary); }
.page-subtitle { font-size: .9rem; color: var(--muted); margin-top: 2px; }

/* ── TOAST NOTIF ─────────────────────────── */
.toast-container {
  position: fixed;
  top: 24px; right: 24px;
  z-index: 9999;
  display: flex; flex-direction: column; gap: 10px;
}

.toast {
  background: var(--white);
  border-radius: 12px;
  padding: 14px 18px;
  display: flex; align-items: center; gap: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,.12);
  border-left: 4px solid var(--secondary);
  min-width: 280px;
  animation: toastIn .3s ease;
  font-weight: 600;
  font-size: .88rem;
}

.toast.error { border-left-color: var(--error); }
.toast .toast-icon { font-size: 1.2rem; }
.toast.success .toast-icon { color: var(--secondary); }
.toast.error .toast-icon { color: var(--error); }

@keyframes toastIn { from { transform: translateX(40px); opacity: 0; } to { transform: none; opacity: 1; } }

/* ── STAT CARDS ──────────────────────────── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}

.stat-card {
  background: var(--white);
  border-radius: var(--radius);
  padding: 20px;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}

.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--card-color, var(--primary));
}

.stat-card .stat-icon {
  width: 42px; height: 42px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--card-color, var(--primary)) 12%, transparent);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem;
  color: var(--card-color, var(--primary));
  margin-bottom: 12px;
}

.stat-value { font-family: 'Fredoka', sans-serif; font-size: 1.9rem; font-weight: 700; line-height: 1; color: var(--text); }
.stat-label { font-size: .78rem; color: var(--muted); margin-top: 4px; font-weight: 600; }
.stat-sub { font-size: .72rem; color: var(--muted); margin-top: 6px; }

/* ── TOOLBAR ─────────────────────────────── */
.toolbar {
  background: var(--white);
  border-radius: var(--radius);
  padding: 16px 20px;
  box-shadow: var(--shadow);
  display: flex; align-items: center; gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.search-box {
  display: flex; align-items: center; gap: 8px;
  background: var(--bg);
  border: 1.5px solid #e2edf5;
  border-radius: 10px;
  padding: 8px 14px;
  flex: 1; min-width: 200px;
  transition: border-color .2s;
}

.search-box:focus-within { border-color: var(--primary); }
.search-box i { color: var(--muted); font-size: 1rem; }
.search-box input { border: none; background: none; outline: none; font-family: 'Nunito', sans-serif; font-size: .9rem; color: var(--text); width: 100%; }

.filter-pill {
  display: flex; gap: 6px; flex-wrap: wrap;
}

.pill {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: .8rem;
  font-weight: 700;
  cursor: pointer;
  border: 2px solid transparent;
  background: var(--bg);
  color: var(--muted);
  transition: all .2s;
  text-decoration: none;
  white-space: nowrap;
}

.pill:hover { border-color: var(--primary); color: var(--primary); }
.pill.active { background: var(--primary); color: var(--white); border-color: var(--primary); }

.sort-select {
  padding: 8px 14px;
  border: 1.5px solid #e2edf5;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif;
  font-size: .85rem;
  color: var(--text);
  background: var(--bg);
  outline: none;
  cursor: pointer;
  transition: border-color .2s;
}

.sort-select:focus { border-color: var(--primary); }

/* ── TABLE ───────────────────────────────── */
.table-card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.table-header {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  display: flex; align-items: center; justify-content: space-between;
}

.table-header h3 { font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.table-header h3 i { color: var(--primary); }

.result-count { font-size: .8rem; color: var(--muted); background: var(--bg); padding: 4px 10px; border-radius: 20px; font-weight: 600; }

table { width: 100%; border-collapse: collapse; }

thead tr { background: #f7fafd; }
thead th {
  padding: 12px 16px;
  text-align: left;
  font-size: .75rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--muted);
  border-bottom: 1px solid #edf2f7;
  white-space: nowrap;
}

thead th.sort-active { color: var(--primary); }

tbody tr {
  border-bottom: 1px solid #f0f4f8;
  transition: background .15s;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f7fafd; }

tbody td { padding: 13px 16px; font-size: .88rem; vertical-align: middle; }

/* ── AVATAR CELL ─────────────────────────── */
.avatar-cell { display: flex; align-items: center; gap: 10px; }
.avatar-circle {
  width: 38px; height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.student-name { font-weight: 700; color: var(--text); }
.student-meta { font-size: .73rem; color: var(--muted); }

/* ── KELAS BADGE ─────────────────────────── */
.kelas-badge {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px;
  background: var(--primary);
  color: var(--white);
  border-radius: 8px;
  font-weight: 800;
  font-size: .8rem;
}

/* ── XP BAR ──────────────────────────────── */
.xp-cell { min-width: 110px; }
.xp-val { font-weight: 800; color: var(--text); font-size: .9rem; }
.xp-bar-wrap { height: 5px; background: #edf2f7; border-radius: 3px; margin-top: 4px; }
.xp-bar-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); transition: width .4s; }

/* ── LEVEL BADGE ─────────────────────────── */
.level-badge {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 20px;
  font-size: .72rem;
  font-weight: 800;
  white-space: nowrap;
}

.level-1 { background: #e8f5e9; color: #388e3c; }
.level-2 { background: #e3f2fd; color: #1565c0; }
.level-3 { background: #f3e5f5; color: #7b1fa2; }
.level-4 { background: #fff3e0; color: #e65100; }
.level-5 { background: #fff8e1; color: #f57f17; }

/* ── ACTION BUTTONS ──────────────────────── */
.action-btns { display: flex; gap: 6px; }

.btn-icon {
  width: 32px; height: 32px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem;
  transition: all .2s;
}

.btn-icon.detail { background: #e8f4fc; color: var(--primary); }
.btn-icon.detail:hover { background: var(--primary); color: var(--white); }
.btn-icon.reset { background: #fff3e0; color: #e65100; }
.btn-icon.reset:hover { background: #e65100; color: var(--white); }
.btn-icon.hapus { background: #fdecea; color: var(--error); }
.btn-icon.hapus:hover { background: var(--error); color: var(--white); }

/* ── PAGINATION ──────────────────────────── */
.pagination {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 20px;
  border-top: 1px solid #edf2f7;
  flex-wrap: wrap; gap: 10px;
}

.page-info { font-size: .82rem; color: var(--muted); font-weight: 600; }

.page-btns { display: flex; gap: 6px; }

.page-btn {
  width: 34px; height: 34px;
  border-radius: 8px;
  border: 1.5px solid #e2edf5;
  background: var(--white);
  color: var(--text);
  font-size: .82rem;
  font-weight: 700;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  text-decoration: none;
  transition: all .2s;
}

.page-btn:hover { border-color: var(--primary); color: var(--primary); }
.page-btn.active { background: var(--primary); color: var(--white); border-color: var(--primary); }
.page-btn.disabled { opacity: .4; pointer-events: none; }

/* ── EMPTY STATE ─────────────────────────── */
.empty-state {
  text-align: center;
  padding: 64px 24px;
}

.empty-icon { font-size: 3.5rem; margin-bottom: 16px; display: block; opacity: .35; }
.empty-title { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.empty-sub { font-size: .88rem; color: var(--muted); }

/* ══════════════════════════════════════════
   MODAL
══════════════════════════════════════════ */
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(26,46,58,.5);
  backdrop-filter: blur(4px);
  z-index: 500;
  display: none; align-items: center; justify-content: center;
  padding: 20px;
}

.modal-backdrop.open { display: flex; }

.modal {
  background: var(--white);
  border-radius: 20px;
  max-width: 520px;
  width: 100%;
  box-shadow: 0 24px 64px rgba(0,0,0,.18);
  animation: modalIn .25s ease;
  overflow: hidden;
}

.modal-lg { max-width: 720px; }

@keyframes modalIn { from { transform: scale(.94) translateY(16px); opacity: 0; } to { transform: none; opacity: 1; } }

.modal-head {
  padding: 20px 24px;
  border-bottom: 1px solid #edf2f7;
  display: flex; align-items: center; justify-content: space-between;
}

.modal-head h3 { font-size: 1.1rem; display: flex; align-items: center; gap: 8px; }
.modal-head h3 i { color: var(--primary); }

.btn-close-modal {
  width: 32px; height: 32px;
  border-radius: 8px;
  border: none;
  background: #f0f4f8;
  color: var(--muted);
  font-size: 1rem;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}

.btn-close-modal:hover { background: var(--error); color: var(--white); }

.modal-body { padding: 24px; }
.modal-foot {
  padding: 16px 24px;
  border-top: 1px solid #edf2f7;
  display: flex; justify-content: flex-end; gap: 10px;
  flex-wrap: wrap;
}

/* ── DETAIL MODAL ────────────────────────── */
.detail-hero {
  text-align: center;
  padding-bottom: 20px;
  border-bottom: 1px solid #edf2f7;
  margin-bottom: 20px;
}

.detail-avatar {
  width: 64px; height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem;
  margin: 0 auto 12px;
}

.detail-name { font-family: 'Fredoka', sans-serif; font-size: 1.4rem; font-weight: 700; }
.detail-meta { font-size: .85rem; color: var(--muted); }

.detail-stats {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 12px; margin-bottom: 20px;
}

.detail-stat {
  background: var(--bg);
  border-radius: 12px;
  padding: 12px;
  text-align: center;
}

.detail-stat-val { font-family: 'Fredoka', sans-serif; font-size: 1.4rem; font-weight: 700; color: var(--primary); }
.detail-stat-lbl { font-size: .72rem; color: var(--muted); font-weight: 600; }

.detail-section-title { font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); margin-bottom: 10px; }

.progress-list { display: flex; flex-direction: column; gap: 6px; max-height: 160px; overflow-y: auto; padding-right: 4px; }

.progress-row {
  display: flex; align-items: center; gap: 10px;
  font-size: .82rem;
}

.progress-row .topic-icon { font-size: 1.1rem; width: 24px; text-align: center; }
.progress-row .topic-name { flex: 1; font-weight: 600; }
.progress-row .topic-score {
  padding: 2px 8px;
  border-radius: 20px;
  font-size: .72rem;
  font-weight: 800;
}

.topic-score.done { background: #e8f8f5; color: #00897b; }
.topic-score.undone { background: #f0f4f8; color: var(--muted); }

.badge-list-mini { display: flex; flex-wrap: wrap; gap: 6px; max-height: 80px; overflow-y: auto; }

.badge-chip {
  display: flex; align-items: center; gap: 5px;
  background: var(--bg);
  border-radius: 20px;
  padding: 4px 10px;
  font-size: .75rem;
  font-weight: 700;
}

.history-list { display: flex; flex-direction: column; gap: 6px; max-height: 140px; overflow-y: auto; }

.history-row {
  display: flex; align-items: center;
  background: var(--bg);
  border-radius: 10px;
  padding: 8px 12px;
  font-size: .8rem;
  gap: 10px;
}

.history-row .h-score { font-weight: 800; color: var(--text); min-width: 40px; }
.history-row .h-topic { flex: 1; color: var(--text); font-weight: 600; }
.history-row .h-xp { color: var(--accent); font-weight: 800; font-size: .75rem; }
.history-row .h-date { color: var(--muted); font-size: .72rem; }

/* ── RESET MODAL ─────────────────────────── */
.reset-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.reset-option {
  border: 2px solid #edf2f7;
  border-radius: 12px;
  padding: 14px;
  cursor: pointer;
  transition: all .2s;
  text-align: center;
  background: var(--white);
}

.reset-option:hover { border-color: var(--primary); background: #f0f8ff; }
.reset-option.danger:hover { border-color: var(--error); background: #fff5f5; }
.reset-option i { font-size: 1.4rem; display: block; margin-bottom: 6px; }
.reset-option .opt-title { font-size: .82rem; font-weight: 800; color: var(--text); }
.reset-option .opt-desc { font-size: .72rem; color: var(--muted); margin-top: 3px; }

/* ── CONFIRM MODAL ───────────────────────── */
.confirm-icon { font-size: 3rem; text-align: center; display: block; margin-bottom: 12px; }
.confirm-text { text-align: center; font-size: .92rem; color: var(--text); line-height: 1.6; }
.confirm-name { font-weight: 800; color: var(--primary); }

/* ── BUTTONS ─────────────────────────────── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif;
  font-size: .88rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: all .2s;
  text-decoration: none;
}

.btn-primary { background: var(--primary); color: var(--white); }
.btn-primary:hover { background: #3a90c2; transform: translateY(-1px); }
.btn-danger { background: var(--error); color: var(--white); }
.btn-danger:hover { background: #c44; transform: translateY(-1px); }
.btn-ghost { background: var(--bg); color: var(--text); border: 1.5px solid #e2edf5; }
.btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
.btn-warning { background: #ff9800; color: var(--white); }
.btn-warning:hover { background: #f57c00; }

/* ── SCROLLBAR ───────────────────────────── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #d0e4ef; border-radius: 10px; }

/* ── RESPONSIVE ──────────────────────────── */
@media (max-width: 900px) {
  .sidebar { transform: translateX(-100%); transition: transform .3s; }
  .sidebar.open { transform: none; }
  .main-content { margin-left: 0; padding: 20px 16px; }
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .detail-stats { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 600px) {
  .reset-options { grid-template-columns: 1fr; }
  table { font-size: .8rem; }
  thead th, tbody td { padding: 10px 10px; }
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><i class="bi bi-stars"></i> Saintara <span>Admin</span></div>
    <small>Panel Guru — Ella's Lab</small>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menu Utama</div>
    <a href="/SAINTARA/pages/admin/dashboard.php" class="nav-link">
      <i class="bi bi-grid-1x2-fill"></i> Dashboard
    </a>
    <a href="/SAINTARA/pages/admin/siswa.php" class="nav-link active">
      <i class="bi bi-people-fill"></i> Data Siswa
    </a>
    <a href="/SAINTARA/pages/admin/soal.php" class="nav-link">
      <i class="bi bi-patch-question-fill"></i> Bank Soal
    </a>
    <a href="/SAINTARA/pages/admin/sesi.php" class="nav-link">
      <i class="bi bi-trophy-fill"></i> Sesi Kompetisi
    </a>
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

<!-- ══════════════════════════════════════════
     TOAST NOTIFIKASI
══════════════════════════════════════════ -->
<?php if ($action_msg): ?>
<div class="toast-container" id="toastContainer">
  <div class="toast <?= $action_type ?>">
    <span class="toast-icon"><?= $action_type === 'success' ? '✅' : '❌' ?></span>
    <?= htmlspecialchars($action_msg) ?>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════ -->
<main class="main-content">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div>
      <h1 class="page-title"><i class="bi bi-people-fill"></i> Data Siswa</h1>
      <p class="page-subtitle">Kelola & pantau perkembangan seluruh siswa Saintara</p>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="stat-grid">
    <div class="stat-card" style="--card-color:#4DA8DA">
      <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
      <div class="stat-value"><?= number_format($stat['total']) ?></div>
      <div class="stat-label">Total Siswa</div>
    </div>
    <?php for ($k = 1; $k <= 6; $k++): ?>
    <div class="stat-card" style="--card-color:<?= ['#4DA8DA','#80D8C3','#FFD66B','#a78bfa','#f97316','#e05555'][$k-1] ?>">
      <div class="stat-icon"><i class="bi bi-<?= ['1-circle','2-circle','3-circle','4-circle','5-circle','6-circle'][$k-1] ?>-fill"></i></div>
      <div class="stat-value"><?= (int)($stat["k$k"] ?? 0) ?></div>
      <div class="stat-label">Kelas <?= $k ?></div>
    </div>
    <?php endfor; ?>
    <div class="stat-card" style="--card-color:#FFD66B">
      <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
      <div class="stat-value"><?= round($stat['avg_xp'] ?? 0) ?></div>
      <div class="stat-label">Rata-rata XP</div>
      <div class="stat-sub">Max: <?= number_format($stat['max_xp'] ?? 0) ?> XP</div>
    </div>
  </div>

  <!-- TOOLBAR -->
  <form method="GET" action="" id="filterForm">
    <div class="toolbar">
      <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" name="q" placeholder="Cari nama siswa…" value="<?= htmlspecialchars($search_nama) ?>" id="searchInput">
      </div>

      <div class="filter-pill">
        <a href="?sort=<?= $sort_by ?>" class="pill <?= $filter_kelas === 0 ? 'active' : '' ?>">Semua</a>
        <?php for ($k = 1; $k <= 6; $k++): ?>
        <a href="?kelas=<?= $k ?>&sort=<?= $sort_by ?><?= $search_nama ? '&q='.urlencode($search_nama) : '' ?>"
           class="pill <?= $filter_kelas === $k ? 'active' : '' ?>">Kelas <?= $k ?></a>
        <?php endfor; ?>
      </div>

      <select class="sort-select" name="sort" onchange="document.getElementById('filterForm').submit()">
        <option value="nama"       <?= $sort_by==='nama'        ? 'selected':'' ?>>Urutkan: Nama</option>
        <option value="kelas"      <?= $sort_by==='kelas'       ? 'selected':'' ?>>Urutkan: Kelas</option>
        <option value="xp"         <?= $sort_by==='xp'          ? 'selected':'' ?>>Urutkan: XP</option>
        <option value="streak"     <?= $sort_by==='streak'      ? 'selected':'' ?>>Urutkan: Streak</option>
        <option value="total_benar"<?= $sort_by==='total_benar' ? 'selected':'' ?>>Urutkan: Soal Benar</option>
      </select>

      <?php if ($filter_kelas) echo '<input type="hidden" name="kelas" value="'.$filter_kelas.'">'; ?>
    </div>
  </form>

  <!-- TABLE CARD -->
  <div class="table-card">
    <div class="table-header">
      <h3><i class="bi bi-table"></i> Daftar Siswa</h3>
      <span class="result-count"><?= number_format($total_siswa) ?> siswa ditemukan</span>
    </div>

    <?php if (empty($list_siswa)): ?>
    <div class="empty-state">
      <span class="empty-icon">🔍</span>
      <div class="empty-title">Tidak ada siswa ditemukan</div>
      <div class="empty-sub">Coba ubah filter atau kata kunci pencarian</div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Siswa</th>
          <th>Kelas</th>
          <th class="<?= $sort_by==='xp'?'sort-active':'' ?>">XP / Level</th>
          <th class="<?= $sort_by==='streak'?'sort-active':'' ?>">Streak</th>
          <th>Topik Selesai</th>
          <th>Badge</th>
          <th class="<?= $sort_by==='total_benar'?'sort-active':'' ?>">Soal Benar</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list_siswa as $i => $s):
          $level_key = (int)($s['level'] ?? 1);
          $level_key = max(1, min(5, $level_key));
          
          // PERBAIKAN: Gunakan array statis agar aman dari error tipe data (Array)
          $batas_xp = [1 => 0, 2 => 100, 3 => 300, 4 => 600, 5 => 1000, 6 => 999999]; 
          
          $xp_prev     = $batas_xp[$level_key] ?? 0;
          $xp_next     = $batas_xp[$level_key + 1] ?? 999999;
          $xp_sekarang = (int)($s['xp'] ?? 0);
          
          $xp_pct = 100;
          if ($xp_next > $xp_prev) {
              $xp_pct = min(100, max(0, round(($xp_sekarang - $xp_prev) / ($xp_next - $xp_prev) * 100)));
          }
        ?>
        <tr>
          <td style="color:var(--muted);font-weight:700"><?= $offset + $i + 1 ?></td>
          <td>
            <div class="avatar-cell">
              <div class="avatar-circle"><?= getAvatarEmoji($s['avatar'] ?? 0) ?></div>
              <div>
                <div class="student-name"><?= htmlspecialchars($s['nama']) ?></div>
                <div class="student-meta">#<?= $s['id'] ?> · <?= $s['jumlah_kuis'] ?> kuis dimainkan</div>
              </div>
            </div>
          </td>
          <td><span class="kelas-badge"><?= $s['kelas'] ?></span></td>
          <td class="xp-cell">
            <div class="xp-val"><?= number_format($s['xp']) ?> XP</div>
            <div style="margin:3px 0 2px"><span class="level-badge level-<?= $level_key ?>"><?= $level_names[$level_key] ?></span></div>
            <div class="xp-bar-wrap"><div class="xp-bar-fill" style="width:<?= $xp_pct ?>%"></div></div>
          </td>
          <td>
            <span style="font-weight:800;color:<?= $s['streak']>=3?'#e65100':'var(--text)' ?>">
              <?= $s['streak'] >= 3 ? '🔥' : '' ?> <?= $s['streak'] ?>
            </span>
          </td>
          <td style="font-weight:800"><?= $s['topik_selesai'] ?><span style="color:var(--muted);font-weight:600">/24</span></td>
          <td><span style="font-size:1rem">🏅</span> <b><?= $s['jumlah_badge'] ?></b></td>
          <td>
            <span style="font-weight:800;color:var(--secondary)"><?= number_format($s['total_benar']) ?></span>
            <?php if ($s['total_soal'] > 0): ?>
            <span style="font-size:.72rem;color:var(--muted)">
              / <?= $s['total_soal'] ?>
              (<?= round($s['total_benar']/$s['total_soal']*100) ?>%)
            </span>
            <?php endif; ?>
          </td>
          <td>
            <div class="action-btns">
              <button class="btn-icon detail" title="Lihat Detail"
                onclick="bukaDetail(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                <i class="bi bi-eye-fill"></i>
              </button>
              <button class="btn-icon reset" title="Reset Data"
                onclick="bukaReset(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>')">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
              <button class="btn-icon hapus" title="Hapus Siswa"
                onclick="bukaHapus(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>')">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <div class="page-info">
        Halaman <?= $page ?> dari <?= $total_pages ?> &middot;
        <?= number_format($total_siswa) ?> siswa
      </div>
      <div class="page-btns">
        <?php
        $q_str  = $search_nama ? '&q='.urlencode($search_nama) : '';
        $kl_str = $filter_kelas ? '&kelas='.$filter_kelas : '';
        $so_str = '&sort='.$sort_by;
        ?>
        <a href="?page=<?= max(1,$page-1) ?><?= $kl_str.$q_str.$so_str ?>"
           class="page-btn <?= $page<=1?'disabled':'' ?>">
          <i class="bi bi-chevron-left"></i>
        </a>
        <?php
        $start = max(1, $page-2);
        $end   = min($total_pages, $start+4);
        for ($p = $start; $p <= $end; $p++):
        ?>
        <a href="?page=<?= $p ?><?= $kl_str.$q_str.$so_str ?>"
           class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a href="?page=<?= min($total_pages,$page+1) ?><?= $kl_str.$q_str.$so_str ?>"
           class="page-btn <?= $page>=$total_pages?'disabled':'' ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

</main>

<!-- ══════════════════════════════════════════
     MODAL: DETAIL SISWA
══════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalDetail">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3><i class="bi bi-person-circle"></i> Detail Siswa</h3>
      <button class="btn-close-modal" onclick="tutupModal('modalDetail')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body" id="detailBody">
      <!-- diisi via JS -->
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="tutupModal('modalDetail')">Tutup</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL: RESET OPTIONS
══════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalReset">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="bi bi-arrow-counterclockwise"></i> Reset Data Siswa</h3>
      <button class="btn-close-modal" onclick="tutupModal('modalReset')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:.88rem;color:var(--muted);margin-bottom:16px">
        Reset data untuk: <strong id="resetNama" style="color:var(--primary)"></strong>
      </p>
      <div class="reset-options">
        <div class="reset-option" onclick="konfirmasiReset('reset_xp')">
          <i class="bi bi-lightning-charge-fill" style="color:#e65100"></i>
          <div class="opt-title">Reset XP & Level</div>
          <div class="opt-desc">XP, level, streak kembali ke 0</div>
        </div>
        <div class="reset-option" onclick="konfirmasiReset('reset_progress')">
          <i class="bi bi-book-fill" style="color:#7b1fa2"></i>
          <div class="opt-title">Reset Progress</div>
          <div class="opt-desc">Hapus progress topik & riwayat kuis</div>
        </div>
        <div class="reset-option" onclick="konfirmasiReset('reset_badge')">
          <i class="bi bi-award-fill" style="color:#1565c0"></i>
          <div class="opt-title">Reset Badge</div>
          <div class="opt-desc">Hapus semua badge yang diraih</div>
        </div>
        <div class="reset-option danger" onclick="konfirmasiReset('reset_all')">
          <i class="bi bi-nuclear-fill" style="color:var(--error)"></i>
          <div class="opt-title">Reset Total</div>
          <div class="opt-desc">Hapus semua data sekaligus</div>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="tutupModal('modalReset')">Batal</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL: KONFIRMASI AKSI
══════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalKonfirmasi">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="bi bi-exclamation-triangle-fill" style="color:#e65100"></i> <span id="konfirmasiJudul">Konfirmasi</span></h3>
      <button class="btn-close-modal" onclick="tutupModal('modalKonfirmasi')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <span class="confirm-icon" id="konfirmasiIcon">⚠️</span>
      <p class="confirm-text" id="konfirmasiPesan"></p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="tutupModal('modalKonfirmasi')">Batal</button>
      <button class="btn btn-danger" id="btnKonfirmasiOk" onclick="jalankanAksi()">
        <i class="bi bi-check-circle-fill"></i> Ya, Lanjutkan
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL: HAPUS SISWA
══════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalHapus">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="bi bi-trash-fill" style="color:var(--error)"></i> Hapus Siswa</h3>
      <button class="btn-close-modal" onclick="tutupModal('modalHapus')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <span class="confirm-icon">🗑️</span>
      <p class="confirm-text">
        Hapus permanen data siswa <span class="confirm-name" id="hapusNama"></span>?<br>
        <span style="color:var(--error);font-size:.83rem">Semua riwayat, badge, dan progress akan ikut terhapus dan tidak bisa dikembalikan.</span>
      </p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="tutupModal('modalHapus')">Batal</button>
      <form method="POST" style="display:inline" id="formHapus">
        <input type="hidden" name="action" value="hapus_siswa">
        <input type="hidden" name="siswa_id" id="hapusSiswaId">
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-trash-fill"></i> Hapus Permanen
        </button>
      </form>
    </div>
  </div>
</div>

<!-- HIDDEN FORM untuk reset -->
<form method="POST" id="formReset" style="display:none">
  <input type="hidden" name="action" id="resetAction">
  <input type="hidden" name="siswa_id" id="resetSiswaId">
</form>

<!-- ══════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════ -->
<script>
// ── Toast auto-hide ───────────────────────────
setTimeout(() => {
  const t = document.getElementById('toastContainer');
  if (t) t.style.opacity = '0', setTimeout(() => t.remove(), 400);
}, 3500);

// ── Modal helpers ─────────────────────────────
function bukaModal(id) { document.getElementById(id).classList.add('open'); }
function tutupModal(id) { document.getElementById(id).classList.remove('open'); }

// Klik luar modal → tutup
document.querySelectorAll('.modal-backdrop').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});

// ── State aksi ────────────────────────────────
let _resetSiswaId = 0;
let _resetSiswaName = '';
let _pendingAction = '';

// ── Buka modal reset ──────────────────────────
function bukaReset(id, nama) {
  _resetSiswaId = id;
  _resetSiswaName = nama;
  document.getElementById('resetNama').textContent = nama;
  tutupModal('modalKonfirmasi');
  bukaModal('modalReset');
}

// ── Buka modal hapus ──────────────────────────
function bukaHapus(id, nama) {
  document.getElementById('hapusNama').textContent = nama;
  document.getElementById('hapusSiswaId').value = id;
  bukaModal('modalHapus');
}

// ── Konfirmasi reset ──────────────────────────
const resetMeta = {
  reset_xp:       { icon:'⚡', judul:'Reset XP & Level', pesan: name => `Reset XP, level, dan streak <b class="confirm-name">${name}</b> ke nol? Data soal benar juga akan ikut direset.` },
  reset_progress: { icon:'📚', judul:'Reset Progress Topik', pesan: name => `Hapus seluruh progress topik dan riwayat kuis <b class="confirm-name">${name}</b>? Semua topik akan kembali ke belum dimulai.` },
  reset_badge:    { icon:'🏅', judul:'Reset Badge', pesan: name => `Hapus semua badge yang diraih <b class="confirm-name">${name}</b>? Badge bisa diraih kembali dengan bermain kuis.` },
  reset_all:      { icon:'💣', judul:'Reset Total', pesan: name => `<span style="color:var(--error)"><b>PERHATIAN!</b></span> Ini akan menghapus <b>semua data</b> siswa <b class="confirm-name">${name}</b> sekaligus — XP, level, progress, badge, riwayat kuis. Tindakan ini <b>tidak bisa dibatalkan</b>.` },
};

function konfirmasiReset(action) {
  _pendingAction = action;
  const meta = resetMeta[action];
  document.getElementById('konfirmasiIcon').textContent = meta.icon;
  document.getElementById('konfirmasiJudul').textContent = meta.judul;
  document.getElementById('konfirmasiPesan').innerHTML = meta.pesan(_resetSiswaName);
  tutupModal('modalReset');
  bukaModal('modalKonfirmasi');
}

function jalankanAksi() {
  document.getElementById('resetAction').value = _pendingAction;
  document.getElementById('resetSiswaId').value = _resetSiswaId;
  document.getElementById('formReset').submit();
}

// ── Buka modal detail ─────────────────────────
const badgeData = <?= json_encode($all_badges) ?>;

function bukaDetail(s) {
  const levelNames = {1:'Penjelajah Baru',2:'Petualang Muda',3:'Ilmuwan Cilik',4:'Peneliti Muda',5:'Profesor Kecil'};
  const avatars = ['🐘','🦁','🐯','🦊','🐧','🦋','🐬','🦜','🐸','🌟'];
  const ava = avatars[s.avatar] || '🐘';
  const lvl = Math.min(5, Math.max(1, parseInt(s.level)));

  // Fetch detail async
  fetch(`?detail=${s.id}`)
    .then(r => r.text())
    .then(() => {
      // Data sudah di server-side, tapi kita pakai AJAX fetch JSON untuk detail
    });

  // Untuk tampilan dasar, gunakan data yang sudah tersedia dari server query
  // Fetch JSON detail via AJAX endpoint
  fetch(`/SAINTARA/api/siswa-detail.php?id=${s.id}`)
    .then(r => r.json())
    .then(data => {
      renderDetail(s, data, ava, lvl, levelNames);
    })
    .catch(() => {
      // Fallback — tampilkan data dasar saja
      renderDetail(s, null, ava, lvl, levelNames);
    });

  // Tampilkan loading dulu
  document.getElementById('detailBody').innerHTML = `
    <div style="text-align:center;padding:40px;color:var(--muted)">
      <i class="bi bi-hourglass-split" style="font-size:2rem;animation:spin 1s linear infinite;display:inline-block"></i>
      <p style="margin-top:12px">Memuat data siswa…</p>
    </div>`;

  bukaModal('modalDetail');
}

function renderDetail(s, extra, ava, lvl, levelNames) {
  const progress_html = extra && extra.progress && extra.progress.length ?
    extra.progress.map(p => `
      <div class="progress-row">
        <span class="topic-icon"><i class="bi ${p.icon || 'bi-book-fill'}"></i></span>
        <span class="topic-name">Kelas ${p.kelas} — ${p.judul}</span>
        <span class="topic-score ${p.selesai ? 'done' : 'undone'}">${p.selesai ? `✅ ${p.skor}%` : 'Belum'}</span>
      </div>`).join('') :
    '<p style="font-size:.82rem;color:var(--muted)">Belum ada progress topik.</p>';

  const badge_html = extra && extra.badges && extra.badges.length ?
    extra.badges.map(b => {
      const bd = badgeData[b.badge_key] || {};
      return `<div class="badge-chip">${bd.icon || '🏅'} ${bd.nama || b.badge_key}</div>`;
    }).join('') :
    '<p style="font-size:.82rem;color:var(--muted)">Belum ada badge.</p>';

  const history_html = extra && extra.riwayat && extra.riwayat.length ?
    extra.riwayat.map(r => `
      <div class="history-row">
        <span class="h-score">${r.benar}/${r.benar+r.salah}</span>
        <span class="h-topic">${r.judul}</span>
        <span class="h-xp">+${r.xp_gained} XP</span>
        <span class="h-date">${r.played_at ? r.played_at.substring(0,10) : ''}</span>
      </div>`).join('') :
    '<p style="font-size:.82rem;color:var(--muted)">Belum ada riwayat kuis.</p>';

  document.getElementById('detailBody').innerHTML = `
    <div class="detail-hero">
      <div class="detail-avatar">${ava}</div>
      <div class="detail-name">${s.nama}</div>
      <div class="detail-meta">Kelas ${s.kelas} &middot; ${levelNames[lvl]} &middot; Level ${lvl}</div>
    </div>

    <div class="detail-stats">
      <div class="detail-stat">
        <div class="detail-stat-val">${Number(s.xp).toLocaleString('id')}</div>
        <div class="detail-stat-lbl">Total XP</div>
      </div>
      <div class="detail-stat">
        <div class="detail-stat-val">${s.streak}</div>
        <div class="detail-stat-lbl">Streak</div>
      </div>
      <div class="detail-stat">
        <div class="detail-stat-val">${s.total_benar}</div>
        <div class="detail-stat-lbl">Soal Benar</div>
      </div>
    </div>

    <div class="detail-section-title">Progress Topik</div>
    <div class="progress-list" style="margin-bottom:16px">${progress_html}</div>

    <div class="detail-section-title">Badge Diraih</div>
    <div class="badge-list-mini" style="margin-bottom:16px">${badge_html}</div>

    <div class="detail-section-title">Riwayat Kuis Terakhir</div>
    <div class="history-list">${history_html}</div>
  `;
}

// ── Search debounce ───────────────────────────
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    document.getElementById('filterForm').submit();
  }, 500);
});

// ── CSS spin ──────────────────────────────────
const style = document.createElement('style');
style.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
document.head.appendChild(style);
</script>

</body>
</html>