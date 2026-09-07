// ============================================================
// SAINTARA — ELLA'S LAB
// assets/js/ella.js — Maskot Ella Si Gajah
// SVG inject, 4 ekspresi, chat panel rule-based
// ============================================================

'use strict';

// ── SVG ELLA ─────────────────────────────────────────────────
const ELLA_SVG = `
<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <ellipse cx="18" cy="38" rx="11" ry="14" fill="#4DA8DA"/>
  <ellipse cx="18" cy="38" rx="7"  ry="10" fill="#80D8C3"/>
  <ellipse cx="62" cy="38" rx="11" ry="14" fill="#4DA8DA"/>
  <ellipse cx="62" cy="38" rx="7"  ry="10" fill="#80D8C3"/>
  <ellipse cx="40" cy="44" rx="22" ry="20" fill="#4DA8DA"/>
  <circle  cx="40" cy="30" r="19" fill="#4DA8DA"/>
  <path d="M34 46 Q28 52 30 58 Q31 62 35 61" stroke="#3A90C2" stroke-width="4.5" stroke-linecap="round" fill="none"/>
  <ellipse cx="26" cy="36" rx="5" ry="3" fill="#FFD66B" fill-opacity="0.55" id="ellaBlushL"/>
  <ellipse cx="54" cy="36" rx="5" ry="3" fill="#FFD66B" fill-opacity="0.55" id="ellaBlushR"/>
  <circle  cx="33" cy="27" r="4" fill="white"/>
  <circle  cx="33" cy="27" r="2.5" fill="#1A2E3A" id="ellaLeftEye"/>
  <circle  cx="34" cy="26" r=".8" fill="white"/>
  <circle  cx="47" cy="27" r="4" fill="white"/>
  <circle  cx="47" cy="27" r="2.5" fill="#1A2E3A" id="ellaRightEye"/>
  <circle  cx="48" cy="26" r=".8" fill="white"/>
  <path id="ellaMouth" d="M33 36 Q40 42 47 36" stroke="#1A2E3A" stroke-width="2" stroke-linecap="round" fill="none"/>
</svg>`;

// ── EKSPRESI CONFIG ───────────────────────────────────────────
const ELLA_MOOD = {
  happy    : { mouth:'M33 36 Q40 42 47 36', bL:'0.55', bR:'0.55', anim:'ellaHappyBounce 2.5s ease-in-out infinite' },
  excited  : { mouth:'M30 36 Q40 45 50 36', bL:'0.9',  bR:'0.9',  anim:'ellaJump 0.5s cubic-bezier(0.34,1.56,0.64,1)' },
  sad      : { mouth:'M33 42 Q40 36 47 42', bL:'0.15', bR:'0.15', anim:'ellaShake 0.4s ease' },
  thinking : { mouth:'M34 39 Q40 39 46 39', bL:'0.35', bR:'0.35', anim:'ellaWiggle 1.5s ease-in-out infinite' },
};

function setEllaEkspresi(mood, svgEl) {
  const target = svgEl || document.querySelector('#ellaFloatBtn svg, #ellaSvg');
  if (!target) return;
  const cfg = ELLA_MOOD[mood] || ELLA_MOOD.happy;
  const m  = target.querySelector('#ellaMouth');
  const bL = target.querySelector('#ellaBlushL');
  const bR = target.querySelector('#ellaBlushR');
  if (m)  m.setAttribute('d', cfg.mouth);
  if (bL) bL.setAttribute('fill-opacity', cfg.bL);
  if (bR) bR.setAttribute('fill-opacity', cfg.bR);
  target.style.animation = 'none';
  void target.offsetWidth;
  target.style.animation = cfg.anim;
}

function injectEllaFloat() {
  const btn = document.getElementById('ellaFloatBtn');
  if (!btn) return;
  btn.innerHTML = ELLA_SVG;
  const svg = btn.querySelector('svg');
  if (svg) { svg.style.cssText = 'width:40px;height:40px;animation:ellaHappyBounce 2.5s ease-in-out infinite'; }
}

// ── CHAT PANEL ────────────────────────────────────────────────
function toggleEllaPanel() {
  const panel = document.getElementById('ellaPanel');
  if (!panel) return;
  const opening = !panel.classList.contains('open');
  panel.classList.toggle('open');
  if (opening) {
    setEllaEkspresi('excited');
    setTimeout(() => setEllaEkspresi('happy'), 600);
    const chat = document.getElementById('ellaChatArea');
    if (chat) setTimeout(() => { chat.scrollTop = chat.scrollHeight; }, 100);
    const inp = document.getElementById('ellaInput');
    if (inp) setTimeout(() => inp.focus(), 200);
  } else {
    setEllaEkspresi('happy');
  }
}

