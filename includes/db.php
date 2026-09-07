<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// includes/db.php — Koneksi Database MySQL
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // default XAMPP
define('DB_PASS', '');           // default XAMPP (kosong)
define('DB_NAME', 'saintara_db');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Tampilkan error hanya di development (XAMPP lokal)
    die(json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal: ' . $e->getMessage()
    ]));
}
// Tambahkan kode ini di bagian paling bawah includes/db.php

function getDB() {
    global $pdo;
    return $pdo;
}

// Test koneksi — hapus baris ini setelah konfirmasi berhasil
// echo "Koneksi berhasil ke saintara_db!";
