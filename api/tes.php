<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// api/tes.php — Menyimpan Hasil Pre-Test & Post-Test
// ============================================================
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

requireSiswa(); 
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Method salah']); exit;
}

$pdo = getDB();
$siswaId = (int)$_SESSION['siswa_id'];
$tipe    = $_POST['tipe'] ?? 'pretest'; // pretest atau posttest
$kelas   = (int)($_POST['kelas'] ?? 1);
$durasi  = (int)($_POST['durasi'] ?? 0);
$jawaban_siswa = json_decode($_POST['jawaban_siswa'] ?? '{}', true);

if (!in_array($tipe, ['pretest', 'posttest'])) {
    echo json_encode(['status'=>'error','message'=>'Tipe tes tidak valid']); exit;
}

$benar = 0;
$salah = 0;

try {
    $pdo->beginTransaction();

    // 1. Cek Jawaban
    if (!empty($jawaban_siswa)) {
        $stmtSoal = $pdo->prepare("SELECT id, jawaban FROM soal_tes WHERE id = ? AND tipe = ? AND kelas = ?");
        foreach ($jawaban_siswa as $id_soal => $jawaban) {
            $stmtSoal->execute([$id_soal, $tipe, $kelas]);
            $soal = $stmtSoal->fetch();
            if ($soal) {
                $isBenar = (strtolower(trim($soal['jawaban'])) === strtolower(trim($jawaban))) ? 1 : 0;
                if ($isBenar) $benar++; else $salah++;

                // Simpan ke detail jawaban tes
                $stmtDetail = $pdo->prepare("INSERT INTO detail_jawaban_tes (siswa_id, soal_tes_id, tipe, jawaban, benar) VALUES (?, ?, ?, ?, ?)");
                $stmtDetail->execute([$siswaId, $id_soal, $tipe, $jawaban, $isBenar]);
            }
        }
    }

    $total = $benar + $salah;
    $skor  = $total > 0 ? ($benar / $total) * 100 : 0;

    // 2. Simpan ke tabel hasil (Gunakan ON DUPLICATE KEY agar hanya ada 1 nilai per kelas per siswa)
    $tabel = ($tipe === 'pretest') ? 'hasil_pretest' : 'hasil_posttest';
    
    $stmtHasil = $pdo->prepare("
        INSERT INTO $tabel (siswa_id, kelas, benar, total, skor, durasi_detik) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        benar = VALUES(benar), total = VALUES(total), skor = VALUES(skor), durasi_detik = VALUES(durasi_detik)
    ");
    $stmtHasil->execute([$siswaId, $kelas, $benar, $total, $skor, $durasi]);

    $pdo->commit();

    echo json_encode([
        'status' => 'ok',
        'skor'   => $skor,
        'benar'  => $benar,
        'salah'  => $salah,
        'total'  => $total
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB error: '.$e->getMessage()]);
}