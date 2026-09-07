<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// api/simpan-tes.php — Simpan Hasil Pre/Post Test
// POST JSON: tipe, kelas, benar, total, skor, durasi, detail[]
// ============================================================
require_once '../includes/session.php';
require_once '../includes/db.php';

requireSiswa();
header('Content-Type: application/json');

$siswa_id = $_SESSION['siswa_id'];
$body     = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Invalid JSON']);
    exit;
}

$tipe   = in_array($body['tipe'] ?? '', ['pretest','posttest']) ? $body['tipe'] : null;
$kelas  = (int)($body['kelas']  ?? 0);
$benar  = (int)($body['benar']  ?? 0);
$total  = (int)($body['total']  ?? 0);
$skor   = round((float)($body['skor'] ?? 0), 2);
$durasi = (int)($body['durasi'] ?? 0);
$detail = $body['detail'] ?? [];

if (!$tipe || $kelas < 1 || $kelas > 6 || $total <= 0) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Data tidak valid']);
    exit;
}

try {
    // FIX BUG #1: getDB() tidak ada — $pdo sudah tersedia dari db.php (global scope)
    global $pdo;
    $tabel = $tipe === 'pretest' ? 'hasil_pretest' : 'hasil_posttest';

    // Upsert hasil (UNIQUE KEY pada siswa_id + kelas)
    $pdo->prepare("
        INSERT INTO $tabel (siswa_id, kelas, benar, total, skor, durasi_detik)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            benar         = VALUES(benar),
            total         = VALUES(total),
            skor          = VALUES(skor),
            durasi_detik  = VALUES(durasi_detik),
            dikerjakan_at = NOW()
    ")->execute([$siswa_id, $kelas, $benar, $total, $skor, $durasi]);

    // Simpan detail jawaban per soal (hapus lama dulu)
    if (!empty($detail)) {
        $del = $pdo->prepare("
            DELETE FROM detail_jawaban_tes
            WHERE siswa_id=? AND tipe=?
            AND soal_tes_id IN (SELECT id FROM soal_tes WHERE tipe=? AND kelas=?)
        ");
        $del->execute([$siswa_id, $tipe, $tipe, $kelas]);

        $ins = $pdo->prepare("
            INSERT INTO detail_jawaban_tes (siswa_id, soal_tes_id, tipe, jawaban, benar)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($detail as $d) {
            $soal_id  = (int)($d['soal_id']  ?? 0);
            $jawaban  = substr($d['jawaban']  ?? 'x', 0, 1);
            $is_benar = (int)($d['benar'] ?? 0);
            if ($soal_id > 0) {
                $ins->execute([$siswa_id, $soal_id, $tipe, $jawaban, $is_benar]);
            }
        }
    }

    // Ambil hasil pretest untuk hitung gain (jika ini posttest)
    $gain = null;
    if ($tipe === 'posttest') {
        $pre = $pdo->prepare("SELECT skor FROM hasil_pretest WHERE siswa_id=? AND kelas=?");
        $pre->execute([$siswa_id, $kelas]);
        $pre_skor = $pre->fetchColumn();
        if ($pre_skor !== false) {
            $gain = round($skor - (float)$pre_skor, 2);
        }
    }

    echo json_encode([
        'ok'    => true,
        'tipe'  => $tipe,
        'skor'  => $skor,
        'gain'  => $gain,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'DB error']);
}