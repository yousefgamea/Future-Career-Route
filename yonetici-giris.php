<?php
session_start();
include 'baglanti.php';

$hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = trim($_POST['mail'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    $stmt = $conn->prepare("SELECT YoneticiID, Ad, Soyad, Sifre FROM yonetici WHERE Mail = ?");
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $yonetici = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($yonetici && password_verify($sifre, $yonetici['Sifre'])) {
        $_SESSION['yonetici_id'] = $yonetici['YoneticiID'];
        $_SESSION['yonetici_ad'] = $yonetici['Ad'];
        header("Location: yonetici-panel.php");
        exit();
    } else {
        $hata = "Email veya şifre hatalı.";
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Yönetici Girişi - Future Career Route</title>
  <style>
    body { font-family: sans-serif; max-width: 400px; margin: 80px auto; padding: 0 20px; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; }
    input { width: 100%; padding: 8px; box-sizing: border-box; }
    button { padding: 10px 20px; background: #89ba16; color: #fff; border: none; cursor: pointer; }
    .error { color: red; margin-bottom: 15px; }
  </style>
</head>
<body>
  <h2>Yönetici Girişi</h2>
  <?php if ($hata): ?>
    <div class="error"><?php echo htmlspecialchars($hata); ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="mail" required>
    </div>
    <div class="form-group">
      <label>Şifre</label>
      <input type="password" name="sifre" required>
    </div>
    <button type="submit">Giriş Yap</button>
  </form>
  <p><a href="index.php">&laquo; Siteye dön</a></p>
</body>
</html>
