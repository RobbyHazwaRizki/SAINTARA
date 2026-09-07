<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// includes/navbar.php — Bottom Navigation
// ============================================================

$activePage = $activePage ?? 'beranda';
?>
<nav class="bottom-nav" role="navigation" aria-label="Navigasi utama">

  <button class="nav-item <?= $activePage === 'beranda' ? 'active' : '' ?>"
          onclick="navigateTo('beranda')" aria-label="Beranda">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
      <polyline points="9,22 9,12 15,12 15,22"/>
    </svg>
    <span>Beranda</span>
  </button>

  <button class="nav-item <?= $activePage === 'materi' ? 'active' : '' ?>"
          onclick="navigateTo('materi')" aria-label="Materi">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
      <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
    </svg>
    <span>Materi</span>
  </button>

  <button class="nav-item <?= $activePage === 'ranking' ? 'active' : '' ?>"
          onclick="navigateTo('ranking')" aria-label="Ranking">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </svg>
    <span>Ranking</span>
  </button>

  <button class="nav-item <?= $activePage === 'profil' ? 'active' : '' ?>"
          onclick="navigateTo('profil')" aria-label="Profil">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
      <circle cx="12" cy="7" r="4"/>
    </svg>
    <span>Profil</span>
  </button>

</nav>