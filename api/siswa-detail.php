<?php
/**
 * SAINTARA — Ella's Lab
 * api/siswa-detail.php
 * Endpoint AJAX — return detail siswa: progress, badge, riwayat
 */

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireGuru();

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$pdo = getDB();

// Progress topik
$dp = $pdo->prepare("
    SELECT p.selesai, p.skor, t.judul, t.kelas, t.icon
    FROM progress p
    JOIN topik t ON t.id = p.topik_id
    WHERE p.siswa_id = ?
    ORDER BY t.kelas, t.urutan
");
$dp->execute([$id]);
$progress = $dp->fetchAll(PDO::FETCH_ASSOC);

// Badge
$db_stmt = $pdo->prepare("SELECT badge_key, unlocked_at FROM badge_siswa WHERE siswa_id=? ORDER BY unlocked_at DESC");
$db_stmt->execute([$id]);
$badges = $db_stmt->fetchAll(PDO::FETCH_ASSOC);

// Riwayat kuis
$dr = $pdo->prepare("
    SELECT r.benar, r.salah, r.xp_gained, r.durasi_detik, r.played_at, t.judul
    FROM riwayat_kuis r
    JOIN topik t ON t.id = r.topik_id
    WHERE r.siswa_id = ?
    ORDER BY r.played_at DESC
    LIMIT 10
");
$dr->execute([$id]);
$riwayat = $dr->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'progress' => $progress,
    'badges'   => $badges,
    'riwayat'  => $riwayat,
]);