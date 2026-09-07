// ============================================================
// SAINTARA — ELLA'S LAB
// assets/js/app.js — Core App Logic
// navigateTo(), animasi XP counter, utility global
// ============================================================

'use strict';

// ── BASE PATH ────────────────────────────────────────────────
const BASE = '/SAINTARA';

// ── NAVIGASI BOTTOM NAV ──────────────────────────────────────
function navigateTo(page) {
  const map = {
    beranda : BASE + '/pages/beranda.php',
    materi  : BASE + '/pages/materi.php',
    ranking : BASE + '/pages/ranking.php',
    profil  : BASE + '/pages/profil.php',
  };
  if (map[page]) {
    window.location.href = map[page];
  }
}

// ── ANIMASI COUNTER (angka naik dari 0 ke target) ────────────
function animateCounter(el, target, duration = 900, suffix = '') {
  if (!el) return;
  const start     = performance.now();
  const startVal  = parseInt(el.textContent) || 0;
  const range     = target - startVal;

  function step(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    // Easing: ease-out cubic
    const ease = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.round(startVal + range * ease) + suffix;
    if (progress < 1) requestAnimationFrame(step);
  }

  requestAnimationFrame(step);
}

// ── XP BAR ANIMASI ───────────────────────────────────────────
function animateXPBar(fillEl, targetPct, delay = 300) {
  if (!fillEl) return;
  fillEl.style.width = '0%';
  setTimeout(() => {
    fillEl.style.transition = 'width .8s cubic-bezier(.4,0,.2,1)';
    fillEl.style.width      = targetPct + '%';
  }, delay);
}

// ── TOAST NOTIFIKASI GLOBAL ──────────────────────────────────
function showToast(msg, type = 'info', duration = 3000) {
  // type: 'info' | 'success' | 'error'
  const icons = { success: '✅', error: '❌', info: '💡' };
  const colors = {
    success: '#80D8C3',
    error  : '#E05555',
    info   : '#4DA8DA',
  };

  const toast = document.createElement('div');
  toast.style.cssText = `
    position:fixed; bottom:calc(68px + 16px); left:50%; transform:translateX(-50%);
    background:#fff; border-radius:12px; padding:12px 18px;
    display:flex; align-items:center; gap:10px;
    box-shadow:0 8px 28px rgba(0,0,0,.14);
    border-left:4px solid ${colors[type]};
    font-family:'Nunito',sans-serif; font-size:.88rem; font-weight:700;
    z-index:9999; white-space:nowrap;
    animation:toastSlideUp .3s ease;
    max-width:calc(100vw - 40px);
  `;

  toast.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;

  const style = document.createElement('style');
  style.textContent = `
    @keyframes toastSlideUp {
      from { transform:translateX(-50%) translateY(20px); opacity:0; }
      to   { transform:translateX(-50%) translateY(0);    opacity:1; }
    }
  `;
  document.head.appendChild(style);
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.transition = 'opacity .3s, transform .3s';
    toast.style.opacity    = '0';
    toast.style.transform  = 'translateX(-50%) translateY(10px)';
    setTimeout(() => toast.remove(), 350);
  }, duration);
}

// ── HAPUS SPLASH SCREEN (jika ada) ───────────────────────────
function removeSplash() {
  const splash = document.getElementById('splashScreen');
  if (splash) {
    splash.style.opacity    = '0';
    splash.style.transition = 'opacity .4s';
    setTimeout(() => splash.remove(), 400);
  }
}

// ── FORMAT ANGKA RIBUAN ──────────────────────────────────────
function formatNumber(n) {
  return Number(n).toLocaleString('id-ID');
}

// ── ACCORDION TOGGLE (materi-detail.php) ────────────────────
function toggleAccordion(header) {
  const body    = header.nextElementSibling;
  const chevron = header.querySelector('.sec-chevron');
  const isOpen  = body && body.classList.contains('open');

  // Tutup semua dulu
  document.querySelectorAll('.sec-body.open').forEach(b => {
    b.classList.remove('open');
    b.style.display = 'none';
  });
  document.querySelectorAll('.sec-chevron.open').forEach(c => {
    c.classList.remove('open');
  });

  // Buka yang diklik (jika sebelumnya tertutup)
  if (!isOpen && body) {
    body.classList.add('open');
    body.style.display = 'block';
    body.style.animation = 'fadeIn .25s ease';
    if (chevron) chevron.classList.add('open');
  }
}

// ── RIPPLE EFFECT pada tombol ────────────────────────────────
function addRipple(e) {
  const btn  = e.currentTarget;
  const rect = btn.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const x    = e.clientX - rect.left - size / 2;
  const y    = e.clientY - rect.top  - size / 2;

  const ripple = document.createElement('span');
  ripple.style.cssText = `
    position:absolute; border-radius:50%; pointer-events:none;
    width:${size}px; height:${size}px; left:${x}px; top:${y}px;
    background:rgba(255,255,255,.3);
    transform:scale(0); animation:rippleAnim .5s ease;
  `;

  if (getComputedStyle(btn).position === 'static') {
    btn.style.position = 'relative';
  }
  btn.style.overflow = 'hidden';
  btn.appendChild(ripple);
  setTimeout(() => ripple.remove(), 600);
}

// ── INIT ON DOM READY ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // Animasi fade-in semua elemen .fade-in
  const fadeEls = document.querySelectorAll('.fade-in');
  if ('IntersectionObserver' in window) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity    = '1';
          entry.target.style.transform  = 'none';
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: .1 });

    fadeEls.forEach(el => {
      el.style.opacity   = '0';
      el.style.transform = 'translateY(14px)';
      el.style.transition = `opacity .4s ease ${el.style.animationDelay || '0s'}, transform .4s ease ${el.style.animationDelay || '0s'}`;
      obs.observe(el);
    });
  }

  // Ripple pada semua .btn
  document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', addRipple);
  });

  // Animasi XP bar on load
  document.querySelectorAll('.xp-bar-fill').forEach(bar => {
    const target = bar.dataset.target || bar.style.width;
    const pct    = parseInt(target) || 0;
    bar.style.width = '0%';
    setTimeout(() => {
      bar.style.transition = 'width .8s cubic-bezier(.4,0,.2,1)';
      bar.style.width      = pct + '%';
    }, 200);
  });

  // Animasi topik-prog-fill
  document.querySelectorAll('.topik-prog-fill').forEach(bar => {
    const w = bar.style.width;
    bar.style.width = '0%';
    setTimeout(() => {
      bar.style.transition = 'width .6s ease';
      bar.style.width = w;
    }, 300);
  });

  // Animasi counter semua .stat-chip-num
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count) || 0;
    animateCounter(el, target, 800);
  });

  // Hapus splash jika ada
  removeSplash();

  // Ripple style inject
  const rippleStyle = document.createElement('style');
  rippleStyle.textContent = `
    @keyframes rippleAnim {
      to { transform:scale(4); opacity:0; }
    }
  `;
  document.head.appendChild(rippleStyle);
});

// ── GLOBAL ERROR HANDLER (silent) ───────────────────────────
window.addEventListener('unhandledrejection', () => {});