<?php
$pageTitle  = 'Beranda';
$activePage = 'beranda';
require_once '../includes/header.php';
require_once '../includes/session.php';
requireSiswa(); // auto redirect ke login jika belum login
?>

<!-- konten halaman di sini -->

<?php require_once '../includes/navbar.php'; ?>
<?php require_once '../includes/footer.php'; ?>

<?php
require_once 'includes/session.php';

// Jika siswa sudah login, arahkan ke beranda. Jika belum, arahkan ke login.
if (!empty($_SESSION['siswa_id'])) {
    header('Location: /SAINTARA/pages/beranda.php');
} else {
    header('Location: /SAINTARA/login.php');
}
exit;