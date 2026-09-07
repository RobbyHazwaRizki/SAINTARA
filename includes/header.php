<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// includes/header.php — HTML Head + Opening Shell
// Cara pakai: require_once '../includes/header.php';
//             $pageTitle = 'Beranda'; (set sebelum require)
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle   = $pageTitle   ?? 'Saintara';
$pageClass   = $pageClass   ?? '';
$hideNav     = $hideNav     ?? false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <meta name="description" content="Saintara — Ella's Lab: Lab Sains Seru untuk Ilmuwan Cilik!">

  <title><?= htmlspecialchars($pageTitle) ?> — Saintara</title>

  <!-- Favicon -->
  <link rel="icon" href="/SAINTARA/assets/img/favicon.png" type="image/png">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- CSS -->
  <link rel="stylesheet" href="/SAINTARA/assets/css/style.css">
  <link rel="stylesheet" href="/SAINTARA/assets/css/ella.css">
</head>
<body>
<div class="app <?= htmlspecialchars($pageClass) ?>">