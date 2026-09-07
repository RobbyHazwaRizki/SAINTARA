<audio id="bgmAudio" src="/SAINTARA/assets/audio/nengjemping-cheerful-little-boy-352347.mp3" loop></audio>
<audio id="sfxPop" src="/SAINTARA/assets/audio/soundreality-pop-click-312649.mp3"></audio>

<button id="btnSoundToggle" class="sound-toggle-btn" aria-label="Nyalakan/Matikan Suara">
  <i class="bi bi-volume-mute-fill"></i>
</button>

<style>
.sound-toggle-btn {
  position: fixed; bottom: 20px; right: 20px; width: 48px; height: 48px;
  border-radius: 50%; background: white; border: 3px solid #EBF6FC;
  color: #6B8899; font-size: 1.5rem; display: flex; align-items: center;
  justify-content: center; box-shadow: 0 4px 15px rgba(26,46,58,0.1);
  cursor: pointer; z-index: 9999; transition: all 0.2s cubic-bezier(0.34,1.56,0.64,1);
}
.sound-toggle-btn.sound-on { color: #4DA8DA; border-color: #4DA8DA; }
.sound-toggle-btn:hover { transform: scale(1.1) rotate(-10deg); }
.sound-toggle-btn:active { transform: scale(0.9); }
</style>

<script>
  const bgmAudio = document.getElementById('bgmAudio');
  const sfxPop = document.getElementById('sfxPop');
  const btnSound = document.getElementById('btnSoundToggle');
  
  bgmAudio.volume = 0.15; 
  sfxPop.volume = 0.8;

  let isSoundOn = localStorage.getItem('saintara_sound') === 'on';

  // =======================================================
  // PERBAIKAN: MELANJUTKAN MUSIK TANPA MENGULANG DARI AWAL
  // =======================================================
  // 1. TUNGGU file MP3 siap, baru atur waktunya agar tidak error/reset!
  bgmAudio.addEventListener('loadedmetadata', () => {
      const savedTime = localStorage.getItem('saintara_bgm_time');
      if (savedTime && isSoundOn) {
          bgmAudio.currentTime = parseFloat(savedTime);
      }
  });

  // 2. Simpan detik terakhir TEPAT SAAT siswa pindah halaman
  window.addEventListener('beforeunload', () => {
      localStorage.setItem('saintara_bgm_time', bgmAudio.currentTime);
  });
  window.addEventListener('pagehide', () => { // Cadangan untuk iPhone/Safari
      localStorage.setItem('saintara_bgm_time', bgmAudio.currentTime);
  });
  // =======================================================

  function updateSoundUI() {
    if (isSoundOn) {
      btnSound.classList.add('sound-on');
      btnSound.innerHTML = '<i class="bi bi-volume-up-fill"></i>';
    } else {
      btnSound.classList.remove('sound-on');
      btnSound.innerHTML = '<i class="bi bi-volume-mute-fill"></i>';
    }
  }

  if (isSoundOn) {
    // Timeout kecil agar browser tidak memblokir autoplay secara agresif
    setTimeout(() => {
        bgmAudio.play().catch(e => console.log('Menunggu interaksi user'));
    }, 100);
  }
  updateSoundUI();

  btnSound.addEventListener('click', () => {
    isSoundOn = !isSoundOn;
    localStorage.setItem('saintara_sound', isSoundOn ? 'on' : 'off');
    
    if (isSoundOn) {
      bgmAudio.play().catch(e => console.log(e));
    } else {
      bgmAudio.pause();
    }
    updateSoundUI();
  });

  function playPop() {
    if (isSoundOn) {
      sfxPop.currentTime = 0; 
      sfxPop.play().catch(e => console.log(e));
    }
  }

  // Pasang SFX Pop ke semua elemen interaktif KECUALI tombol suara itu sendiri
  document.querySelectorAll('a, button:not(#btnSoundToggle), .card-topik, .nav-item').forEach(elemen => {
    elemen.addEventListener('click', () => {
      playPop();
    });
  });

  // Pancingan autoplay saat layar diklik pertama kali
  document.body.addEventListener('click', () => {
      if(isSoundOn && bgmAudio.paused) {
          bgmAudio.play().catch(e => console.log(e));
      }
  }, { once: true });
</script>