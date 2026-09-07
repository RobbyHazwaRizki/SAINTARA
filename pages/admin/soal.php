<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// pages/admin/soal.php — Manajemen Bank Soal
// CRUD: Tambah, Edit, Hapus soal per topik & kelas
// ============================================================
require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

requireGuru();
$pdo  = getDB();
$guru = getGuruSession();

// ── AKSI POST ────────────────────────────────────────────────
$flash = ''; $flash_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = bersihkan($_POST['action'] ?? '');

    // Shared field sanitizer
    $getData = fn($k) => bersihkan($_POST[$k] ?? '');

    switch ($act) {

        case 'tambah':
        case 'edit':
            $topik_id   = (int)($_POST['topik_id'] ?? 0);
            $kelas      = (int)($_POST['kelas'] ?? 0);
            $pertanyaan = trim($_POST['pertanyaan'] ?? '');
            $opsi_a     = trim($_POST['opsi_a'] ?? '');
            $opsi_b     = trim($_POST['opsi_b'] ?? '');
            $opsi_c     = trim($_POST['opsi_c'] ?? '');
            $opsi_d     = trim($_POST['opsi_d'] ?? '');
            $jawaban    = bersihkan($_POST['jawaban'] ?? '');
            $xp_reward  = (int)($_POST['xp_reward'] ?? getXpReward($kelas));

            if (!$topik_id || !$kelas || !$pertanyaan || !$opsi_a || !$opsi_b || !$opsi_c || !$opsi_d || !in_array($jawaban, ['a','b','c','d'])) {
                $flash = 'Semua field wajib diisi dengan benar.';
                $flash_type = 'error';
                break;
            }

            if ($act === 'tambah') {
                $pdo->prepare("INSERT INTO soal (topik_id, kelas, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, xp_reward) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$topik_id, $kelas, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban, $xp_reward]);
                $flash = 'Soal berhasil ditambahkan!';
            } else {
                $soal_id = (int)($_POST['soal_id'] ?? 0);
                $pdo->prepare("UPDATE soal SET topik_id=?, kelas=?, pertanyaan=?, opsi_a=?, opsi_b=?, opsi_c=?, opsi_d=?, jawaban=?, xp_reward=? WHERE id=?")
                    ->execute([$topik_id, $kelas, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban, $xp_reward, $soal_id]);
                $flash = 'Soal berhasil diperbarui!';
            }
            $flash_type = 'success';
            break;

        case 'hapus':
            $soal_id = (int)($_POST['soal_id'] ?? 0);
            if ($soal_id) {
                $pdo->prepare("DELETE FROM soal WHERE id=?")->execute([$soal_id]);
                $flash = 'Soal berhasil dihapus.';
                $flash_type = 'success';
            }
            break;

        case 'hapus_bulk':
            $ids = $_POST['ids'] ?? [];
            $ids = array_filter(array_map('intval', $ids));
            if ($ids) {
                $pl = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM soal WHERE id IN ($pl)")->execute($ids);
                $flash = count($ids) . ' soal berhasil dihapus.';
                $flash_type = 'success';
            }
            break;
    }

    // Redirect agar tidak re-POST
    $qs = http_build_query(array_filter([
        'kelas'    => $_POST['filter_kelas'] ?? '',
        'topik_id' => $_POST['filter_topik'] ?? '',
        'q'        => $_POST['filter_q']     ?? '',
    ]));
    header('Location: /SAINTARA/pages/admin/soal.php?' . $qs .
           ($flash ? '&flash='.urlencode($flash).'&ft='.$flash_type : ''));
    exit;
}

// Ambil flash dari redirect
if (isset($_GET['flash'])) {
    $flash      = bersihkan($_GET['flash']);
    $flash_type = bersihkan($_GET['ft'] ?? 'success');
}

// ── FILTER ───────────────────────────────────────────────────
$filter_kelas    = (int)($_GET['kelas'] ?? 0);
$filter_topik_id = (int)($_GET['topik_id'] ?? 0);
$search          = bersihkan($_GET['q'] ?? '');
$page            = max(1, (int)($_GET['page'] ?? 1));
$per_page        = 15;

// ── AMBIL SEMUA TOPIK (untuk dropdown) ──────────────────────
$semua_topik = $pdo->query("SELECT id, kelas, judul, icon FROM topik ORDER BY kelas, urutan")->fetchAll();

// Kelompokkan topik per kelas
$topik_per_kelas = [];
foreach ($semua_topik as $t) {
    $topik_per_kelas[$t['kelas']][] = $t;
}

