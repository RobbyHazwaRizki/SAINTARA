<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// includes/session.php — Session & Auth Guard
// v2 — tambah getGuruSession + setGuruSession
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── CEK LOGIN SISWA ────────────────────────────────────────
function requireSiswa(): void {
    if (empty($_SESSION['siswa_id'])) {
        header('Location: /SAINTARA/login.php');
        exit;
    }
}

// ─── CEK LOGIN GURU ─────────────────────────────────────────
function requireGuru(): void {
    if (empty($_SESSION['guru_id'])) {
        header('Location: /SAINTARA/login-guru.php');
        exit;
    }
}

// ─── AMBIL DATA SISWA DARI SESSION ──────────────────────────
function getSiswaSession(): ?array {
    if (empty($_SESSION['siswa_id'])) return null;
    return [
        'id'     => $_SESSION['siswa_id'],
        'nama'   => $_SESSION['siswa_nama']   ?? '',
        'kelas'  => $_SESSION['siswa_kelas']  ?? 1,
        'xp'     => $_SESSION['siswa_xp']     ?? 0,
        'level'  => $_SESSION['siswa_level']  ?? 1,
        'avatar' => $_SESSION['siswa_avatar'] ?? '🐘',
        'streak' => $_SESSION['siswa_streak'] ?? 0,
    ];
}

// ─── SET SESSION SISWA ──────────────────────────────────────
function setSiswaSession(array $siswa): void {
    $_SESSION['siswa_id']     = $siswa['id'];
    $_SESSION['siswa_nama']   = $siswa['nama'];
    $_SESSION['siswa_kelas']  = $siswa['kelas'];
    $_SESSION['siswa_xp']     = $siswa['xp'];
    $_SESSION['siswa_level']  = $siswa['level'];
    $_SESSION['siswa_avatar'] = $siswa['avatar'];
    $_SESSION['siswa_streak'] = $siswa['streak'];
}

// ─── SET SESSION GURU ────────────────────────────────────────
function setGuruSession(array $guru): void {
    $_SESSION['guru_id']       = $guru['id'];
    $_SESSION['guru_username'] = $guru['username'];
    $_SESSION['guru_nama']     = $guru['nama'];
}

// ─── AMBIL DATA GURU DARI SESSION ───────────────────────────
function getGuruSession(): array {
    return [
        'id'       => $_SESSION['guru_id']       ?? 0,
        'username' => $_SESSION['guru_username'] ?? '',
        'nama'     => $_SESSION['guru_nama']     ?? 'Guru',
    ];
}

// ─── UPDATE SESSION XP & LEVEL ──────────────────────────────
function updateSessionXP(int $xpBaru, int $levelBaru): void {
    $_SESSION['siswa_xp']    = $xpBaru;
    $_SESSION['siswa_level'] = $levelBaru;
}

// ─── LOGOUT ─────────────────────────────────────────────────
function logoutSiswa(): void {
    session_unset();
    session_destroy();
    header('Location: /SAINTARA/login.php');
    exit;
}

function logoutGuru(): void {
    session_unset();
    session_destroy();
    header('Location: /SAINTARA/login-guru.php');
    exit;
}

// ─── SUDAH LOGIN? (untuk redirect dari login page) ──────────
function sudahLoginSiswa(): bool {
    return !empty($_SESSION['siswa_id']);
}

function sudahLoginGuru(): bool {
    return !empty($_SESSION['guru_id']);
}