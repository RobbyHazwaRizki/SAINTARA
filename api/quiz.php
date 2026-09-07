<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// api/quiz.php — Simpan hasil kuis + update XP/badge & Sesi
// ============================================================

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireSiswa(); 

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Method salah']); exit;
}

$pdo = getDB();

$siswaId   = (int)$_SESSION['siswa_id'];
$topikId   = (int)($_POST['topik_id']   ?? 0);
$sesiId    = (int)($_POST['sesi_id']    ?? 0); // KUNCI UTAMA: Menangkap ID Sesi dari Frontend
$kelas     = (int)($_POST['kelas']      ?? 1);
$durasi    = (int)($_POST['durasi']     ?? 0);
$fastCount = (int)($_POST['fast_count'] ?? 0);

$jawaban_siswa = $_POST['jawaban_siswa'] ?? [];

$benar = 0;
$salah = 0;
$xpGained = 0;
$xp_per_soal = 10; 

try {
    // 1. VALIDASI JAWABAN (Anti-Cheat Server Side)
    if (!empty($jawaban_siswa)) {
        $stmtSoal = $pdo->prepare("SELECT jawaban FROM soal WHERE id = ?");
        foreach ($jawaban_siswa as $id_soal => $jawaban) {
            $stmtSoal->execute([$id_soal]);
            $soal = $stmtSoal->fetch();
            if ($soal) {
                if (strtolower(trim($soal['jawaban'])) === strtolower(trim($jawaban))) {
                    $benar++;
                    $xpGained += $xp_per_soal; 
                } else {
                    $salah++;
                }
            }
        }
    }

    $total = $benar + $salah;

    // --- FALLBACK UNTUK KUIS HARIAN ---
    if ($total === 0 && isset($_POST['benar'])) {
        $benar = (int)$_POST['benar'];
        $salah = (int)$_POST['salah'];
        $total = $benar + $salah;
        
        if ($total > 30) { $total = 30; $benar = 30; $salah = 0; }
        $xpGained = $benar * $xp_per_soal;
    }

    // Jika tidak ada soal sama sekali, batalkan
    if ($total === 0) {
        echo json_encode(['status'=>'error', 'message'=>'Data kuis kosong']); exit;
    }

    // 2. SIMPAN RIWAYAT KUIS (Hanya 1 Kali)
    $topikInsert = $topikId > 0 ? $topikId : 0;
    $stmt = $pdo->prepare("INSERT INTO riwayat_kuis (siswa_id, topik_id, benar, salah, xp_gained, durasi_detik) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$siswaId, $topikInsert, $benar, $salah, $xpGained, $durasi]);

    // 3. UPDATE STATISTIK SISWA
    $stmt = $pdo->prepare("UPDATE siswa SET total_benar = total_benar + ?, total_soal = total_soal + ? WHERE id = ?");
    $stmt->execute([$benar, $total, $siswaId]);

    // 4. TAMBAH XP KE PROFIL UTAMA
    $xpResult = tambahXP($siswaId, $xpGained, $pdo);

    // =========================================================
    // 5. CATAT HASIL TRY OUT KE TABEL SESI (UNTUK LEADERBOARD)
    // =========================================================
    if ($sesiId > 0) {
        // Karena tabel xp_sesi di database Anda punya PRIMARY KEY (sesi_id, siswa_id), 
        // INSERT IGNORE memastikan siswa tidak bisa mengulang ujian!
        $stmtXpSesi = $pdo->prepare("INSERT IGNORE INTO xp_sesi (sesi_id, siswa_id, xp) VALUES (?, ?, ?)");
        $stmtXpSesi->execute([$sesiId, $siswaId, $xpGained]);
    }
    // =========================================================

    // 6. UPDATE STREAK DAN PROGRESS MATERI
    $streakBaru = tambahStreak($siswaId, $pdo);
    if ($topikId > 0) {
        $selesai = ($benar / max($total, 1)) >= 0.6 ? 1 : 0;
        $stmt = $pdo->prepare("
            INSERT INTO progress (siswa_id, topik_id, selesai, skor) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE selesai = GREATEST(selesai, VALUES(selesai)), skor = GREATEST(skor, VALUES(skor))
        ");
        $stmt->execute([$siswaId, $topikId, $selesai, $benar]);
    }

    // 7. SINKRONISASI SESSION & BADGES
    $_SESSION['siswa_xp']    = $xpResult['xp_baru'];
    $_SESSION['siswa_level'] = $xpResult['level_baru'];

    $kuisData = ['benar' => $benar, 'total_soal' => $total, 'durasi_detik' => $durasi, 'fast_count' => $fastCount];
    $badgesUnlocked = cekDanBerikanBadge($siswaId, $kuisData, $pdo);

    // Sesi untuk halaman hasil.php
    $_SESSION['flash_hasil_kuis'] = [
        'benar' => $benar,
        'salah' => $salah,
        'xp_didapat' => $xpGained
    ];

    echo json_encode([
        'status'          => 'ok',
        'xp_baru'         => $xpResult['xp_baru'],
        'level_baru'      => $xpResult['level_baru'],
        'level_naik'      => $xpResult['level_naik'],
        'level_name'      => $xpResult['level_name'],
        'streak'          => $streakBaru,
        'badges_unlocked' => $badgesUnlocked,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB error: '.$e->getMessage()]);
}