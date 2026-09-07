// ============================================================
// SAINTARA — ELLA'S LAB
// assets/js/quiz.js — Quiz Bridge & Path Fix
// Engine utama ada inline di kuis.php
// File ini: fix path, helper fungsi, konfirmasi keluar
// ============================================================

'use strict';

// ── FIX PATH: pastikan semua fetch ke /SAINTARA/ bukan /saintara/ ──
(function patchFetch() {
  const _fetch = window.fetch;
  window.fetch = function(url, options) {
    if (typeof url === 'string') {
      url = url.replace(/^\/saintara\//i, '/SAINTARA/');
    }
    return _fetch.call(this, url, options);
  };
})();

// ── FIX LOCATION: patch window.location.href assignment ────────────
// kuis.php redirect ke /saintara/ — intercept dan ganti ke /SAINTARA/
(function patchLocation() {
  const _assign = window.location.assign.bind(window.location);
  const origDescriptor = Object.getOwnPropertyDescriptor(window.location, 'href');

  // Patch link redirect manual di selesaiKuis() via MutationObserver + override
  // Cara aman: override history.pushState tidak bisa, tapi bisa intercept
  // via event sebelum redirect terjadi

  // Patch: intercept fetch .then redirect
  const bodyEl = document.querySelector('body');
  if (bodyEl) {
    bodyEl.addEventListener('click', e => {
      const a = e.target.closest('a[href]');
      if (a) {
        const href = a.getAttribute('href');
        if (href && href.toLowerCase().includes('/saintara/')) {
          e.preventDefault();
          window.location.href = href.replace(/\/saintara\//gi, '/SAINTARA/');
        }
      }
    }, true);
  }
})();

// ── KONFIRMASI KELUAR SAAT KUIS SEDANG BERJALAN ─────────────────────
let _kuisAktif = false;

function setKuisAktif(aktif) {
  _kuisAktif = aktif;
  if (aktif) {
    window.onbeforeunload = () =>
      'Kuis sedang berjalan! Yakin ingin meninggalkan halaman? Progress kuis akan hilang.';
  } else {
    window.onbeforeunload = null;
  }
}

// Aktifkan guard saat halaman kuis dimuat
document.addEventListener('DOMContentLoaded', () => {
  // Cek apakah ini halaman kuis (ada elemen quizData)
  if (document.getElementById('quizData')) {
    setKuisAktif(true);
  }
});

// Matikan guard saat kuis selesai (dipanggil dari selesaiKuis() inline)
window.matikanGuardKuis = function() {
  setKuisAktif(false);
};

// ── UTILITY: Shuffle array Fisher-Yates ──────────────────────────────
window.shuffleArr = function(arr) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
};

// ── UTILITY: Format detik ke menit:detik ──────────────────────────────
window.formatWaktu = function(detik) {
  const m = Math.floor(detik / 60);
  const s = detik % 60;
  return m > 0 ? `${m}m ${s}d` : `${s} detik`;
};

// ── KEYFRAME soalIn (fallback jika style.css belum load) ─────────────
(function injectSoalKeyframe() {
  if (document.getElementById('soalInKF')) return;
  const s = document.createElement('style');
  s.id = 'soalInKF';
  s.textContent = `
    @keyframes soalIn {
      from { transform: translateX(24px) scale(.97); opacity: 0; }
      to   { transform: none; opacity: 1; }
    }
    @keyframes feedPop {
      from { transform: scale(0); }
      to   { transform: scale(1); }
    }
  `;
  document.head.appendChild(s);
})();