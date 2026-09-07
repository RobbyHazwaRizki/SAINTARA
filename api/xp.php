<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['siswa_id'])) { jsonResponse(['status'=>'error'],401); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['status'=>'error'],405); }
$siswaId  = (int)$_SESSION['siswa_id'];
$xpGained = (int)($_POST['xp'] ?? 0);
if ($xpGained <= 0) { jsonResponse(['status'=>'error','message'=>'XP tidak valid']); }
// Update xp lifetime
$result = tambahXP($siswaId, $xpGained, $pdo);
// Update xp sesi aktif
$stmt = $pdo->prepare("SELECT id FROM sesi WHERE aktif=1 ORDER BY mulai DESC LIMIT 1");
$stmt->execute();
$sesi = $stmt->fetch();
if ($sesi) {
    $stmt = $pdo->prepare("
        INSERT INTO xp_sesi (sesi_id, siswa_id, xp) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE xp = xp + VALUES(xp)
    ");
    $stmt->execute([$sesi['id'], $siswaId, $xpGained]);
}
jsonResponse(array_merge(['status'=>'ok'], $result));