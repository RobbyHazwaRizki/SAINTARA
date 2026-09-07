<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// api/auth.php — Cek Status Session (AJAX)
// GET → return JSON status login siswa/guru
// ============================================================

require_once '../includes/session.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$role = $_GET['role'] ?? 'siswa'; // 'siswa' | 'guru'

if ($role === 'guru') {
    if (empty($_SESSION['guru_id'])) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'logged_in'=>false,'role'=>'guru']);
        exit;
    }
    echo json_encode([
        'ok'        => true,
        'logged_in' => true,
        'role'      => 'guru',
        'id'        => (int)$_SESSION['guru_id'],
        'nama'      => $_SESSION['guru_nama'] ?? '',
        'username'  => $_SESSION['guru_username'] ?? '',
    ]);
    exit;
}

// Default: siswa
if (empty($_SESSION['siswa_id'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'logged_in'=>false,'role'=>'siswa']);
    exit;
}

// Ambil data terkini dari DB (bukan cuma session)
try {
    // FIX: getDB() tidak ada — $pdo sudah tersedia dari db.php (global scope)
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, nama, kelas, xp, level, avatar, streak, total_benar FROM siswa WHERE id=?");
    $stmt->execute([$_SESSION['siswa_id']]);
    $siswa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        // Session sudah tidak valid — hapus
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['ok'=>false,'logged_in'=>false,'role'=>'siswa','reason'=>'not_found']);
        exit;
    }

    // Sync session dengan data DB terkini
    $_SESSION['siswa_xp']    = $siswa['xp'];
    $_SESSION['siswa_level'] = $siswa['level'];
    $_SESSION['siswa_streak']= $siswa['streak'];

    echo json_encode([
        'ok'          => true,
        'logged_in'   => true,
        'role'        => 'siswa',
        'id'          => (int)$siswa['id'],
        'nama'        => $siswa['nama'],
        'kelas'       => (int)$siswa['kelas'],
        'xp'          => (int)$siswa['xp'],
        'level'       => (int)$siswa['level'],
        'avatar'      => (int)$siswa['avatar'],
        'streak'      => (int)$siswa['streak'],
        'total_benar' => (int)$siswa['total_benar'],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'DB error']);
}