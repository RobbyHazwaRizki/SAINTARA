<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// api/ranking.php — Endpoint JSON Ranking
// GET ?kelas=&mode=lifetime|sesi&limit=
// ============================================================
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireSiswa();
header('Content-Type: application/json');

$pdo    = getDB();
$siswa  = getSiswaSession();
$kelas  = (int)($_GET['kelas']  ?? 0);
$mode   = ($_GET['mode'] === 'sesi') ? 'sesi' : 'lifetime';
$limit  = min(50, max(10, (int)($_GET['limit'] ?? 20)));

$avatars = ['🐘','🦁','🐯','🦊','🐧','🦋','🐬','🦜','🐸','🌟'];

try {
    if ($mode === 'lifetime') {
        // ── RANKING LIFETIME ────────────────────────────────
        $where  = $kelas ? 'WHERE kelas = ?' : '';
        $params = $kelas ? [$kelas, $limit] : [$limit];

        $stmt = $pdo->prepare("
            SELECT id, nama, kelas, xp, level, avatar,
                   streak, total_benar,
                   RANK() OVER (ORDER BY xp DESC) AS ranking
            FROM siswa
            $where
            ORDER BY xp DESC
            LIMIT ?
        ");
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Posisi sendiri
        $my_where  = $kelas ? 'AND kelas = ?' : '';
        $my_params = $kelas ? [$siswa['id'], $kelas, $siswa['id']] : [$siswa['id'], $siswa['id']];
        $my_stmt   = $pdo->prepare("
            SELECT ranking FROM (
                SELECT id, RANK() OVER (ORDER BY xp DESC) AS ranking
                FROM siswa
                WHERE 1=1 $my_where
            ) r WHERE id = ?
        ");
        $my_stmt->execute($kelas ? [$kelas, $siswa['id']] : [$siswa['id']]);
        $my_rank = (int)($my_stmt->fetchColumn() ?: 0);

    } else {
        // ── RANKING SESI AKTIF ───────────────────────────────
        $sesi = $pdo->query("SELECT id, nama, kelas FROM sesi WHERE aktif=1 ORDER BY mulai DESC LIMIT 1")->fetch();

        if (!$sesi) {
            echo json_encode(['ok'=>true,'mode'=>'sesi','sesi'=>null,'list'=>[],'my_rank'=>0,'my_xp'=>0]);
            exit;
        }

        $where  = $kelas ? 'AND si.kelas = ?' : '';
        $params = array_filter([$sesi['id'], $kelas ?: null, $limit]);
        $params = $kelas ? [$sesi['id'], $kelas, $limit] : [$sesi['id'], $limit];

        $stmt = $pdo->prepare("
            SELECT si.id, si.nama, si.kelas, si.avatar, si.level,
                   x.xp,
                   RANK() OVER (ORDER BY x.xp DESC) AS ranking
            FROM xp_sesi x
            JOIN siswa si ON si.id = x.siswa_id
            WHERE x.sesi_id = ? $where
            ORDER BY x.xp DESC
            LIMIT ?
        ");
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // XP sesi milik sendiri
        $my_xp_stmt = $pdo->prepare("SELECT xp FROM xp_sesi WHERE sesi_id=? AND siswa_id=?");
        $my_xp_stmt->execute([$sesi['id'], $siswa['id']]);
        $my_xp = (int)($my_xp_stmt->fetchColumn() ?: 0);

        // Posisi sendiri di sesi
        $my_rank_stmt = $pdo->prepare("
            SELECT ranking FROM (
                SELECT siswa_id, RANK() OVER (ORDER BY xp DESC) AS ranking
                FROM xp_sesi WHERE sesi_id=?
            ) r WHERE siswa_id=?
        ");
        $my_rank_stmt->execute([$sesi['id'], $siswa['id']]);
        $my_rank = (int)($my_rank_stmt->fetchColumn() ?: 0);
    }

    // Format list
    $formatted = array_map(function($r) use ($avatars) {
        return [
            'id'      => (int)$r['id'],
            'nama'    => $r['nama'],
            'kelas'   => (int)$r['kelas'],
            'xp'      => (int)$r['xp'],
            'level'   => (int)($r['level'] ?? 1),
            'avatar'  => $avatars[(int)$r['avatar']] ?? '🐘',
            'ranking' => (int)$r['ranking'],
            'is_me'   => ((int)$r['id'] === (int)($GLOBALS['siswa']['id'] ?? 0)),
        ];
    }, $list);

    $resp = [
        'ok'      => true,
        'mode'    => $mode,
        'kelas'   => $kelas,
        'list'    => $formatted,
        'my_rank' => $my_rank,
    ];

    if ($mode === 'sesi') {
        $resp['sesi']   = $sesi ?? null;
        $resp['my_xp']  = $my_xp ?? 0;
    }

    echo json_encode($resp, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server error']);
}