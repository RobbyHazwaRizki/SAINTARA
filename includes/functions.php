<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// includes/functions.php — Helper Functions
// ============================================================

require_once __DIR__ . '/db.php';

// ─── LEVEL SYSTEM ───────────────────────────────────────────
function getLevel(int $xp): int {
    if ($xp >= 2000) return 5;
    if ($xp >= 1000) return 4;
    if ($xp >= 500)  return 3;
    if ($xp >= 200)  return 2;
    return 1;
}

function getLevelName(int $level): string {
    $names = [
        1 => 'Penjelajah Baru',
        2 => 'Petualang Muda',
        3 => 'Ilmuwan Cilik',
        4 => 'Peneliti Muda',
        5 => 'Profesor Kecil',
    ];
    return $names[$level] ?? 'Penjelajah Baru';
}

function getLevelThreshold(int $level): array {
    $thresholds = [
        1 => ['min' => 0,    'max' => 199],
        2 => ['min' => 200,  'max' => 499],
        3 => ['min' => 500,  'max' => 999],
        4 => ['min' => 1000, 'max' => 1999],
        5 => ['min' => 2000, 'max' => 9999],
    ];
    return $thresholds[$level] ?? $thresholds[1];
}

function getLevelProgress(int $xp): int {
    $level  = getLevel($xp);
    $thresh = getLevelThreshold($level);
    $range  = $thresh['max'] - $thresh['min'];
    $earned = $xp - $thresh['min'];
    return $range > 0 ? (int) min(100, ($earned / $range) * 100) : 100;
}

function getXpReward(int $kelas): int {
    if ($kelas <= 2) return 20;
    if ($kelas <= 4) return 15;
    return 10;
}

// ─── XP UPDATE ──────────────────────────────────────────────
function tambahXP(int $siswaId, int $xpGained, PDO $pdo): array {
    $stmt = $pdo->prepare("SELECT xp, level FROM siswa WHERE id = ?");
    $stmt->execute([$siswaId]);
    $siswa = $stmt->fetch();

    if (!$siswa) return ['success' => false, 'message' => 'Siswa tidak ditemukan'];

    $xpBaru    = $siswa['xp'] + $xpGained;
    $levelBaru = getLevel($xpBaru);
    $levelNaik = $levelBaru > $siswa['level'];

    $stmt = $pdo->prepare("UPDATE siswa SET xp = ?, level = ? WHERE id = ?");
    $stmt->execute([$xpBaru, $levelBaru, $siswaId]);

    return [
        'success'    => true,
        'xp_baru'    => $xpBaru,
        'level_baru' => $levelBaru,
        'level_naik' => $levelNaik,
        'level_name' => getLevelName($levelBaru),
        'progress'   => getLevelProgress($xpBaru),
    ];
}

// ─── STREAK ─────────────────────────────────────────────────
function tambahStreak(int $siswaId, PDO $pdo): int {
    $stmt = $pdo->prepare("UPDATE siswa SET streak = streak + 1 WHERE id = ?");
    $stmt->execute([$siswaId]);
    $stmt = $pdo->prepare("SELECT streak FROM siswa WHERE id = ?");
    $stmt->execute([$siswaId]);
    return (int) $stmt->fetchColumn();
}

function resetStreak(int $siswaId, PDO $pdo): void {
    $stmt = $pdo->prepare("UPDATE siswa SET streak = 0 WHERE id = ?");
    $stmt->execute([$siswaId]);
}

// ─── BADGE DEFINITIONS ──────────────────────────────────────
function getAllBadges(): array {
    return [
        'selamat_datang'   => ['nama' => 'Selamat Datang!',    'icon' => '🎉', 'tier' => 'mudah'],
        'benih_pengetahuan'=> ['nama' => 'Benih Pengetahuan',  'icon' => '🌱', 'tier' => 'mudah'],
        'bintang_pertama'  => ['nama' => 'Bintang Pertama',    'icon' => '⭐', 'tier' => 'mudah'],
        'api_semangat'     => ['nama' => 'Api Semangat',       'icon' => '🔥', 'tier' => 'sedang'],
        'sempurna'         => ['nama' => 'Sempurna!',          'icon' => '💯', 'tier' => 'sedang'],
        'kutu_buku'        => ['nama' => 'Kutu Buku',          'icon' => '📚', 'tier' => 'sedang'],
        'kilat'            => ['nama' => 'Kilat!',             'icon' => '⚡', 'tier' => 'sedang'],
        'tepat_sasaran'    => ['nama' => 'Tepat Sasaran',      'icon' => '🎯', 'tier' => 'sedang'],
        'rajin_belajar'    => ['nama' => 'Rajin Belajar',      'icon' => '📖', 'tier' => 'sedang'],
        'juara_sejati'     => ['nama' => 'Juara Sejati',       'icon' => '🏆', 'tier' => 'langka'],
        'ilmuwan_cilik'    => ['nama' => 'Ilmuwan Cilik',      'icon' => '🔬', 'tier' => 'langka'],
        'raja_kelas'       => ['nama' => 'Raja Kelas',         'icon' => '👑', 'tier' => 'langka'],
    ];
}

