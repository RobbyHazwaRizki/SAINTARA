// ============================================================
// SAINTARA — ELLA'S LAB
// assets/js/admin.js — Utilitas Panel Admin
// ============================================================

'use strict';

// ── TOAST ────────────────────────────────────────────────────
function adminToast(msg, type = 'success', duration = 3200) {
  const existing = document.querySelector('.admin-toast');
  if (existing) existing.remove();

  const t = document.createElement('div');
  t.className = 'admin-toast';
  t.style.cssText = `
    position:fixed;top:24px;right:24px;z-index:9999;
    background:#fff;border-radius:12px;padding:14px 18px;
    display:flex;align-items:center;gap:10px;
    box-shadow:0 8px 32px rgba(0,0,0,.12);
    border-left:4px solid ${type==='success'?'#80D8C3':'#E05555'};
    font-family:'Nunito',sans-serif;font-size:.88rem;font-weight:700;
    animation:adminToastIn .3s ease;min-width:260px;
  `;

  const style = document.createElement('style');
  style.textContent = `@keyframes adminToastIn{from{transform:translateX(40px);opacity:0}to{transform:none;opacity:1}}`;
  document.head.appendChild(style);

  t.innerHTML = `<span>${type==='success'?'✅':'❌'}</span><span>${msg}</span>`;
  document.body.appendChild(t);

  setTimeout(() => {
    t.style.transition = 'opacity .4s';
    t.style.opacity = '0';
    setTimeout(() => t.remove(), 400);
  }, duration);
}

// ── CONFIRM DIALOG ────────────────────────────────────────────
function adminConfirm(msg, onYes, onNo) {
  const backdrop = document.createElement('div');
  backdrop.style.cssText = `
    position:fixed;inset:0;background:rgba(26,46,58,.5);backdrop-filter:blur(4px);
    z-index:9998;display:flex;align-items:center;justify-content:center;padding:20px;
    animation:fadeIn .2s ease;
  `;

  const box = document.createElement('div');
  box.style.cssText = `
    background:#fff;border-radius:20px;padding:28px 24px;max-width:380px;width:100%;
    text-align:center;box-shadow:0 24px 64px rgba(0,0,0,.18);
    animation:popIn .25s cubic-bezier(.34,1.56,.64,1);
    font-family:'Nunito',sans-serif;
  `;

  const fadeStyle = document.createElement('style');
  fadeStyle.textContent = `
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}
    @keyframes popIn{from{transform:scale(.88);opacity:0}to{transform:none;opacity:1}}
  `;
  document.head.appendChild(fadeStyle);

  box.innerHTML = `
    <div style="font-size:2.5rem;margin-bottom:14px">⚠️</div>
    <p style="font-size:.92rem;color:#1A2E3A;line-height:1.6;font-weight:600;margin-bottom:20px">${msg}</p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button id="confirmNo"  style="padding:9px 20px;border-radius:9px;border:1.5px solid #e2edf5;background:#f0f8ff;color:#1A2E3A;font-family:'Nunito',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer">Batal</button>
      <button id="confirmYes" style="padding:9px 20px;border-radius:9px;border:none;background:#E05555;color:#fff;font-family:'Nunito',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer">Ya, Lanjutkan</button>
    </div>
  `;

  backdrop.appendChild(box);
  document.body.appendChild(backdrop);

  const close = () => backdrop.remove();

  backdrop.querySelector('#confirmYes').addEventListener('click', () => { close(); onYes && onYes(); });
  backdrop.querySelector('#confirmNo').addEventListener('click',  () => { close(); onNo && onNo(); });
  backdrop.addEventListener('click', e => { if (e.target === backdrop) close(); });
}

// ── AUTO-HIDE TOAST (yang di-render server-side) ─────────────
document.addEventListener('DOMContentLoaded', () => {
  const serverToast = document.getElementById('toastEl');
  if (serverToast) {
    setTimeout(() => {
      serverToast.style.transition = 'opacity .4s';
      serverToast.style.opacity    = '0';
      setTimeout(() => serverToast.remove(), 400);
    }, 3200);
  }

  // Konfirmasi hapus via data-confirm
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      const msg = el.dataset.confirm;
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // Bar chart animasi (dashboard)
  document.querySelectorAll('.bar-fill').forEach(bar => {
    const w = bar.style.width;
    bar.style.width = '0';
    requestAnimationFrame(() => {
      bar.style.transition = 'width .7s cubic-bezier(.4,0,.2,1)';
      bar.style.width = w;
    });
  });
});

// ── EXPORT GLOBAL ─────────────────────────────────────────────
window.adminToast   = adminToast;
window.adminConfirm = adminConfirm;