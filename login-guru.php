<?php
// ============================================================
// SAINTARA — ELLA'S LAB
// login-guru.php — Halaman Login Guru / Admin
// ============================================================
session_start();
require_once __DIR__ . '/includes/db.php';

// Sudah login → redirect dashboard
if (!empty($_SESSION['guru_id'])) {
    header('Location: /SAINTARA/pages/admin/dashboard.php');
    exit;
}

$error    = '';
$username = '';
$loggedOut = isset($_GET['logout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password harus diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM guru WHERE username = ?");
        $stmt->execute([$username]);
        $guru = $stmt->fetch();

        if ($guru && password_verify($password, $guru['password'])) {
            $_SESSION['guru_id']       = $guru['id'];
            $_SESSION['guru_username'] = $guru['username'];
            $_SESSION['guru_nama']     = $guru['nama'];
            header('Location: /SAINTARA/pages/admin/dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
            // Sedikit delay untuk mencegah brute-force
            sleep(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Guru — Saintara Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary:   #4DA8DA;
      --secondary: #80D8C3;
      --accent:    #FFD66B;
      --bg:        #F0F8FF;
      --error:     #E05555;
      --text:      #1A2E3A;
      --muted:     #6B8899;
      --white:     #FFFFFF;
      --navy:      #1A2E3A;
      --navy2:     #243447;
    }

    html, body {
      height: 100%;
      font-family: 'Nunito', sans-serif;
      background: var(--navy);
      color: var(--white);
      -webkit-font-smoothing: antialiased;
    }

    /* ── BACKGROUND ──────────────────────────────── */
    .bg-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #0f1e28 0%, #1A2E3A 50%, #1e3a4a 100%);
    }

    /* Decorative orbs */
    .orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      pointer-events: none;
    }
    .orb-1 { width: 400px; height: 400px; background: rgba(77,168,218,.12); top: -100px; right: -100px; }
    .orb-2 { width: 300px; height: 300px; background: rgba(128,216,195,.08); bottom: -80px; left: -80px; }
    .orb-3 { width: 200px; height: 200px; background: rgba(255,214,107,.06); top: 50%; left: 50%; transform: translate(-50%,-50%); }

    /* Grid dots */
    .bg-wrap::before {
      content: '';
      position: absolute; inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,.04) 1px, transparent 1px);
      background-size: 32px 32px;
      pointer-events: none;
    }

    /* ── CARD ─────────────────────────────────────── */
    .login-card {
      background: rgba(255,255,255,.05);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 24px;
      padding: 40px 36px;
      width: 100%;
      max-width: 420px;
      position: relative;
      z-index: 1;
      box-shadow: 0 24px 64px rgba(0,0,0,.4);
    }

    /* ── HEADER CARD ──────────────────────────────── */
    .card-header { text-align: center; margin-bottom: 32px; }

    .logo-wrap {
      width: 68px; height: 68px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 18px;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      margin: 0 auto 16px;
      box-shadow: 0 8px 24px rgba(77,168,218,.35);
    }

    .card-title {
      font-family: 'Fredoka', sans-serif;
      font-size: 1.7rem;
      font-weight: 700;
      color: var(--white);
      margin-bottom: 6px;
    }

    .card-title span { color: var(--accent); }

    .card-subtitle {
      font-size: .85rem;
      color: rgba(255,255,255,.5);
    }

    /* ── BADGE LOGOUT ─────────────────────────────── */
    .logout-badge {
      background: rgba(128,216,195,.15);
      border: 1px solid rgba(128,216,195,.3);
      color: var(--secondary);
      border-radius: 10px;
      padding: 10px 14px;
      font-size: .83rem;
      font-weight: 700;
      display: flex; align-items: center; gap: 8px;
      margin-bottom: 20px;
    }

    /* ── ERROR ────────────────────────────────────── */
    .error-box {
      background: rgba(224,85,85,.15);
      border: 1px solid rgba(224,85,85,.3);
      color: #ff9090;
      border-radius: 10px;
      padding: 11px 14px;
      font-size: .85rem;
      font-weight: 700;
      display: flex; align-items: center; gap: 8px;
      margin-bottom: 20px;
      animation: shake .35s ease;
    }

    @keyframes shake {
      0%,100% { transform: translateX(0); }
      25%      { transform: translateX(-6px); }
      75%      { transform: translateX(6px); }
    }

    /* ── FORM ─────────────────────────────────────── */
    .form-group { margin-bottom: 18px; }

    .form-label {
      display: block;
      font-size: .78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .8px;
      color: rgba(255,255,255,.5);
      margin-bottom: 7px;
    }

    .input-wrap {
      position: relative;
      display: flex; align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      color: rgba(255,255,255,.35);
      font-size: 1rem;
      pointer-events: none;
    }

    .form-input {
      width: 100%;
      padding: 13px 14px 13px 42px;
      background: rgba(255,255,255,.07);
      border: 1.5px solid rgba(255,255,255,.12);
      border-radius: 12px;
      font-family: 'Nunito', sans-serif;
      font-size: .95rem;
      font-weight: 600;
      color: var(--white);
      outline: none;
      transition: border-color .2s, background .2s;
      -webkit-text-fill-color: var(--white);
    }

    .form-input::placeholder { color: rgba(255,255,255,.25); font-weight: 400; }

    .form-input:focus {
      border-color: var(--primary);
      background: rgba(77,168,218,.1);
    }

    .form-input.error-field { border-color: rgba(224,85,85,.6); }

    /* Password toggle */
    .toggle-pw {
      position: absolute;
      right: 14px;
      background: none;
      border: none;
      color: rgba(255,255,255,.35);
      cursor: pointer;
      font-size: 1rem;
      padding: 4px;
      transition: color .2s;
      display: flex; align-items: center;
    }
    .toggle-pw:hover { color: rgba(255,255,255,.7); }

    /* ── SUBMIT BUTTON ────────────────────────────── */
    .btn-submit {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, var(--primary), #3a90c2);
      color: var(--white);
      border: none;
      border-radius: 12px;
      font-family: 'Fredoka', sans-serif;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all .25s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      box-shadow: 0 6px 20px rgba(77,168,218,.3);
      margin-top: 8px;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(77,168,218,.45);
      background: linear-gradient(135deg, #5ab8ea, #4DA8DA);
    }

    .btn-submit:active { transform: translateY(0); }

    .btn-submit.loading { opacity: .7; pointer-events: none; }

    /* ── DIVIDER ──────────────────────────────────── */
    .divider {
      display: flex; align-items: center; gap: 12px;
      margin: 24px 0 16px;
      color: rgba(255,255,255,.2);
      font-size: .78rem;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1; height: 1px;
      background: rgba(255,255,255,.1);
    }

    /* ── HINT ─────────────────────────────────────── */
    .hint-box {
      background: rgba(255,214,107,.08);
      border: 1px solid rgba(255,214,107,.2);
      border-radius: 10px;
      padding: 12px 14px;
      font-size: .8rem;
      color: rgba(255,255,255,.5);
      line-height: 1.6;
    }

    .hint-box strong { color: var(--accent); }

    /* ── BACK LINK ────────────────────────────────── */
    .back-link {
      text-align: center;
      margin-top: 24px;
    }

    .back-link a {
      color: rgba(255,255,255,.35);
      font-size: .82rem;
      font-weight: 700;
      text-decoration: none;
      display: inline-flex; align-items: center; gap: 5px;
      transition: color .2s;
    }

    .back-link a:hover { color: var(--primary); }

    /* ── BOTTOM TAG ───────────────────────────────── */
    .bottom-tag {
      position: fixed;
      bottom: 16px;
      left: 50%; transform: translateX(-50%);
      font-size: .72rem;
      color: rgba(255,255,255,.2);
      white-space: nowrap;
      z-index: 0;
    }

    /* ── AUTOFILL FIX ─────────────────────────────── */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0 50px #1e3545 inset;
      -webkit-text-fill-color: var(--white);
      caret-color: var(--white);
      border-color: var(--primary) !important;
    }
  </style>