// ─── BADGE FUNCTIONS ────────────────────────────────────────
function sudahPunyaBadge(int $siswaId, string $badgeKey, PDO $pdo): bool {
    $stmt = $pdo->prepare("SELECT id FROM badge_siswa WHERE siswa_id = ? AND badge_key = ?");
    $stmt->execute([$siswaId, $badgeKey]);
    return (bool) $stmt->fetch();
}

function berikanBadge(int $siswaId, string $badgeKey, PDO $pdo): ?array {
    $badges = getAllBadges();
    if (!isset($badges[$badgeKey])) return null;
    if (sudahPunyaBadge($siswaId, $badgeKey, $pdo)) return null;

    $stmt = $pdo->prepare("INSERT INTO badge_siswa (siswa_id, badge_key) VALUES (?, ?)");
    $stmt->execute([$siswaId, $badgeKey]);

    return array_merge(['key' => $badgeKey], $badges[$badgeKey]);
}

function getBadgeSiswa(int $siswaId, PDO $pdo): array {
    $stmt = $pdo->prepare("SELECT badge_key, unlocked_at FROM badge_siswa WHERE siswa_id = ? ORDER BY unlocked_at DESC");
    $stmt->execute([$siswaId]);
    $rows   = $stmt->fetchAll();
    $badges = getAllBadges();
    $result = [];
    foreach ($rows as $row) {
        $key = $row['badge_key'];
        if (isset($badges[$key])) {
            $result[] = array_merge(['key' => $key, 'unlocked_at' => $row['unlocked_at']], $badges[$key]);
        }
    }
    return $result;
}

// ─── FUNGSI CEK DAN BERIKAN BADGE (DINAMIS DARI DB) ───────────
function cekDanBerikanBadge(int $siswaId, array $kuisData, PDO $pdo): array {
    $unlocked = [];

    // 1. Ambil data statistik siswa saat ini
    $stmt = $pdo->prepare("SELECT total_benar, streak FROM siswa WHERE id = ?");
    $stmt->execute([$siswaId]);
    $siswa = $stmt->fetch();

    // Hitung jumlah topik yang sudah selesai (skor >= 60%)
    $stmtTopik = $pdo->prepare("SELECT COUNT(*) FROM progress WHERE siswa_id = ? AND selesai = 1");
    $stmtTopik->execute([$siswaId]);
    $topikSelesai = (int)$stmtTopik->fetchColumn();

    // 2. Ambil SEMUA badge yang BELUM didapatkan oleh siswa
    $stmtBadges = $pdo->prepare("
        SELECT bm.* FROM badge_master bm
        LEFT JOIN badge_siswa bs ON bm.badge_key = bs.badge_key AND bs.siswa_id = ?
        WHERE bs.badge_key IS NULL
    ");
    $stmtBadges->execute([$siswaId]);
    $availableBadges = $stmtBadges->fetchAll();

    // 3. Cek satu per satu apakah syaratnya sudah terpenuhi
    foreach ($availableBadges as $badge) {
        $tipe  = $badge['syarat_tipe'];
        $nilai = (int)$badge['syarat_nilai'];
        $memenuhiSyarat = false;

        switch ($tipe) {
            case 'total_benar': // Contoh: Harus menjawab 50 soal benar
                if ($siswa['total_benar'] >= $nilai) $memenuhiSyarat = true;
                break;
            case 'login_streak': // Contoh: Login/belajar 7 hari berturut-turut
                if ($siswa['streak'] >= $nilai) $memenuhiSyarat = true;
                break;
            case 'topik_selesai': // Contoh: Menyelesaikan 24 topik
                if ($topikSelesai >= $nilai) $memenuhiSyarat = true;
                break;
            case 'perfect_score': // Contoh: Nilai 100 di satu kuis
                if (isset($kuisData['benar'], $kuisData['total_soal']) && $kuisData['total_soal'] > 0) {
                    if ($kuisData['benar'] === $kuisData['total_soal']) $memenuhiSyarat = true;
                }
                break;
            // Anda bisa tambahkan case lain sesuai kebutuhan di database nanti
        }

        // 4. Jika terpenuhi, masukkan ke database badge_siswa
        if ($memenuhiSyarat) {
            $stmtUnlock = $pdo->prepare("INSERT IGNORE INTO badge_siswa (siswa_id, badge_key) VALUES (?, ?)");
            if ($stmtUnlock->execute([$siswaId, $badge['badge_key']])) {
                $unlocked[] = [
                    'key'  => $badge['badge_key'],
                    'nama' => $badge['nama'],
                    'icon' => $badge['icon']
                ];
            }
        }
    }

    return $unlocked; // Kembalikan array lencana yang baru saja didapat
}

// ─── RANKING ────────────────────────────────────────────────
function getRanking(PDO $pdo, ?int $kelas = null, int $limit = 20): array {
    if ($kelas) {
        $stmt = $pdo->prepare("SELECT id, nama, kelas, xp, level, avatar FROM siswa WHERE kelas = ? ORDER BY xp DESC LIMIT ?");
        $stmt->execute([$kelas, $limit]);
    } else {
        $stmt = $pdo->prepare("SELECT id, nama, kelas, xp, level, avatar FROM siswa ORDER BY xp DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

// ─── UTILITIES ──────────────────────────────────────────────
function bersihkan(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validasiKelas(int $kelas): bool {
    return $kelas >= 1 && $kelas <= 6;
}

function jsonResponse(array $data, int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}