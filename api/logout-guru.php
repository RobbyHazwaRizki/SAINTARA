<?php
/**
 * SAINTARA — Ella's Lab
 * api/logout-guru.php
 * Logout guru → redirect ke login-guru.php
 */

require_once '../includes/session.php';

// Hancurkan session guru
logoutGuru();

// Redirect ke halaman login guru
header('Location: /SAINTARA/login-guru.php?logout=1');
exit;