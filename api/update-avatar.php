<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (empty($_SESSION['siswa_id'])) { http_response_code(401); exit; }
$avatar = trim($_POST['avatar'] ?? '🐘');
$allowed = ['🐘','🦁','🐯','🐧','🦊','🐸','🦋','🐬','🦄','🐙'];
if (!in_array($avatar, $allowed)) { http_response_code(400); exit; }
$stmt = $pdo->prepare("UPDATE siswa SET avatar = ? WHERE id = ?");
$stmt->execute([$avatar, $_SESSION['siswa_id']]);
$_SESSION['siswa_avatar'] = $avatar;
echo json_encode(['status'=>'ok']);