</head>
<body>

<div class="bg-wrap">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>

  <div class="login-card">

    <!-- Header -->
    <div class="card-header">
      <div class="logo-wrap">🔬</div>
      <h1 class="card-title">Saintara <span>Admin</span></h1>
      <p class="card-subtitle">Panel Guru — Ella's Lab</p>
    </div>

    <!-- Notif logout berhasil -->
    <?php if ($loggedOut): ?>
    <div class="logout-badge">
      <i class="bi bi-check-circle-fill"></i>
      Kamu berhasil logout. Sampai jumpa lagi!
    </div>
    <?php endif; ?>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="error-box">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" id="loginForm" autocomplete="off">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-wrap">
          <i class="bi bi-person-fill input-icon"></i>
          <input
            type="text"
            id="username"
            name="username"
            class="form-input <?= $error ? 'error-field' : '' ?>"
            placeholder="Masukkan username"
            value="<?= htmlspecialchars($username) ?>"
            autocomplete="username"
            required
            autofocus
          >
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <i class="bi bi-lock-fill input-icon"></i>
          <input
            type="password"
            id="password"
            name="password"
            class="form-input <?= $error ? 'error-field' : '' ?>"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          >
          <button type="button" class="toggle-pw" id="togglePw" onclick="togglePassword()" aria-label="Tampilkan password">
            <i class="bi bi-eye-fill" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn">
        <i class="bi bi-shield-lock-fill"></i>
        Masuk ke Panel Guru
      </button>

    </form>

    <div class="divider">akun default</div>

    <div class="hint-box">
      <strong>Username:</strong> admin &nbsp;·&nbsp; <strong>Password:</strong> password<br>
      <span style="font-size:.73rem;opacity:.7">Ganti password setelah pertama kali login di lingkungan produksi.</span>
    </div>

    <div class="back-link">
      <a href="/SAINTARA/login.php">
        <i class="bi bi-arrow-left"></i> Kembali ke Login Siswa
      </a>
    </div>

  </div><!-- /.login-card -->
</div><!-- /.bg-wrap -->

<div class="bottom-tag">Saintara — Ella's Lab © 2025 · BSI</div>

<script>
// Toggle password visibility
function togglePassword() {
  const pw  = document.getElementById('password');
  const ico = document.getElementById('eyeIcon');
  if (pw.type === 'password') {
    pw.type = 'text';
    ico.className = 'bi bi-eye-slash-fill';
  } else {
    pw.type = 'password';
    ico.className = 'bi bi-eye-fill';
  }
}

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('submitBtn');
  btn.classList.add('loading');
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memverifikasi...';
});
</script>

</body>
</html>