// Topik sesuai filter kelas (untuk dropdown form)
$topik_filter = $filter_kelas ? ($topik_per_kelas[$filter_kelas] ?? []) : $semua_topik;

// ── QUERY SOAL ───────────────────────────────────────────────
$where = []; $params = [];

if ($filter_kelas)    { $where[] = 's.kelas = ?';    $params[] = $filter_kelas; }
if ($filter_topik_id) { $where[] = 's.topik_id = ?'; $params[] = $filter_topik_id; }
if ($search)          { $where[] = 's.pertanyaan LIKE ?'; $params[] = '%'.$search.'%'; }

$where_sql  = $where ? 'WHERE '.implode(' AND ', $where) : '';
$total_soal = (int)$pdo->prepare("SELECT COUNT(*) FROM soal s $where_sql")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM soal s $where_sql")->execute($params) : 0;

// Fix: pakai cara yang benar
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM soal s $where_sql");
$count_stmt->execute($params);
$total_soal  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_soal / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$list_stmt = $pdo->prepare("
    SELECT s.*, t.judul AS judul_topik, t.icon AS icon_topik
    FROM soal s
    LEFT JOIN topik t ON t.id = s.topik_id
    $where_sql
    ORDER BY s.kelas, s.topik_id, s.id
    LIMIT ? OFFSET ?
");
$list_stmt->execute(array_merge($params, [$per_page, $offset]));
$list_soal = $list_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── STATS ────────────────────────────────────────────────────
$stats = [];
for ($k = 1; $k <= 6; $k++) {
    $stats[$k] = (int)$pdo->query("SELECT COUNT(*) FROM soal WHERE kelas=$k")->fetchColumn();
}
$total_all = array_sum($stats);

// Edit mode (prefill form)
$edit_soal = null;
if (isset($_GET['edit'])) {
    $es = $pdo->prepare("SELECT * FROM soal WHERE id=?");
    $es->execute([(int)$_GET['edit']]);
    $edit_soal = $es->fetch(PDO::FETCH_ASSOC);
}

$jawaban_labels = ['a'=>'A','b'=>'B','c'=>'C','d'=>'D'];
$kelas_colors   = ['','#4DA8DA','#80D8C3','#FFD66B','#a78bfa','#f97316','#E05555'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bank Soal — Saintara Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root {
  --primary:#4DA8DA; --secondary:#80D8C3; --accent:#FFD66B;
  --bg:#F0F8FF; --error:#E05555; --text:#1A2E3A; --muted:#6B8899;
  --white:#FFFFFF; --navy:#1A2E3A; --sidebar-w:240px;
  --radius:14px; --shadow:0 4px 20px rgba(77,168,218,.12);
  --shadow-lg:0 8px 32px rgba(77,168,218,.18);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Nunito',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}
h1,h2,h3,h4{font-family:'Fredoka',sans-serif}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);background:var(--navy);color:#fff;position:fixed;top:0;left:0;height:100vh;display:flex;flex-direction:column;z-index:100;overflow-y:auto}
.sidebar-brand{padding:24px 20px 20px;border-bottom:1px solid rgba(255,255,255,.08)}
.brand-logo{font-family:'Fredoka',sans-serif;font-size:1.4rem;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:8px}
.brand-logo span{color:var(--accent)}
.sidebar-brand small{color:var(--muted);font-size:.75rem;display:block;margin-top:4px}
.sidebar-nav{padding:12px 0;flex:1}
.nav-label{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:1.2px;color:var(--muted);padding:16px 20px 6px}
.nav-link{display:flex;align-items:center;gap:10px;padding:11px 20px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.9rem;font-weight:600;transition:all .2s;border-left:3px solid transparent}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.05)}
.nav-link.active{color:#fff;background:rgba(77,168,218,.15);border-left-color:var(--primary)}
.nav-link i{font-size:1.1rem;width:20px;text-align:center}
.sidebar-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,.08)}
.guru-info{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.guru-av{width:36px;height:36px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem}
.guru-name{font-size:.85rem;font-weight:700;color:#fff}
.guru-role{font-size:.7rem;color:var(--muted)}
.btn-logout{display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;background:rgba(224,85,85,.15);color:#ff8080;border:1px solid rgba(224,85,85,.25);border-radius:8px;font-size:.83rem;font-weight:700;cursor:pointer;text-decoration:none;transition:all .2s}
.btn-logout:hover{background:rgba(224,85,85,.3)}

/* MAIN */
.main-content{margin-left:var(--sidebar-w);flex:1;padding:28px 32px;min-height:100vh}

/* LAYOUT 2 KOLOM */
.two-col{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}

/* TOPBAR */
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.page-title{font-size:1.8rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:10px}
.page-title i{color:var(--primary)}
.page-subtitle{font-size:.88rem;color:var(--muted);margin-top:2px}

/* TOAST */
.toast-wrap{position:fixed;top:24px;right:24px;z-index:9999}
.toast{background:#fff;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;box-shadow:0 8px 32px rgba(0,0,0,.12);border-left:4px solid var(--secondary);font-weight:700;font-size:.88rem;animation:toastIn .3s ease}
.toast.error{border-left-color:var(--error)}
@keyframes toastIn{from{transform:translateX(40px);opacity:0}to{transform:none;opacity:1}}

/* STAT BAR */
.stat-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.stat-chip{background:#fff;border-radius:10px;padding:10px 16px;display:flex;align-items:center;gap:8px;box-shadow:var(--shadow);font-size:.85rem;font-weight:700}
.stat-chip .dot{width:10px;height:10px;border-radius:50%}
.stat-chip .num{font-family:'Fredoka',sans-serif;font-size:1.1rem;font-weight:700}

/* CARD */
.card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.card-head{padding:16px 20px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.card-head h3{font-size:1rem;display:flex;align-items:center;gap:8px}
.card-head h3 i{color:var(--primary)}
.card-body{padding:20px}

/* TOOLBAR */
.toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 20px;border-bottom:1px solid #edf2f7;background:#f7fafd}
.search-box{display:flex;align-items:center;gap:8px;background:#fff;border:1.5px solid #e2edf5;border-radius:10px;padding:8px 13px;flex:1;min-width:180px;transition:border-color .2s}
.search-box:focus-within{border-color:var(--primary)}
.search-box i{color:var(--muted)}
.search-box input{border:none;background:none;outline:none;font-family:'Nunito',sans-serif;font-size:.88rem;color:var(--text);width:100%}
.filter-sel{padding:8px 13px;border:1.5px solid #e2edf5;border-radius:10px;font-family:'Nunito',sans-serif;font-size:.85rem;color:var(--text);background:#f7fafd;outline:none;cursor:pointer;transition:border-color .2s}
.filter-sel:focus{border-color:var(--primary)}

/* TABLE */
table{width:100%;border-collapse:collapse}
thead tr{background:#f7fafd}
thead th{padding:10px 14px;text-align:left;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);border-bottom:1px solid #edf2f7;white-space:nowrap}
tbody tr{border-bottom:1px solid #f0f4f8;transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:#f7fafd}
tbody tr.selected-row{background:rgba(77,168,218,.06)}
tbody td{padding:11px 14px;font-size:.85rem;vertical-align:middle}

.question-text{font-weight:700;max-width:320px;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.opsi-preview{font-size:.72rem;color:var(--muted);margin-top:3px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.kelas-badge{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;font-weight:800;font-size:.78rem;color:#fff}

.jawaban-badge{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;background:rgba(128,216,195,.15);color:#00897b;font-weight:800;font-size:.82rem}

.xp-tag{font-size:.72rem;font-weight:800;color:#92640a;background:rgba(255,214,107,.2);padding:2px 7px;border-radius:20px}

/* ACTION BTNS */
.act-btns{display:flex;gap:5px}
.btn-icon{width:30px;height:30px;border-radius:7px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .2s}
.btn-icon.edit-btn{background:#e3f2fd;color:#1565c0}
.btn-icon.edit-btn:hover{background:#1565c0;color:#fff}
.btn-icon.del-btn{background:#fdecea;color:var(--error)}
.btn-icon.del-btn:hover{background:var(--error);color:#fff}

/* PAGINATION */
.pagination{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-top:1px solid #edf2f7;flex-wrap:wrap;gap:8px}
.page-info{font-size:.8rem;color:var(--muted);font-weight:600}
.page-btns{display:flex;gap:5px}
.page-btn{width:32px;height:32px;border-radius:7px;border:1.5px solid #e2edf5;background:#fff;color:var(--text);font-size:.8rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all .2s}
.page-btn:hover{border-color:var(--primary);color:var(--primary)}
.page-btn.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.page-btn.disabled{opacity:.35;pointer-events:none}

/* FORM CARD */
.form-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);position:sticky;top:24px}
.form-card-head{padding:16px 20px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between}
.form-card-head h3{font-size:1rem;display:flex;align-items:center;gap:8px}
.form-edit-mode{border-top:3px solid var(--accent)}
.form-body{padding:18px 20px}

.form-group{margin-bottom:14px}
.form-label{display:block;font-size:.73rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:5px}
.form-input,.form-select,.form-textarea{width:100%;padding:9px 12px;border:1.5px solid #e2edf5;border-radius:9px;font-family:'Nunito',sans-serif;font-size:.88rem;color:var(--text);background:var(--bg);outline:none;transition:border-color .2s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--primary);background:#fff}
.form-textarea{resize:vertical;min-height:80px;line-height:1.5}
.form-hint{font-size:.7rem;color:var(--muted);margin-top:4px}

/* OPSI RADIO GROUP */
.opsi-group{display:flex;flex-direction:column;gap:6px}
.opsi-row{display:flex;align-items:center;gap:8px}
.opsi-label{width:22px;height:22px;border-radius:6px;background:var(--bg);border:1.5px solid #e2edf5;display:flex;align-items:center;justify-content:center;font-family:'Fredoka',sans-serif;font-size:.85rem;font-weight:700;color:var(--muted);flex-shrink:0;cursor:pointer;transition:all .15s}
.opsi-row:has(input[type=radio]:checked) .opsi-label{background:var(--secondary);border-color:var(--secondary);color:#fff}
.opsi-row:has(input[type=radio]:checked) .form-input{border-color:var(--secondary);background:rgba(128,216,195,.06)}
.jawaban-radio{display:none}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:9px;font-family:'Nunito',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;border:none;transition:all .2s;text-decoration:none}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:#3a90c2;transform:translateY(-1px)}
.btn-accent{background:var(--accent);color:var(--navy)}
.btn-accent:hover{background:#e8b84b}
.btn-ghost{background:var(--bg);color:var(--text);border:1.5px solid #e2edf5}
.btn-ghost:hover{border-color:var(--primary);color:var(--primary)}
.btn-danger{background:var(--error);color:#fff}
.btn-full{width:100%;justify-content:center}
.btn-sm{padding:6px 12px;font-size:.8rem;border-radius:7px}

/* EMPTY */
.empty-state{text-align:center;padding:48px 24px;color:var(--muted)}
.empty-state .ei{font-size:3rem;opacity:.3;display:block;margin-bottom:14px}

/* MODAL */
.modal-backdrop{position:fixed;inset:0;background:rgba(26,46,58,.5);backdrop-filter:blur(4px);z-index:500;display:none;align-items:center;justify-content:center;padding:20px}
.modal-backdrop.open{display:flex}
.modal{background:#fff;border-radius:20px;max-width:440px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.18);animation:modalIn .25s ease;overflow:hidden}
@keyframes modalIn{from{transform:scale(.94) translateY(16px);opacity:0}to{transform:none;opacity:1}}
.modal-head{padding:18px 22px;border-bottom:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between}
.modal-head h3{font-size:1rem;display:flex;align-items:center;gap:8px}
.modal-body{padding:22px;text-align:center}
.modal-foot{padding:14px 22px;border-top:1px solid #edf2f7;display:flex;justify-content:flex-end;gap:8px}
.btn-close-modal{width:30px;height:30px;border-radius:7px;border:none;background:#f0f4f8;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;transition:all .2s}
.btn-close-modal:hover{background:var(--error);color:#fff}

/* BULK DELETE BAR */
.bulk-bar{display:none;position:sticky;top:0;z-index:50;background:rgba(26,46,58,.95);color:#fff;padding:10px 20px;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.bulk-bar.visible{display:flex}

::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-thumb{background:#d0e4ef;border-radius:10px}

@media(max-width:1100px){.two-col{grid-template-columns:1fr}}
@media(max-width:900px){.main-content{margin-left:0;padding:20px 16px}}
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
    <a href="/SAINTARA/pages/admin/siswa.php"     class="nav-link"><i class="bi bi-people-fill"></i> Data Siswa</a>
    <a href="/SAINTARA/pages/admin/soal.php"      class="nav-link active"><i class="bi bi-patch-question-fill"></i> Bank Soal</a>
    <a href="/SAINTARA/pages/admin/sesi.php"      class="nav-link"><i class="bi bi-trophy-fill"></i> Sesi Kompetisi</a>
  </nav>
  <div class="sidebar-footer">
    <div class="guru-info">
      <div class="guru-av"><i class="bi bi-person-fill"></i></div>
      <div>
        <div class="guru-name"><?= htmlspecialchars($guru['nama']) ?></div>
        <div class="guru-role">Administrator</div>
      </div>
    </div>
    <a href="/SAINTARA/api/logout-guru.php" class="btn-logout" onclick="return confirm('Yakin logout?')">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</aside>

<!-- TOAST -->
<?php if ($flash): ?>
<div class="toast-wrap" id="toastEl">
  <div class="toast <?= $flash_type ?>">
    <?= $flash_type === 'success' ? '✅' : '❌' ?>
    <?= htmlspecialchars($flash) ?>
  </div>
</div>
<?php endif; ?>

<!-- MAIN -->
<main class="main-content">

  <!-- TOPBAR -->
  <div class="topbar">
    <div>
      <h1 class="page-title"><i class="bi bi-patch-question-fill"></i> Bank Soal</h1>
      <p class="page-subtitle">Kelola soal kuis untuk semua kelas dan topik</p>
    </div>
  </div>

  <!-- STAT BAR PER KELAS -->
  <div class="stat-bar">
    <div class="stat-chip">
      <span class="num"><?= $total_all ?></span>
      <span style="color:var(--muted)">Total Soal</span>
    </div>
    <?php
    $kc = ['','#4DA8DA','#80D8C3','#FFD66B','#a78bfa','#f97316','#E05555'];
    for ($k = 1; $k <= 6; $k++):
    ?>
    <div class="stat-chip">
      <span class="dot" style="background:<?= $kc[$k] ?>"></span>
      <span style="color:var(--muted);font-size:.75rem">Kelas <?= $k ?></span>
      <span class="num" style="color:<?= $kc[$k] ?>"><?= $stats[$k] ?></span>
    </div>
    <?php endfor; ?>
  </div>

  <!-- 2 KOLOM: List + Form -->
  <div class="two-col">

    <!-- KIRI: LIST SOAL -->
    <div>

      <!-- BULK DELETE BAR -->
      <div class="bulk-bar" id="bulkBar">
        <span id="bulkCount">0 soal dipilih</span>
        <form method="POST" id="bulkForm">
          <input type="hidden" name="action" value="hapus_bulk">
          <input type="hidden" name="filter_kelas" value="<?= $filter_kelas ?>">
          <input type="hidden" name="filter_topik" value="<?= $filter_topik_id ?>">
          <input type="hidden" name="filter_q" value="<?= htmlspecialchars($search) ?>">
          <div id="bulkInputs"></div>
          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus soal yang dipilih?')">
            <i class="bi bi-trash-fill"></i> Hapus Dipilih
          </button>
        </form>
        <button class="btn btn-ghost btn-sm" onclick="clearSelect()">Batal</button>
      </div>

      <div class="card">
        <!-- TOOLBAR FILTER -->
        <form method="GET" id="filterForm">
          <div class="toolbar">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" name="q" placeholder="Cari pertanyaan…" value="<?= htmlspecialchars($search) ?>" id="searchInput">
            </div>

            <select name="kelas" class="filter-sel" id="kelasFilter" onchange="updateTopikFilter()">
              <option value="">Semua Kelas</option>
              <?php for ($k=1;$k<=6;$k++): ?>
              <option value="<?= $k ?>" <?= $filter_kelas===$k?'selected':'' ?>>Kelas <?= $k ?></option>
              <?php endfor; ?>
            </select>

            <select name="topik_id" class="filter-sel" id="topikFilter">
              <option value="">Semua Topik</option>
              <?php foreach ($topik_filter as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $filter_topik_id===$t['id']?'selected':'' ?>>
                Kelas <?= $t['kelas'] ?> — <?= htmlspecialchars($t['judul']) ?>
              </option>
              <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel-fill"></i> Filter</button>
            <a href="/SAINTARA/pages/admin/soal.php" class="btn btn-ghost btn-sm"><i class="bi bi-x-lg"></i></a>
          </div>
        </form>

        <!-- TABEL -->
        <div class="card-head" style="border-top:none">
          <h3><i class="bi bi-table"></i> Daftar Soal</h3>
          <span style="font-size:.78rem;color:var(--muted);font-weight:700"><?= number_format($total_soal) ?> soal</span>
        </div>

        <?php if (empty($list_soal)): ?>
        <div class="empty-state">
          <span class="ei">🔍</span>
          <div style="font-weight:700;font-size:1rem;margin-bottom:6px">Tidak ada soal ditemukan</div>
          <div>Coba ubah filter atau tambah soal baru lewat form di samping.</div>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table>
          <thead><tr>
            <th><input type="checkbox" id="checkAll" title="Pilih semua" onchange="toggleAll(this)"></th>
            <th>#</th>
            <th>Kls</th>
            <th>Topik</th>
            <th>Pertanyaan</th>
            <th>Jwb</th>
            <th>XP</th>
            <th>Aksi</th>
          </tr></thead>
          <tbody id="soalTbody">
          <?php foreach ($list_soal as $i => $s): ?>
          <tr id="row-<?= $s['id'] ?>">
            <td>
              <input type="checkbox" class="row-check" value="<?= $s['id'] ?>" onchange="updateBulk()">
            </td>
            <td style="color:var(--muted);font-weight:700;font-size:.75rem"><?= ($page-1)*$per_page+$i+1 ?></td>
            <td>
              <span class="kelas-badge" style="background:<?= $kc[$s['kelas']] ?>">
                <?= $s['kelas'] ?>
              </span>
            </td>
            <td style="font-size:.78rem;color:var(--muted);font-weight:600;max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= htmlspecialchars($s['judul_topik'] ?? '—') ?>
            </td>
            <td>
              <div class="question-text"><?= htmlspecialchars($s['pertanyaan']) ?></div>
              <div class="opsi-preview">
                A: <?= htmlspecialchars($s['opsi_a']) ?> &nbsp;·&nbsp;
                B: <?= htmlspecialchars($s['opsi_b']) ?> &nbsp;·&nbsp;
                C: <?= htmlspecialchars($s['opsi_c']) ?> &nbsp;·&nbsp;
                D: <?= htmlspecialchars($s['opsi_d']) ?>
              </div>
            </td>
            <td><span class="jawaban-badge"><?= strtoupper($s['jawaban']) ?></span></td>
            <td><span class="xp-tag">+<?= $s['xp_reward'] ?></span></td>
            <td>
              <div class="act-btns">
                <a href="?edit=<?= $s['id'] ?>&kelas=<?= $filter_kelas ?>&topik_id=<?= $filter_topik_id ?>&q=<?= urlencode($search) ?>"
                   class="btn-icon edit-btn" title="Edit soal">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <button class="btn-icon del-btn" title="Hapus soal"
                  onclick="bukaHapus(<?= $s['id'] ?>, '<?= htmlspecialchars(substr($s['pertanyaan'],0,40), ENT_QUOTES) ?>...')">
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
          <div class="page-info">Hal <?= $page ?> / <?= $total_pages ?></div>
          <div class="page-btns">
            <?php
            $qs = http_build_query(array_filter(['kelas'=>$filter_kelas,'topik_id'=>$filter_topik_id,'q'=>$search]));
            ?>
            <a href="?page=<?= max(1,$page-1) ?>&<?= $qs ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
            <?php
            $start = max(1,$page-2); $end = min($total_pages,$start+4);
            for ($p=$start;$p<=$end;$p++):
            ?>
            <a href="?page=<?= $p ?>&<?= $qs ?>" class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="?page=<?= min($total_pages,$page+1) ?>&<?= $qs ?>" class="page-btn <?= $page>=$total_pages?'disabled':'' ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- KANAN: FORM TAMBAH / EDIT -->
    <div>
      <div class="form-card <?= $edit_soal ? 'form-edit-mode' : '' ?>">
        <div class="form-card-head">
          <h3>
            <?php if ($edit_soal): ?>
            <i class="bi bi-pencil-fill" style="color:var(--accent)"></i> Edit Soal #<?= $edit_soal['id'] ?>
            <?php else: ?>
            <i class="bi bi-plus-circle-fill" style="color:var(--primary)"></i> Tambah Soal Baru
            <?php endif; ?>
          </h3>
          <?php if ($edit_soal): ?>
          <a href="/SAINTARA/pages/admin/soal.php?kelas=<?= $filter_kelas ?>&topik_id=<?= $filter_topik_id ?>"
             class="btn btn-ghost btn-sm"><i class="bi bi-x-lg"></i> Batal</a>
          <?php endif; ?>
        </div>

        <div class="form-body">
          <form method="POST" id="soalForm">
            <input type="hidden" name="action" value="<?= $edit_soal ? 'edit' : 'tambah' ?>">
            <?php if ($edit_soal): ?>
            <input type="hidden" name="soal_id" value="<?= $edit_soal['id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="filter_kelas" value="<?= $filter_kelas ?>">
            <input type="hidden" name="filter_topik" value="<?= $filter_topik_id ?>">
            <input type="hidden" name="filter_q" value="<?= htmlspecialchars($search) ?>">

            <!-- KELAS -->
            <div class="form-group">
              <label class="form-label">Kelas <span style="color:var(--error)">*</span></label>
              <select name="kelas" class="form-select" id="formKelas" onchange="loadTopikByKelas(this.value)" required>
                <option value="">— Pilih Kelas —</option>
                <?php for ($k=1;$k<=6;$k++): ?>
                <option value="<?= $k ?>" <?= ($edit_soal?$edit_soal['kelas']:$filter_kelas)==$k?'selected':'' ?>>
                  Kelas <?= $k ?></option>
                <?php endfor; ?>
              </select>
            </div>

            <!-- TOPIK -->
            <div class="form-group">
              <label class="form-label">Topik <span style="color:var(--error)">*</span></label>
              <select name="topik_id" class="form-select" id="formTopik" required>
                <option value="">— Pilih kelas dulu —</option>
                <?php
                $kelasForm = $edit_soal ? $edit_soal['kelas'] : $filter_kelas;
                if ($kelasForm && isset($topik_per_kelas[$kelasForm])):
                  foreach ($topik_per_kelas[$kelasForm] as $t):
                ?>
                <option value="<?= $t['id'] ?>" <?= ($edit_soal&&$edit_soal['topik_id']==$t['id'])||($filter_topik_id==$t['id'])?'selected':'' ?>>
                  <?= htmlspecialchars($t['judul']) ?>
                </option>
                <?php endforeach; endif; ?>
              </select>
            </div>

            <!-- PERTANYAAN -->
            <div class="form-group">
              <label class="form-label">Pertanyaan <span style="color:var(--error)">*</span></label>
              <textarea name="pertanyaan" class="form-textarea" placeholder="Tulis pertanyaan di sini…" required><?= htmlspecialchars($edit_soal['pertanyaan'] ?? '') ?></textarea>
            </div>

            <!-- OPSI A–D dengan radio jawaban -->
            <div class="form-group">
              <label class="form-label">Opsi Jawaban — <span style="color:var(--secondary)">klik radio = jawaban benar</span></label>
              <div class="opsi-group">
                <?php foreach (['a','b','c','d'] as $huruf): ?>
                <div class="opsi-row">
                  <input type="radio" name="jawaban" value="<?= $huruf ?>" class="jawaban-radio"
                    id="jw<?= $huruf ?>"
                    <?= ($edit_soal && $edit_soal['jawaban']===$huruf) ? 'checked' : '' ?>
                    required>
                  <label class="opsi-label" for="jw<?= $huruf ?>"><?= strtoupper($huruf) ?></label>
                  <input type="text" name="opsi_<?= $huruf ?>" class="form-input"
                    placeholder="Opsi <?= strtoupper($huruf) ?>…"
                    value="<?= htmlspecialchars($edit_soal['opsi_'.$huruf] ?? '') ?>"
                    required>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="form-hint">Klik huruf A/B/C/D untuk menandai jawaban yang benar.</div>
            </div>

            <!-- XP REWARD -->
            <div class="form-group">
              <label class="form-label">XP Reward</label>
              <select name="xp_reward" class="form-select" id="formXP">
                <option value="20" <?= ($edit_soal&&$edit_soal['xp_reward']==20)?'selected':'' ?>>20 XP (Kelas 1–2)</option>
                <option value="15" <?= ($edit_soal&&$edit_soal['xp_reward']==15)?'selected':'' ?>>15 XP (Kelas 3–4)</option>
                <option value="10" <?= ($edit_soal&&$edit_soal['xp_reward']==10)?'selected':'' ?>>10 XP (Kelas 5–6)</option>
              </select>
              <div class="form-hint">Otomatis menyesuaikan saat kelas dipilih.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
              <?php if ($edit_soal): ?>
              <i class="bi bi-check-circle-fill"></i> Simpan Perubahan
              <?php else: ?>
              <i class="bi bi-plus-circle-fill"></i> Tambah Soal
              <?php endif; ?>
            </button>

          </form>
        </div>

        <!-- TIPS -->
        <div style="padding:12px 20px;border-top:1px solid #edf2f7;font-size:.75rem;color:var(--muted);line-height:1.6">
          <b style="color:var(--text)">💡 Tips:</b>
          Buat soal yang jelas dan sesuai KD Kurikulum Merdeka.
          Kelas 1–2 = 5 soal, Kelas 3–4 = 10 soal, Kelas 5–6 = 15 soal per topik.
        </div>
      </div>
    </div>

  </div><!-- /.two-col -->
</main>

<!-- MODAL HAPUS -->
<div class="modal-backdrop" id="modalHapus">
  <div class="modal">
    <div class="modal-head">
      <h3><i class="bi bi-trash-fill" style="color:var(--error)"></i> Hapus Soal</h3>
      <button class="btn-close-modal" onclick="tutupModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div style="font-size:2.5rem;margin-bottom:12px">🗑️</div>
      <p style="font-size:.9rem;color:var(--text);line-height:1.6">
        Hapus soal: <strong id="hapusPertanyaan" style="color:var(--primary)"></strong>?<br>
        <span style="font-size:.8rem;color:var(--muted)">Tindakan ini tidak bisa dibatalkan.</span>
      </p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost btn-sm" onclick="tutupModal()">Batal</button>
      <form method="POST" style="display:inline" id="formHapus">
        <input type="hidden" name="action" value="hapus">
        <input type="hidden" name="soal_id" id="hapusSoalId">
        <input type="hidden" name="filter_kelas" value="<?= $filter_kelas ?>">
        <input type="hidden" name="filter_topik" value="<?= $filter_topik_id ?>">
        <input type="hidden" name="filter_q" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-danger btn-sm">
          <i class="bi bi-trash-fill"></i> Hapus
        </button>
      </form>
    </div>
  </div>
</div>

<!-- DATA TOPIK UNTUK JS -->
<script>
const TOPIK_DATA = <?= json_encode($topik_per_kelas, JSON_UNESCAPED_UNICODE) ?>;
const XP_MAP = {1:20,2:20,3:15,4:15,5:10,6:10};

// Toast auto-hide
setTimeout(() => {
  const t = document.getElementById('toastEl');
  if (t) { t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(()=>t.remove(),400); }
}, 3500);

// Modal hapus
function bukaHapus(id, pertanyaan) {
  document.getElementById('hapusSoalId').value = id;
  document.getElementById('hapusPertanyaan').textContent = pertanyaan;
  document.getElementById('modalHapus').classList.add('open');
}
function tutupModal() {
  document.getElementById('modalHapus').classList.remove('open');
}
document.getElementById('modalHapus').addEventListener('click', e => {
  if (e.target === e.currentTarget) tutupModal();
});

// Klik opsi label → pilih radio
document.querySelectorAll('.opsi-label').forEach(lbl => {
  lbl.addEventListener('click', () => {
    const radio = lbl.previousElementSibling;
    if (radio) radio.checked = true;
  });
});

// Load topik dropdown berdasarkan kelas (form)
function loadTopikByKelas(kelas) {
  const sel = document.getElementById('formTopik');
  const xpSel = document.getElementById('formXP');
  sel.innerHTML = '<option value="">— Pilih Topik —</option>';
  const k = parseInt(kelas);
  if (TOPIK_DATA[k]) {
    TOPIK_DATA[k].forEach(t => {
      sel.innerHTML += `<option value="${t.id}">${t.judul}</option>`;
    });
  }
  // Auto-set XP
  if (xpSel && XP_MAP[k]) {
    const xp = XP_MAP[k];
    Array.from(xpSel.options).forEach(o => { o.selected = parseInt(o.value) === xp; });
  }
}

// Update topik filter saat kelas filter berubah
function updateTopikFilter() {
  const kelas = document.getElementById('kelasFilter').value;
  const sel = document.getElementById('topikFilter');
  sel.innerHTML = '<option value="">Semua Topik</option>';
  const k = parseInt(kelas);
  if (TOPIK_DATA[k]) {
    TOPIK_DATA[k].forEach(t => {
      sel.innerHTML += `<option value="${t.id}">Kelas ${t.kelas} — ${t.judul}</option>`;
    });
  }
  document.getElementById('filterForm').submit();
}

// Search debounce
let st;
document.getElementById('searchInput')?.addEventListener('input', () => {
  clearTimeout(st);
  st = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});

// Bulk select
function toggleAll(cb) {
  document.querySelectorAll('.row-check').forEach(c => {
    c.checked = cb.checked;
    c.closest('tr').classList.toggle('selected-row', cb.checked);
  });
  updateBulk();
}

function updateBulk() {
  const checked = document.querySelectorAll('.row-check:checked');
  const bar = document.getElementById('bulkBar');
  document.getElementById('bulkCount').textContent = checked.length + ' soal dipilih';
  bar.classList.toggle('visible', checked.length > 0);

  const container = document.getElementById('bulkInputs');
  container.innerHTML = '';
  checked.forEach(c => {
    container.innerHTML += `<input type="hidden" name="ids[]" value="${c.value}">`;
  });
}

function clearSelect() {
  document.querySelectorAll('.row-check').forEach(c => {
    c.checked = false;
    c.closest('tr').classList.remove('selected-row');
  });
  document.getElementById('checkAll').checked = false;
  updateBulk();
}
</script>

</body>
</html>