function showEllaBubble(text, duration = 3000) {
  const b = document.getElementById('ellaBubble');
  if (!b) return;
  b.textContent = text;
  b.style.cssText = 'display:block;opacity:1;animation:bubblePop .3s cubic-bezier(.34,1.56,.64,1)';
  clearTimeout(b._t);
  b._t = setTimeout(() => {
    b.style.transition = 'opacity .3s';
    b.style.opacity    = '0';
    setTimeout(() => { b.style.display = 'none'; b.style.opacity = '1'; }, 300);
  }, duration);
}

// ── RULE-BASED CHAT ───────────────────────────────────────────
let _cc = 0;

const RULES = [
  { k:['halo','hai','hello','hi','pagi','siang','sore','malam'],
    r:['Halo! 🐘 Aku Ella asisten belajar IPA-mu. Ada yang mau ditanyain?',
       'Hai! Senang ketemu kamu~ Mau belajar apa hari ini? 🌟',
       'Halo halo! 🎉 Siap belajar IPA bareng Ella?'] },
  { k:['apa itu','pengertian','definisi','apa yang dimaksud'],
    r:['Wah pertanyaan bagus! Coba baca bagian materi dulu ya 📖',
       'Aku suka pertanyaan "apa itu"! Jawabannya ada di seksi materi 🔍'] },
  { k:['kenapa','mengapa','sebab','alasan'],
    r:['Nah ini pertanyaan ilmuwan sejati! 🔬 Coba telusuri di materi ya',
       'Pertanyaan "kenapa" itu tanda kamu kritis! Jawabannya ada di materi 💡'] },
  { k:['bagaimana','cara','proses','langkah'],
    r:['Prosesnya seru banget! Baca step by step di materi ya 📚',
       'Buka seksi materi satu-satu, semua ada penjelasannya! 🐘'] },
  { k:['kuis','soal','latihan','tes','ujian'],
    r:['Semangat kuisnya! 💪 Klik tombol "Mulai Kuis" di bawah ya',
       'Kuis siap menunggumu! Baca materinya dulu biar tambah yakin 🎯',
       'Ayo kuis! Baca materi dulu supaya bisa dapat XP banyak ⭐'] },
  { k:['susah','sulit','bingung','ga ngerti','tidak mengerti','gak paham'],
    r:['Tenang, ini memang butuh waktu 💙 Baca pelan-pelan dari atas ya!',
       'Ella juga dulu sering bingung 😅 Coba baca ulang bagian yang susah',
       'Jangan menyerah! Kalau masih bingung, tanya gurumu juga ya 🌱'] },
  { k:['mudah','gampang','paham','ngerti','oke','ok'],
    r:['Keren banget! 🎉 Kalau sudah paham langsung coba kuisnya ya!',
       'Hebat! Kamu memang ilmuwan cilik! 🔬 Siap coba soal-soalnya?',
       'Yes! Langsung gas kuis! Kumpulin XP sebanyak-banyaknya 🚀'] },
  { k:['xp','poin','level','naik level','badge'],
    r:['XP bisa didapat dari menjawab soal kuis dengan benar 💛',
       'Level naik kalau XP-mu cukup 🏆 Terus rajin belajar ya!',
       'Badge didapat dari prestasi khusus — cek halaman Profil 🏅'] },
  { k:['ella','kamu siapa','siapa kamu'],
    r:['Aku Ella si gajah! 🐘 Asisten belajar IPA Saintara.',
       'Hehe aku Ella~ Gajah biru yang suka IPA 💙 Tanya aja!'] },
  { k:['terima kasih','makasih','thanks'],
    r:['Sama-sama! 🐘💙 Semangat terus belajarnya ya!',
       'Dengan senang hati! Jangan sungkan tanya lagi 🌟'] },
];

const DEFAULTS = [
  'Hmm, aku belum bisa jawab itu 🤔 Tapi jawabannya pasti ada di materi!',
  'Wah pertanyaan menarik! Coba cari di bagian seksi materi ya 📖',
  'Aku masih belajar juga 😅 Tapi kamu bisa tanya guru untuk yang ini!',
  'Interesting! Coba eksplorasi materinya dulu 🔍',
  'Ella kurang tahu yang itu 🐘 Baca materinya dulu yuk!',
];

function getEllaRespons(input) {
  const low = input.toLowerCase().trim();
  for (const rule of RULES) {
    if (rule.k.some(k => low.includes(k))) {
      return rule.r[Math.floor(Math.random() * rule.r.length)];
    }
  }
  return DEFAULTS[(_cc++) % DEFAULTS.length];
}

