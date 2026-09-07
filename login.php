<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// login.php — Halaman Login Siswa (v2 — Human Design)
// ============================================================
session_start();
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['siswa_id'])) {
    header('Location: /SAINTARA/pages/beranda.php');
    exit;
}

$error         = '';
$selectedKelas = 0;
$namaValue     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaValue     = trim($_POST['nama']  ?? '');
    $selectedKelas = (int)($_POST['kelas'] ?? 0);

    if (strlen($namaValue) < 2) {
        $error = 'Nama harus diisi minimal 2 huruf ya!';
    } elseif ($selectedKelas < 1 || $selectedKelas > 6) {
        $error = 'Pilih kelasmu dulu ya!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM siswa WHERE nama = ? AND kelas = ?");
        $stmt->execute([$namaValue, $selectedKelas]);
        $siswa = $stmt->fetch();

        if (!$siswa) {
            $avatarList    = ['🐘','🦁','🐯','🐧','🦊','🐸','🦋','🐬','🦄','🐙'];
            $avatarAcak    = $avatarList[array_rand($avatarList)];
            $stmt = $pdo->prepare("INSERT INTO siswa (nama, kelas, avatar) VALUES (?, ?, ?)");
            $stmt->execute([$namaValue, $selectedKelas, $avatarAcak]);
            $newId = $pdo->lastInsertId();
            $stmt  = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
            $stmt->execute([$newId]);
            $siswa = $stmt->fetch();
        }

        $_SESSION['siswa_id']     = $siswa['id'];
        $_SESSION['siswa_nama']   = $siswa['nama'];
        $_SESSION['siswa_kelas']  = $siswa['kelas'];
        $_SESSION['siswa_xp']     = $siswa['xp'];
        $_SESSION['siswa_level']  = $siswa['level'];
        $_SESSION['siswa_avatar'] = $siswa['avatar'];
        $_SESSION['siswa_streak'] = $siswa['streak'];

        header('Location: /SAINTARA/pages/beranda.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#4DA8DA">
  <title>Masuk — Saintara</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary:    #4DA8DA;
      --primary-dk: #3590C2;
      --mint:       #80D8C3;
      --yellow:     #FFD66B;
      --yellow-dk:  #E8B84B;
      --bg:         #F0F8FF;
      --white:      #FFFFFF;
      --navy:       #1A2E3A;
      --muted:      #6B8899;
      --error:      #E05555;
      --error-bg:   #FFF0F0;
      --radius-lg:  20px;
      --radius-xl:  28px;
      --radius-full:9999px;
      --font-head:  'Fredoka', sans-serif;
      --font-body:  'Nunito', sans-serif;
    }

    html, body {
      height: 100%;
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--navy);
      -webkit-font-smoothing: antialiased;
    }

    /* ── LAYOUT ─────────────────────────────────────────────── */
    .page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      padding: 0 0 40px;
      background: var(--bg);
    }

    /* ── TOP WAVE HEADER ────────────────────────────────────── */
    .top-wave {
      width: 100%;
      background: linear-gradient(160deg, #3A90C2 0%, #4DA8DA 60%, #6EC5E0 100%);
      padding: 36px 24px 70px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      overflow: hidden;
    }

    /* Geometric shapes — bukan bubble random */
    .geo-shape {
      position: absolute;
      opacity: 0.1;
      pointer-events: none;
    }
    .geo-1 {
      width: 200px; height: 200px;
      border: 3px solid white;
      border-radius: 50%;
      top: -60px; right: -60px;
    }
    .geo-2 {
      width: 120px; height: 120px;
      border: 2px solid white;
      border-radius: 50%;
      bottom: 20px; left: -30px;
    }
    .geo-3 {
      width: 60px; height: 60px;
      background: white;
      border-radius: 14px;
      transform: rotate(25deg);
      top: 20px; left: 40px;
      opacity: 0.06;
    }
    .geo-4 {
      width: 40px; height: 40px;
      background: var(--yellow);
      border-radius: 50%;
      top: 30px; right: 80px;
      opacity: 0.25;
    }

    /* Wave bawah */
    .wave-bottom {
      position: absolute;
      bottom: -1px; left: 0; right: 0;
    }

    /* ── ELLA SVG ───────────────────────────────────────────── */
    .ella-wrap {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 4px;
      animation: ellaIn 0.7s cubic-bezier(0.34,1.56,0.64,1) both;
      z-index: 2;
    }

    @keyframes ellaIn {
      from { opacity: 0; transform: translateY(-24px) scale(0.8); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ella-svg {
      width: 110px; height: 110px;
      filter: drop-shadow(0 6px 18px rgba(26,46,58,0.22));
      animation: ellaFloat 3.5s ease-in-out infinite;
    }

    @keyframes ellaFloat {
      0%,100% { transform: translateY(0); }
      50%      { transform: translateY(-8px); }
    }

    .ella-shadow {
      width: 65px; height: 12px;
      background: rgba(26,46,58,0.18);
      border-radius: 50%;
      margin-top: -4px;
      animation: shadowScale 3.5s ease-in-out infinite;
    }

    @keyframes shadowScale {
      0%,100% { transform: scaleX(1); opacity: 0.5; }
      50%      { transform: scaleX(0.75); opacity: 0.25; }
    }

    /* Speech bubble — clean minimal */
    .ella-speech {
      background: var(--white);
      border-radius: 16px 16px 16px 4px;
      border: 2px solid rgba(77,168,218,0.15);
      padding: 9px 16px;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--navy);
      text-align: center;
      margin-top: 10px;
      max-width: 220px;
      line-height: 1.4;
      animation: bubbleIn 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.4s both;
      min-height: 38px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    @keyframes bubbleIn {
      from { opacity: 0; transform: scale(0.8) translateY(6px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Brand */
    .brand-wrap {
      text-align: center;
      margin-top: 14px;
      z-index: 2;
      animation: fadeUp 0.5s ease 0.2s both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .brand-name {
      font-family: var(--font-head);
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--white);
      letter-spacing: -0.3px;
      line-height: 1;
    }

    .brand-name .accent { color: var(--yellow); }

    .brand-tagline {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.82);
      font-weight: 600;
      margin-top: 5px;
      letter-spacing: 0.2px;
    }

    /* ── CARD ───────────────────────────────────────────────── */
    .card-wrap {
      width: 100%;
      max-width: 420px;
      padding: 0 20px;
      margin-top: -36px;
      z-index: 10;
      animation: cardUp 0.6s cubic-bezier(0.34,1.56,0.64,1) 0.15s both;
    }

    @keyframes cardUp {
      from { opacity: 0; transform: translateY(32px) scale(0.96); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .card {
      background: var(--white);
      border-radius: var(--radius-xl);
      padding: 28px 24px 24px;
      box-shadow:
        0 4px 6px rgba(26,46,58,0.04),
        0 12px 32px rgba(26,46,58,0.10),
        0 1px 0 rgba(255,255,255,0.8) inset;
      border: 1px solid rgba(77,168,218,0.1);
    }

    .card-title {
      font-family: var(--font-head);
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--navy);
      text-align: center;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .card-title i {
      font-size: 1.3rem;
      color: var(--primary);
    }

    /* ── ERROR ──────────────────────────────────────────────── */
    .alert {
      background: var(--error-bg);
      border: 1.5px solid var(--error);
      border-radius: 12px;
      padding: 10px 14px;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--error);
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: alertShake 0.4s ease;
    }

    @keyframes alertShake {
      0%,100% { transform: translateX(0); }
      20%      { transform: translateX(-5px); }
      60%      { transform: translateX(5px); }
    }

    /* ── FORM ───────────────────────────────────────────────── */
    .field { margin-bottom: 18px; }

    .field-label {
      font-size: 0.8rem;
      font-weight: 800;
      color: var(--navy);
      margin-bottom: 7px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .field-label i {
      font-size: 1rem;
      color: var(--primary);
    }

    /* Input nama */
    .input-name {
      width: 100%;
      padding: 13px 16px 13px 44px;
      border: 2px solid rgba(77,168,218,0.22);
      border-radius: var(--radius-lg);
      font-family: var(--font-body);
      font-size: 0.97rem;
      font-weight: 600;
      color: var(--navy);
      background: #F7FBFF;
      transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
      outline: none;
      position: relative;
    }

    .input-wrap {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.1rem;
      color: var(--primary);
      pointer-events: none;
    }

    .input-name:focus {
      border-color: var(--primary);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(77,168,218,0.14);
    }

    .input-name.error-state {
      border-color: var(--error);
      box-shadow: 0 0 0 3px rgba(224,85,85,0.12);
      animation: alertShake 0.35s ease;
    }

    .input-name::placeholder {
      color: #9BBCCF;
      font-weight: 500;
    }

    /* ── KELAS GRID ─────────────────────────────────────────── */
    .kelas-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 9px;
    }

    .kelas-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 5px;
      padding: 12px 6px;
      border: 2px solid rgba(77,168,218,0.18);
      border-radius: 16px;
      background: #F7FBFF;
      cursor: pointer;
      transition:
        border-color 0.16s,
        background 0.16s,
        transform 0.2s cubic-bezier(0.34,1.56,0.64,1),
        box-shadow 0.16s;
      font-family: var(--font-body);
      position: relative;
      overflow: hidden;
      -webkit-tap-highlight-color: transparent;
    }

    .kelas-icon {
      width: 34px; height: 34px;
      border-radius: 10px;
      background: rgba(77,168,218,0.1);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .kelas-icon i {
      font-size: 1.15rem;
      color: var(--primary);
    }

    .kelas-label {
      font-family: var(--font-head);
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--muted);
      transition: color 0.15s;
    }

    .kelas-btn:hover {
      border-color: var(--primary);
      background: #EBF6FC;
      transform: translateY(-2px);
    }

    .kelas-btn.selected {
      border-color: var(--primary);
      background: linear-gradient(145deg, #4DA8DA, #3A90C2);
      box-shadow: 0 5px 16px rgba(77,168,218,0.38);
      transform: translateY(-3px) scale(1.03);
    }

    .kelas-btn.selected .kelas-icon {
      background: rgba(255,255,255,0.2);
    }

    .kelas-btn.selected .kelas-icon i { color: var(--white); }
    .kelas-btn.selected .kelas-label  { color: var(--white); }

    .kelas-btn:active { transform: scale(0.95); }

    /* Checkmark di sudut */
    .kelas-check {
      position: absolute;
      top: 5px; right: 6px;
      width: 16px; height: 16px;
      background: var(--yellow);
      border-radius: 50%;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .kelas-check i { font-size: 9px; color: var(--navy); }
    .kelas-btn.selected .kelas-check { display: flex; }

    /* ── TOMBOL SUBMIT ──────────────────────────────────────── */
    .btn-submit {
      width: 100%;
      margin-top: 22px;
      padding: 15px 20px;
      border: none;
      border-radius: var(--radius-full);
      background: linear-gradient(135deg, #4DA8DA 0%, #3A90C2 100%);
      color: var(--white);
      font-family: var(--font-head);
      font-size: 1.05rem;
      font-weight: 700;
      cursor: pointer;
      transition:
        transform 0.2s cubic-bezier(0.34,1.56,0.64,1),
        box-shadow 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 6px 20px rgba(77,168,218,0.38);
      position: relative;
      overflow: hidden;
    }

    /* Shimmer effect */
    .btn-submit::after {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 60%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
      transform: skewX(-15deg);
      transition: left 0.5s;
    }

    .btn-submit:not(:disabled):hover::after { left: 140%; }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(77,168,218,0.45);
    }

    .btn-submit:active {
      transform: scale(0.97);
      box-shadow: 0 3px 10px rgba(77,168,218,0.3);
    }

    .btn-submit:disabled {
      opacity: 0.75;
      cursor: not-allowed;
      transform: none;
    }

    .btn-submit i { font-size: 1.1rem; }

    /* Spinner */
    .spin-icon {
      animation: iconSpin 0.7s linear infinite;
      display: none;
    }

    .btn-submit.loading .spin-icon   { display: inline-block; }
    .btn-submit.loading .arrow-icon  { display: none; }
    .btn-submit.loading .btn-label   { opacity: 0.7; }

    @keyframes iconSpin { to { transform: rotate(360deg); } }

    /* ── DIVIDER ────────────────────────────────────────────── */
    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 18px 0 0;
    }

    .divider-line {
      flex: 1;
      height: 1px;
      background: rgba(77,168,218,0.15);
    }

    .divider-text {
      font-size: 0.75rem;
      color: var(--muted);
      font-weight: 600;
    }

    /* ── FOOTER LINK ────────────────────────────────────────── */
    .footer-link {
      text-align: center;
      margin-top: 16px;
      padding: 0 20px;
    }

    .footer-link a {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 11px 22px;
      background: var(--white);
      border: 1.5px solid rgba(77,168,218,0.2);
      border-radius: var(--radius-full);
      font-family: var(--font-body);
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--navy);
      text-decoration: none;
      transition: all 0.18s;
      box-shadow: 0 2px 8px rgba(26,46,58,0.06);
    }

    .footer-link a i {
      font-size: 1rem;
      color: var(--primary);
    }

    .footer-link a:hover {
      border-color: var(--primary);
      background: #EBF6FC;
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(77,168,218,0.2);
    }

    /* ── HIDDEN INPUT ───────────────────────────────────────── */
    #kelasInput { display: none; }
  </style>
</head>
<body>
<div class="page">

  <!-- Top section dengan wave -->
  <div class="top-wave">
    <!-- Geometric accents -->
    <div class="geo-shape geo-1"></div>
    <div class="geo-shape geo-2"></div>
    <div class="geo-shape geo-3"></div>
    <div class="geo-shape geo-4"></div>

    <!-- Ella -->
    <div class="ella-wrap">
      <svg class="ella-svg" viewBox="0 0 110 110" xmlns="http://www.w3.org/2000/svg" id="ellaSvg">
        <!-- Telinga kiri -->
        <ellipse cx="22" cy="60" rx="16" ry="20" fill="#3A90C2"/>
        <ellipse cx="22" cy="60" rx="10" ry="13" fill="#80D8C3"/>
        <!-- Telinga kanan -->
        <ellipse cx="88" cy="60" rx="16" ry="20" fill="#3A90C2"/>
        <ellipse cx="88" cy="60" rx="10" ry="13" fill="#80D8C3"/>
        <!-- Badan -->
        <ellipse cx="55" cy="74" rx="32" ry="26" fill="#4DA8DA"/>
        <!-- Kepala -->
        <circle cx="55" cy="48" r="28" fill="#4DA8DA"/>
        <!-- Highlight kepala -->
        <ellipse cx="46" cy="36" rx="9" ry="6" fill="rgba(255,255,255,0.13)"/>
        <!-- Mata kiri -->
        <ellipse cx="44" cy="43" rx="6.5" ry="7.5" fill="white"/>
        <circle  cx="45" cy="44" r="4" fill="#1A2E3A"/>
        <circle  cx="46.5" cy="42.5" r="1.4" fill="white"/>
        <!-- Mata kanan -->
        <ellipse cx="66" cy="43" rx="6.5" ry="7.5" fill="white"/>
        <circle  cx="67" cy="44" r="4" fill="#1A2E3A"/>
        <circle  cx="68.5" cy="42.5" r="1.4" fill="white"/>
        <!-- Pipi blush -->
        <ellipse cx="37" cy="52" rx="5.5" ry="3.5" fill="#FFD66B" fill-opacity="0.55"/>
        <ellipse cx="73" cy="52" rx="5.5" ry="3.5" fill="#FFD66B" fill-opacity="0.55"/>
        <!-- Belalai kiri -->
        <path d="M42 56 Q34 61 33 70 Q32 77 38 78" stroke="#3A90C2" stroke-width="6" stroke-linecap="round" fill="none"/>
        <circle cx="38" cy="78" r="4.5" fill="#3A90C2"/>
        <!-- Senyum -->
        <path d="M47 56 Q55 63 63 56" stroke="#1A2E3A" stroke-width="2" stroke-linecap="round" fill="none"/>
        <!-- Kaki -->
        <rect x="36" y="90" width="15" height="13" rx="7" fill="#3A90C2"/>
        <rect x="59" y="90" width="15" height="13" rx="7" fill="#3A90C2"/>
      </svg>
      <div class="ella-shadow"></div>
      <!-- Speech bubble -->
      <div class="ella-speech" id="ellaSpeech">
        Halo! Siap jadi ilmuwan cilik?
      </div>
    </div>

    <!-- Brand -->
    <div class="brand-wrap">
      <div class="brand-name">Sain<span class="accent">tara</span></div>
      <div class="brand-tagline">Lab Sains Seru untuk Ilmuwan Cilik</div>
    </div>

    <!-- Wave SVG -->
    <svg class="wave-bottom" viewBox="0 0 1440 54" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,28 C360,56 1080,0 1440,28 L1440,54 L0,54 Z" fill="#F0F8FF"/>
    </svg>
  </div>

  <!-- Card -->
  <div class="card-wrap">
    <div class="card">

      <div class="card-title">
        <i class="bi bi-person-circle"></i>
        Siapakah nama ilmuwan ini?
      </div>

      <?php if ($error): ?>
      <div class="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" id="loginForm" novalidate>

        <!-- Nama -->
        <div class="field">
          <div class="field-label">
            <i class="bi bi-pencil-fill"></i>
            Nama kamu
          </div>
          <div class="input-wrap">
            <i class="bi bi-person input-icon"></i>
            <input
              type="text"
              name="nama"
              id="namaInput"
              class="input-name"
              placeholder="Tulis nama lengkap kamu..."
              value="<?= htmlspecialchars($namaValue) ?>"
              maxlength="50"
              autocomplete="given-name"
              autofocus
            >
          </div>
        </div>

        <!-- Kelas -->
        <div class="field">
          <div class="field-label">
            <i class="bi bi-building"></i>
            Kelas berapa?
          </div>
          <div class="kelas-grid" id="kelasGrid">

            <?php
            $kelasDef = [
              1 => ['icon' => 'bi-flower1',        'label' => 'Kelas 1'],
              2 => ['icon' => 'bi-tree',            'label' => 'Kelas 2'],
              3 => ['icon' => 'bi-search',          'label' => 'Kelas 3'],
              4 => ['icon' => 'bi-eyeglasses',  'label' => 'Kelas 4'],
              5 => ['icon' => 'bi-droplet-half',    'label' => 'Kelas 5'],
              6 => ['icon' => 'bi-rocket-takeoff',  'label' => 'Kelas 6'],
            ];
            foreach ($kelasDef as $num => $def): ?>
            <div
              class="kelas-btn <?= $selectedKelas === $num ? 'selected' : '' ?>"
              onclick="pilihKelas(<?= $num ?>)"
              role="button"
              tabindex="0"
              aria-label="<?= $def['label'] ?>"
              onkeypress="if(event.key==='Enter') pilihKelas(<?= $num ?>)"
            >
              <div class="kelas-check"><i class="bi bi-check"></i></div>
              <div class="kelas-icon">
                <i class="bi <?= $def['icon'] ?>"></i>
              </div>
              <span class="kelas-label"><?= $def['label'] ?></span>
            </div>
            <?php endforeach; ?>

          </div>
          <input type="hidden" name="kelas" id="kelasInput" value="<?= $selectedKelas ?>">
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-submit" id="btnSubmit">
          <i class="bi bi-arrow-right-circle-fill arrow-icon"></i>
          <i class="bi bi-arrow-repeat spin-icon"></i>
          <span class="btn-label">Ayo Masuk!</span>
        </button>

      </form>
    </div>
  </div>

  <!-- Footer link guru — SELALU TERLIHAT -->
  <div class="footer-link" style="margin-top: 20px;">
    <a href="/SAINTARA/login-guru.php">
      <i class="bi bi-shield-lock-fill"></i>
      Masuk sebagai Guru / Admin
    </a>
  </div>

</div><!-- /.page -->

<script>
  let kelasSelected = <?= (int)$selectedKelas ?>;

  // Kelas speech bubbles
  const speeches = {
    0: 'Halo! Siap jadi ilmuwan cilik?',
    1: 'Kelas 1! Kita pelajari Panca Indera dulu!',
    2: 'Kelas 2! Siap belajar tentang hewan & tumbuhan?',
    3: 'Kelas 3! Kita eksplorasi sumber energi bareng!',
    4: 'Kelas 4! Rangka tubuh manusia menunggu kita!',
    5: 'Kelas 5! Sistem peredaran darah, keren banget!',
    6: 'Kelas 6! Ayo jelajahi Tata Surya bersama Ella!'
  };

  function pilihKelas(num) {
    kelasSelected = num;
    document.getElementById('kelasInput').value = num;

    // Update tampilan tombol
    document.querySelectorAll('.kelas-btn').forEach(btn => {
      const isSelected = parseInt(btn.getAttribute('data-num') || btn.onclick.toString().match(/\d+/)[0]) === num;
      btn.classList.toggle('selected', isSelected);
    });

    // Update speech Ella
    setSpeech(speeches[num] || speeches[0]);

    // Ella bounce kecil
    const svg = document.getElementById('ellaSvg');
    svg.style.animation = 'none';
    void svg.offsetWidth;
    svg.style.animation = 'ellaFloat 3.5s ease-in-out infinite, ellaBounce 0.35s ease';
  }

  // Tambahkan animasi bounce
  const style = document.createElement('style');
  style.textContent = `
    @keyframes ellaBounce {
      0%   { transform: translateY(0) scale(1); }
      35%  { transform: translateY(-12px) scale(1.04); }
      70%  { transform: translateY(-5px) scale(0.98); }
      100% { transform: translateY(0) scale(1); }
    }
  `;
  document.head.appendChild(style);

  function setSpeech(text) {
    const el = document.getElementById('ellaSpeech');
    el.style.opacity = '0';
    el.style.transform = 'scale(0.92)';
    el.style.transition = 'opacity 0.15s, transform 0.15s';
    setTimeout(() => {
      el.textContent = text;
      el.style.opacity = '1';
      el.style.transform = 'scale(1)';
    }, 150);
  }

  // Reaktif saat ketik nama
  let typingTimer;
  document.getElementById('namaInput').addEventListener('input', function() {
    clearTimeout(typingTimer);
    const val = this.value.trim();
    if (val.length >= 2) {
      typingTimer = setTimeout(() => {
        setSpeech('Hai, ' + val.split(' ')[0] + '! Senang ketemu kamu!');
      }, 500);
    } else {
      setSpeech(speeches[kelasSelected] || speeches[0]);
    }
  });

  // Submit validasi
  document.getElementById('loginForm').addEventListener('submit', function(e) {
    const nama  = document.getElementById('namaInput').value.trim();
    const input = document.getElementById('namaInput');

    if (nama.length < 2) {
      e.preventDefault();
      input.classList.add('error-state');
      input.focus();
      setSpeech('Nama kamu belum diisi nih!');
      setTimeout(() => input.classList.remove('error-state'), 500);
      return;
    }

    if (!kelasSelected) {
      e.preventDefault();
      const grid = document.getElementById('kelasGrid');
      grid.style.animation = 'none';
      void grid.offsetWidth;
      grid.style.animation = 'alertShake 0.4s ease';
      setSpeech('Pilih kelasmu dulu ya!');
      return;
    }

    // Loading state
    const btn = document.getElementById('btnSubmit');
    btn.classList.add('loading');
    btn.disabled = true;
  });

  // Sync kelas-btn data
  document.querySelectorAll('.kelas-btn').forEach((btn, i) => {
    btn.setAttribute('data-num', i + 1);
  });
</script>
</body>
</html>