function kirimPesan() {
  const inp  = document.getElementById('ellaInput');
  const chat = document.getElementById('ellaChatArea');
  if (!inp || !chat) return;
  const text = inp.value.trim();
  if (!text) return;

  // User msg
  const um = document.createElement('div');
  um.className = 'chat-msg user';
  um.textContent = text;
  chat.appendChild(um);
  inp.value = '';
  chat.scrollTop = chat.scrollHeight;

  setEllaEkspresi('thinking');

  // Typing
  const typing = document.createElement('div');
  typing.className = 'chat-typing';
  typing.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
  chat.appendChild(typing);
  chat.scrollTop = chat.scrollHeight;

  setTimeout(() => {
    typing.remove();
    const resp = getEllaRespons(text);
    const em   = document.createElement('div');
    em.className = 'chat-msg ella';
    em.textContent = resp;
    chat.appendChild(em);
    chat.scrollTop = chat.scrollHeight;

    if (resp.includes('🎉') || resp.includes('🚀') || resp.includes('🏆')) {
      setEllaEkspresi('excited');
      setTimeout(() => setEllaEkspresi('happy'), 1000);
    } else if (resp.includes('😅') || resp.includes('belum bisa')) {
      setEllaEkspresi('sad');
      setTimeout(() => setEllaEkspresi('happy'), 1500);
    } else {
      setEllaEkspresi('happy');
    }
  }, 700 + Math.random() * 500);
}

// ── GREETING OTOMATIS ─────────────────────────────────────────
const GREETINGS = [
  'Hai! Mau belajar apa hari ini? 📚',
  'Semangat belajar IPA! 🔬',
  'Ada yang mau ditanyain? 🐘',
  'Kuis yuk! Kumpulin XP 🌟',
  'Ella siap bantu kamu! 💙',
];

function startGreeting() {
  let i = 0;
  setTimeout(() => {
    showEllaBubble(GREETINGS[i++]);
    setInterval(() => {
      const p = document.getElementById('ellaPanel');
      if (p && p.classList.contains('open')) return;
      showEllaBubble(GREETINGS[i++ % GREETINGS.length]);
    }, 9000);
  }, 2500);
}

// ── INJECT KEYFRAMES ──────────────────────────────────────────
function injectKeyframes() {
  if (document.getElementById('ellaKF')) return;
  const s = document.createElement('style');
  s.id = 'ellaKF';
  s.textContent = `
    @keyframes ellaJump{0%{transform:translateY(0) scale(1)}30%{transform:translateY(-14px) scale(1.08,.95)}55%{transform:translateY(0) scale(.95,1.05)}75%{transform:translateY(-5px) scale(1.03,.98)}100%{transform:translateY(0) scale(1)}}
    @keyframes ellaShake{0%,100%{transform:translateX(0)}20%{transform:translateX(-5px) rotate(-3deg)}40%{transform:translateX(5px) rotate(3deg)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}}
    @keyframes ellaWiggle{0%,100%{transform:rotate(0)}25%{transform:rotate(-4deg)}75%{transform:rotate(4deg)}}
    @keyframes ellaHappyBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
    @keyframes bubblePop{from{transform:scale(.7) translateY(10px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}
    @keyframes typingBounce{0%,60%,100%{transform:translateY(0);opacity:.4}30%{transform:translateY(-5px);opacity:1}}
    .typing-dot{width:6px;height:6px;border-radius:50%;background:#4DA8DA;animation:typingBounce 1.2s infinite}
    .typing-dot:nth-child(2){animation-delay:.2s}.typing-dot:nth-child(3){animation-delay:.4s}
    .chat-typing{display:flex;gap:5px;align-self:flex-start;background:#fff;border-radius:4px 16px 16px 16px;padding:10px 14px;box-shadow:0 2px 6px rgba(26,46,58,.07)}
  `;
  document.head.appendChild(s);
}

// ── INIT ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  injectKeyframes();
  injectEllaFloat();
  startGreeting();

  const inp = document.getElementById('ellaInput');
  if (inp) inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); kirimPesan(); } });

  document.addEventListener('click', e => {
    const panel = document.getElementById('ellaPanel');
    const float = document.getElementById('ellaFloat');
    if (panel && float && panel.classList.contains('open')) {
      if (!panel.contains(e.target) && !float.contains(e.target)) {
        panel.classList.remove('open');
        setEllaEkspresi('happy');
      }
    }
  });
});

// ── GLOBAL ────────────────────────────────────────────────────
window.setEllaEkspresi = setEllaEkspresi;
window.showEllaBubble  = showEllaBubble;
window.toggleEllaPanel = toggleEllaPanel;
window.kirimPesan      = kirimPesan;